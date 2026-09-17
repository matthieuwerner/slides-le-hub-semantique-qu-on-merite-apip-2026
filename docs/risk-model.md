# The risk model, specified once

This document is the **single normative specification** of the scoring logic. Both the PHP
implementation (`api/src/Engine/Php`) and the Go implementation (`go/risk`) are written
against this document, and `make parity-test` proves they agree.

If this document and the code disagree, investigate against independently calculated cases,
then reconcile the specification, implementation and tests together. Code is not its own oracle.

---

## 1. Why the specification is this pedantic

Two implementations in two languages must produce **bit-identical** results, otherwise the
central claim of the talk ("only the implementation changed, the behaviour did not") is
unfalsifiable.

That requirement forces two constraints that are worth stating explicitly, because they are
the kind of thing you only discover when you actually try to do this:

### 1.1 No floating point anywhere in the scoring path

Money is carried in **minor units** as integers. Every weight, threshold and score is an
integer. No `log`, no `pow`, no float weights, no division that is not integer division.

If the model used float weights, PHP and Go could differ in the last mantissa bits, and
"parity" would become "parity within epsilon" — which is not parity, and which quietly
turns a decision boundary into a coin flip for inputs sitting exactly on a threshold.

### 1.2 Integer width must be pinned to 32 bits

PHP has no unsigned 64-bit integer: arithmetic that overflows `PHP_INT_MAX` silently becomes
a float. Go's `uint64` wraps. Those two behaviours are irreconcilable.

Every hash/PRNG operation in this specification is therefore defined on **unsigned 32-bit**
values. PHP masks with `& 0xFFFFFFFF`, Go uses `uint32`. Both are then exact.

---

## 2. Feature vector

The engine never sees the HTTP payload. The application layer enriches the request into a
`RiskInput`, which is what crosses the boundary. Enrichment is deliberately outside the
measured boundary so that all three engines receive identical input.

| # | Feature | Type | Source |
| --- | --- | --- | --- |
| 0 | `amountMinor` | int | request (minor units) |
| 1 | `currency` | string, ISO 4217 alpha-3 | request |
| 2 | `country` | string, ISO 3166-1 alpha-2 | request (acquirer country) |
| 3 | `cardBin` | string, 6–8 digits | request |
| 4 | `merchantId` | string | request |
| 5 | `deviceId` | string | request |
| 6 | `binCountry` | string, alpha-2 | **enriched** from the static BIN table |
| 7 | `deviceTxCount24h` | int | **enriched** from the static velocity table |
| 8 | `merchantRiskTier` | int, 0–3 | **enriched** from the static merchant table |

The three enrichment tables are static lab fixtures (`fixtures/enrichment.json`), not a
database. They are deterministic on purpose: a feature store would make the benchmark
measure the feature store.

---

## 3. Hard rules (evaluated first, short-circuit)

Evaluated in this order. The first match wins and returns immediately.

| Order | Condition | Decision | Reason |
| --- | --- | --- | --- |
| 1 | `amountMinor <= 0` | `declined` | `INVALID_AMOUNT` |
| 2 | `currency` not in `{EUR, USD, GBP, CHF, SEK}` | `declined` | `UNSUPPORTED_CURRENCY` |
| 3 | `amountMinor > 5000000` | `declined` | `AMOUNT_LIMIT_EXCEEDED` |
| 4 | `binCountry` in `{IR, KP, SY, CU}` | `declined` | `SANCTIONED_ISSUER` |

When a hard rule fires, `riskScore` is **100**.

---

## 4. `RULES` profile (default)

Ten additive rules. Each contributes an integer number of points and carries a reason code.

| # | Rule | Points | Reason code |
| --- | --- | --- | --- |
| R1 | Amount band (see below) | 0–40 | `HIGH_AMOUNT` |
| R2 | `country != binCountry` | 18 | `CROSS_BORDER` |
| R3 | `currency` is not the expected currency for `country` | 10 | `CURRENCY_COUNTRY_MISMATCH` |
| R4 | `cardBin` starts with a prefix in the high-risk list | 22 | `HIGH_RISK_BIN` |
| R5 | Device velocity band (see below) | 0–30 | `DEVICE_VELOCITY` |
| R6 | `merchantRiskTier * 7` | 0–21 | `MERCHANT_RISK_TIER` |
| R7 | `amountMinor >= 1000` and `amountMinor % 1000 == 0` | 6 | `ROUND_AMOUNT` |
| R8 | `deviceId` is empty | 9 | `UNKNOWN_DEVICE` |
| R9 | `binCountry` is unknown (BIN not in table) | 12 | `UNKNOWN_BIN` |
| R10 | `deviceTxCount24h == 0` and `amountMinor > 5000` | 8 | `FIRST_SEEN_DEVICE_HIGH_AMOUNT` |

> **Why R7 and R10 use low thresholds.** An earlier draft had R7 fire only at or above
> `100000` and R10 only above `100000`. Both weights (6 and 8) are lower than the R1 amount-band
> weight that necessarily applies at those amounts (20 or more), so neither rule could ever be
> the *highest* contributor — which made `ROUND_AMOUNT` and `FIRST_SEEN_DEVICE_HIGH_AMOUNT`
> declared-but-unreachable reason codes. Two dead enum cases in a published contract is a defect,
> not a curiosity. The thresholds were lowered so that every reason code in §9 is genuinely
> emittable, and `RulesReachabilityTest` now asserts exactly that.

**R1 — amount bands** (`amountMinor`, inclusive lower bound):

| Band | Points |
| --- | --- |
| `< 2000` | 0 |
| `< 10000` | 4 |
| `< 50000` | 12 |
| `< 200000` | 20 |
| `< 1000000` | 30 |
| `>= 1000000` | 40 |

**R5 — device velocity bands** (`deviceTxCount24h`):

| Band | Points |
| --- | --- |
| `<= 2` | 0 |
| `3–5` | 5 |
| `6–10` | 12 |
| `11–20` | 20 |
| `> 20` | 30 |

**Score:** `riskScore = min(100, sum of all rule points)`.

**Reason:** the reason code of the rule with the **highest point contribution**, i.e. the
leading risk *factor*, not a judgement about it. Ties are broken by rule order (R1 before R2,
…). If every rule contributed 0, the reason is `LOW_RISK`.

So `status: approved` with `decisionReason: HIGH_AMOUNT` is a normal, meaningful response: the
request was approved, and the amount band was the largest of the (small) contributions.

> **An earlier draft got this wrong.** It forced the reason to `LOW_RISK` whenever the status
> was `approved`, on the grounds that an approved request has no actionable reason. That
> special case made five reason codes — `CURRENCY_COUNTRY_MISMATCH`, `ROUND_AMOUNT`,
> `UNKNOWN_DEVICE`, `UNKNOWN_BIN`, `FIRST_SEEN_DEVICE_HIGH_AMOUNT` — impossible to ever emit,
> because their weights are all below 20 and therefore can only be the largest contributor on
> a request that gets approved. Publishing five reason codes in an OpenAPI document that the
> engine can never return is a contract defect. Removing the special case fixed it and deleted
> code. `RulesReachabilityTest` now prevents the class of bug from returning.

Reporting the dominant reason rather than just the score makes parity strictly harder to
fake: the two implementations must agree on *why*, not only on *how much*.

---

## 5. `ENSEMBLE` profile

A deterministic integer decision-tree ensemble. This is included because it is the actual
shape of production card-fraud scoring (gradient-boosted tree ensembles), so it is a fair
representation of a CPU-bound scoring hot path — as opposed to a `sleep()` or an artificial
busy loop, which would prove nothing.

Hard rules from §3 still apply first.

### 5.1 The forest is generated, not stored

Storing a few hundred trees as JSON would make the benchmark measure JSON parsing. Instead
both implementations **generate the identical forest** from a fixed seed using the PRNG
below. This is why §1.2 exists.

### 5.2 PRNG — xorshift32, fully specified

State is `uint32`, seeded with `0x5EED1234`. `next()` is:

```
x ^= (x << 13)   masked to 32 bits
x ^= (x >> 17)
x ^= (x << 5)    masked to 32 bits
return x
```

PHP:

```php
$x ^= ($x << 13) & 0xFFFFFFFF; $x &= 0xFFFFFFFF;
$x ^= $x >> 17;
$x ^= ($x << 5) & 0xFFFFFFFF;  $x &= 0xFFFFFFFF;
```

Go:

```go
x ^= x << 13
x ^= x >> 17
x ^= x << 5
```

### 5.3 Forest shape

- `TREE_COUNT` = 256 (override with `BOUNDARY_LAB_TREE_COUNT`)
- `DEPTH` = 6 → 63 internal nodes, 64 leaves per tree
- Nodes are generated breadth-first; for each internal node, in this exact order:
  - `featureIndex = next() % 6`
  - `threshold    = next() % featureScale[featureIndex]`
- Then, for each of the 64 leaves in order:
  - `leafValue = (next() % 201) - 100`   → range `-100 … 100`

`featureScale` (the quantisation range of each numeric feature fed to the trees):

| Index | Derived feature | Scale |
| --- | --- | --- |
| 0 | `amountMinor / 100` (major units, truncated) | 60000 |
| 1 | `deviceTxCount24h` | 64 |
| 2 | `merchantRiskTier` | 4 |
| 3 | `crossBorder` (0 or 1) | 2 |
| 4 | `fnv1a32(cardBin) % 1024` | 1024 |
| 5 | `fnv1a32(merchantId) % 1024` | 1024 |

### 5.4 String hashing — FNV-1a, 32-bit

```
hash = 0x811C9DC5
for each byte b:
    hash ^= b
    hash = (hash * 0x01000193) masked to 32 bits
```

Byte-oriented, so it is identical in PHP (`ord()` over the string) and Go (`[]byte`).

### 5.5 Traversal and score

For each tree, start at node 0. At each internal node, go **left** if
`feature[featureIndex] <= threshold`, otherwise **right**. Accumulate the reached leaf value.

```
raw   = sum of the TREE_COUNT leaf values          // -100*N … 100*N
score = clamp( (raw * 50) / (100 * TREE_COUNT) + 50 , 0, 100 )
```

The division is **integer division truncating toward zero** in both languages. Reason code
is always `ENSEMBLE_MODEL`.

> Note on truncation: PHP's `intdiv()` and Go's integer `/` both truncate toward zero for
> mixed signs, so they agree. `floor()` semantics would **not** agree with Go and must not
> be used.

---

## 6. Decision thresholds (both profiles)

| `riskScore` | `status` |
| --- | --- |
| `< 20` | `approved` |
| `20 … 59` | `challenged` |
| `>= 60` | `declined` |

`challenged` means "step up to 3-D Secure", which is why the API models three outcomes and
not a boolean.

---

## 7. What parity asserts

For every case in `fixtures/risk-cases.json`, and for both profiles, all engines must agree
on **all three** of:

- `riskScore` (exact integer)
- `status`
- `decisionReason`

Agreement on the score alone would be too weak: two different rule sets can coincidentally
produce the same total.
