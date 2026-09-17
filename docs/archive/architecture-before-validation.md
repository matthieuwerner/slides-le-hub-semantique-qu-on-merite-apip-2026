# Historique antérieur à la validation du 15 septembre 2026

Ce document est conservé pour traçabilité. Il ne décrit plus nécessairement le code courant.

# Architecture

## 1. The claim

> API Platform can be the stable, published, semantic boundary of a system whose internals are
> free to change — including changing language, transport and execution model.

And its corollary, which is the part that usually goes unsaid:

> A network boundary has a price. It has to *buy* something. If it does not buy isolation,
> independent lifecycle, independent scaling, fault containment or clear ownership, you are
> paying for nothing.

The repository exists to make both testable rather than assertable. One endpoint, one contract,
three implementations behind it, and measurements of what each boundary costs.

## 2. The second claim, which is the more interesting one

Latency is the obvious cost of a boundary and the least interesting. The one worth arguing about
is **when you find out you were wrong**.

| Boundary | The contract is | Violations surface |
| --- | --- | --- |
| HTTP, hand-written client | prose, plus hope | at runtime, in production, on the first unusual payload |
| HTTP, generated client + static analysis | an OpenAPI document | in CI, before merge |
| Native Go extension | a function signature generated from Go source | at build time |
| In-process PHP | a PHP interface | at static-analysis time |

As the boundary moves inward, contract enforcement moves **earlier in time**. That is a stronger
argument for collapsing an internal boundary than any latency figure, because a millisecond is
cheap and a Friday-evening incident is not.

Which leads to the design rule this repository actually advocates:

> **Late-bound and semantic at the edge. Early-bound and typed on the inside.**

The public API is consumed by parties who discover it at runtime — third-party integrators, and
increasingly agents reading the OpenAPI and JSON-LD documents to work out what the thing does. It
*should* be self-describing and late-bound; that is what the semantics are for. An internal link
between two components you deploy together has none of those consumers, and every reason to be
checked by a compiler.

Treating both boundaries the same way is how teams end up versioning internal DTOs for an audience
of nobody.

## 3. Shape

```
                            ┌───────────────────────────────────────┐
   client  ──── HTTP ─────▶ │  API Platform 4.3  /  Symfony 8.1     │
   (or an agent reading     │                                       │
    the OpenAPI document)   │  AuthorizationRequest  (input DTO)    │
                            │  PaymentAuthorization  (output, JSON-LD)
                            │  PaymentAuthorizationProcessor        │
                            └──────────────────┬────────────────────┘
                                               │
                                     FeatureEnricher
                                    (outside the measured boundary)
                                               │
                                               ▼
                                    ┌──────────────────────┐
                                    │  RiskEngine          │  ← the only abstraction
                                    │  (one interface)     │
                                    └──────────┬───────────┘
                        ┌──────────────────────┼──────────────────────┐
                        ▼                      ▼                      ▼
                 PhpRiskEngine         HttpGoRiskEngine       NativeGoRiskEngine
                        │                      │                      │
                        │              generated Jane client   PHP array ⇄ Go map
                        │                      │                      │
                        ▼                      ▼                      ▼
                  PHP scorers          HTTP ─▶ risk-server       \BoundaryLab\Native\assess()
                                               │                      │
                                               └──────┬───────────────┘
                                                      ▼
                                                  go/risk
                                            (one package, both adapters)
```

The important line in that diagram is the bottom one. `go/risk` is compiled unchanged into both the
HTTP service and the PHP extension, so Experiments 2 and 3 differ **only** by the boundary between
them and the caller. Without that, the benchmark would be comparing two programs.

## 4. The three experiments

| | Experiment 1 | Experiment 2 | Experiment 3 |
| --- | --- | --- | --- |
| Implementation | `PhpRiskEngine` | `HttpGoRiskEngine` | `NativeGoRiskEngine` |
| Runs where | this process | another container | this process |
| Transport | none | HTTP/1.1 + JSON | CGO |
| Serialisation | none | JSON both ways | PHP array ⇄ Go map |
| Contract | PHP interface | OpenAPI → generated client | Go source → generated PHP stub |
| Drift caught | static analysis | CI | build |
| Adapter size | 0 lines | ~110 lines | ~60 lines |
| Deployable alone | no | yes | no |
| Scalable alone | no | yes | no |
| Fault containment | none | process + network | none |
| Extra operational surface | none | image, health, timeouts, retries, service discovery, distributed tracing | a custom FrankenPHP build |

The last two rows are the whole trade-off. Experiment 2 is the only one that buys anything
operationally, and it is also the only one that costs anything meaningful in latency.

## 5. Decision records

### ADR-1 — Exactly one abstraction

`RiskEngine` is the only interface introduced for the sake of the architecture. A repository, a
factory, a strategy resolver and a mapper interface could each be justified on a slide, and none of
them would make the point better.

The count of abstractions in a demonstration should be the count the argument requires. Adding more
would make the code look more "architectural" and the argument weaker, because a reader would no
longer be able to see that swapping the implementation touches exactly one seam.

### ADR-2 — No ORM, and no entity

There is no Doctrine in this project. API Platform is widely assumed to require an ORM; it does not.
The resource is a plain readonly class, and a state processor fills it in.

This also serves the talk's original promise of "short-circuiting the ORM": the shortest path is not
to circumvent it but to not introduce it. A risk decision is not a row.

### ADR-3 — Separate input and output models

`AuthorizationRequest` (in) and `PaymentAuthorization` (out) are distinct classes, and neither is the
domain model.

The input model's shape is dictated by *someone else's* convenience, so it changes on their schedule.
Letting it double as the domain model would tie the scoring logic to that schedule. The cost is a
handful of assignments in one enricher; the benefit is that the three engines never see an HTTP
concern.

### ADR-4 — Validation enforces form; the engine decides policy

`currency` is validated as three uppercase letters. Whether we settle JPY is **not** validated, it is
decided — and returned as a `201` carrying `declined` / `UNSUPPORTED_CURRENCY`.

This is the single most consequential API decision in the project. A payment client can act on
`UNSUPPORTED_CURRENCY`; it cannot act on `422 invalid request`. Modelling currency as a closed enum
would have collapsed the two and thrown the reason code away.

The same reasoning sets the amount rules: a negative amount is malformed (422), while zero and
above-ceiling amounts are policy (201 + declined).

### ADR-5 — `Server-Timing` proves which engine ran

The three demos need to show which runtime answered. The engine name is deliberately **not** in the
response body, because if it were, the claim that the contract is identical across all three would
be false, and clients could begin depending on it.

`Server-Timing` is a W3C standard, appears in browser devtools already, and lives in response
metadata. It gives the proof without touching the representation, the JSON-LD context, the Hydra
documentation or the OpenAPI schema.

### ADR-6 — Engine selection by header, disabled by default

A single running process can be told which engine to use, per request, via
`X-Boundary-Lab-Engine`. Two reasons, one honest and one better:

- **Honest:** it makes the live demos survive a conference. Three curls hit one process with no
  restarts, which removes the largest failure mode of a live demo.
- **Better:** it makes the benchmark fairer. Every engine is measured in the same process with the
  same warm worker state, so a difference cannot be an artefact of one container being colder.

It defaults to **off**, and when off the header is **rejected rather than ignored** — a silently
ignored override would let a benchmark report the wrong engine's numbers with total confidence.
Compose turns it on explicitly, in writing, with a comment saying why that would be unacceptable in
production.

### ADR-7 — Integer-only arithmetic, pinned to 32 bits

Money is in minor units. Every weight, threshold and hash is an integer, and every PRNG operation is
masked to 32 bits.

This started as a parity requirement and turned out to be the most interesting constraint in the
project. PHP has no unsigned 64-bit integer — arithmetic past `PHP_INT_MAX` silently becomes a float
— while Go's `uint64` wraps. Those behaviours cannot be reconciled, so the specification pins the
width instead of hoping. Float weights would have made "parity" mean "parity within epsilon", which
turns a decision threshold into a coin flip for inputs sitting exactly on it.

### ADR-8 — Explicit builder module instead of `xcaddy`

`xcaddy` is the documented way to build FrankenPHP with an extension. It is not used here:

1. It does not work in the builder image. `php-config --ldflags` returns `-pie`, and with
   `CGO_LDFLAGS` exported the `go install xcaddy` step itself dies with
   `unknown relocation type 313; compiled without -fpic?`.
2. Even working, it resolves versions at build time. A five-line `main.go` with a committed
   `go.mod`/`go.sum` pins Caddy, FrankenPHP and the extension exactly, which matters more for an
   artifact people will clone months later.

### ADR-9 — Generated code is committed

The Jane client and the FrankenPHP stub are in the repository.

`git clone && make demo` has to work without a code-generation step, and a reviewer should be able to
read what the generator actually produced instead of taking a claim about it on trust. The cost is
diff noise on regeneration, which is a fair price for a repository whose whole subject is contracts.

### ADR-10 — Two benchmark levels, always reported together

Level 1 measures the boundary crossing alone. Level 2 measures what a client feels through API
Platform.

Neither is reported without the other, because each is misleading alone: Level 1 overstates the
stakes, and Level 2 hides the mechanism. The interesting result is the *ratio* between them.

## 6. Rejected alternatives

**A mapper (AutoMapper / `symfony/object-mapper`) at the wire seam.** The talk's abstract promised
"Jane PHP and AutoMapper". Jane stays — it is healthy and it is the right tool. The mapper does not,
for two reasons worth stating rather than hiding:

- `jane-php/automapper` is **abandoned**; its last release is v7.5.3 from July 2023. It now lives as
  `jolicode/automapper`, and Symfony 8.1 separately ships a first-party `symfony/object-mapper`. The
  toolchain named in the abstract is no longer one toolchain.
- More importantly it would not help here. Automatic mapping pays off on wide, flat, mostly 1:1
  structures. Every field in this mapping crosses a value object (`Money->minorUnits`,
  `Money->currency->code`, `CardBin->digits`) or a backed enum needing failure handling on the way
  back. Configuring transformers for that is more code than the twenty explicit lines in
  `WireMapper`, and harder to read.

Where a mapper *would* earn its place is the case this project does not have: a generated client with
thirty nullable scalars mapping onto a similarly wide domain object.

**Generating the Go server from the OpenAPI document.** `oapi-codegen` would let one contract change
break the Go build too. Hand-written structs plus `contract_test.go` — which compares them to the
document by reflection — gives the same guarantee, keeps the Go readable, and adds no codegen step.
Verified: renaming `riskScore` in the contract fails the Go test with a precise message.

**A `Get` operation and persistence.** Would have made the resource dereferenceable, and would have
added a store, a lifecycle and shared mutable state to a benchmark. API Platform mints a proper
JSON-LD blank-node IRI (`/api/.well-known/genid/…`) for an identifier-less resource, which is
correct: a decision we do not keep is legitimately a blank node.

**A fourth async engine over Symfony Messenger.** The question "do I need this answer
synchronously?" is more important than "PHP or Go?", and deserves a slide. It does not deserve a
fourth code path in a repository whose argument is about synchronous boundaries. Noted in the README
as the honest first question to ask.

**A `sleep()` or arbitrary busy loop for the CPU-bound profile.** Would have inflated the numbers
without representing anything. The `ENSEMBLE` profile evaluates an integer decision-tree ensemble,
which is the actual shape of production card-fraud scoring. It is still reported separately and never
as "PHP vs Go".

## 7. Where the bodies are buried

Read `docs/limitations.md` before quoting anything from this repository. It lists what is
experimental, what is stubbed, what the benchmark does not measure, and the one place where the same
knowledge deliberately lives twice.
