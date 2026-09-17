# Historique antérieur à la validation du 15 septembre 2026

Ce document est conservé pour traçabilité. Il ne décrit plus nécessairement le code courant.

# Boundary Lab

One HTTP endpoint. One JSON-LD contract. Three completely different implementations behind it:
PHP in-process, Go over HTTP, and Go compiled into the PHP process itself.

Then measurements of what each of those boundaries actually costs.

Built for a talk at **API Platform Conference 2026** — *API Platform: le hub sémantique qu'on mérite
(et comment piloter du Go avec)*. The repository is meant to be useful without the talk.

```sh
git clone https://github.com/matthieuwerner/slides-le-hub-semantique-qu-on-merite-apip-2026 boundary-lab
cd boundary-lab
make start
make demo
```

---

## 1. Why this exists

Two claims, and the repository exists to make them falsifiable rather than assertable.

**The first is about API Platform.** It can be the stable, published, semantic boundary of a system
whose internals are free to change — including changing language, transport and execution model.
Here the scoring engine moves from PHP to a Go service to a Go function inside the PHP process, and
the public contract does not move at all. Same endpoint, same JSON-LD, same OpenAPI document, same
Hydra description, same verdict.

**The second is about the price of a boundary.** A network hop has to *buy* something. If it does
not buy isolation, independent lifecycle, independent scaling, fault containment or clear ownership,
you are paying for nothing. So the repository measures the bill.

And a third, which turned out to be the more interesting one:

> Latency is the obvious cost of a boundary and the least important. The one worth arguing about is
> **when you find out you were wrong.**

| Boundary | The contract is | Violations surface |
| --- | --- | --- |
| HTTP, hand-written client | prose, plus hope | in production, on the first unusual payload |
| HTTP, generated client + static analysis | an OpenAPI document | in CI, before merge |
| Native Go extension | a function signature generated from Go source | at build time |
| In-process PHP | a PHP interface | at static-analysis time |

Which gives the design rule this project actually advocates: **late-bound and semantic at the edge,
early-bound and typed on the inside.** The public API is read at runtime by third parties — and
increasingly by agents parsing the OpenAPI and JSON-LD to work out what the thing does — so it
*should* be self-describing. An internal link between two components you deploy together has none of
those consumers and every reason to be checked by a compiler.

---

## 2. Architecture

```
   client ──── HTTP ────▶  API Platform 4.3 / Symfony 8.1
   (or an agent reading   ├── AuthorizationRequest   (input DTO, validated)
    the OpenAPI doc)      ├── PaymentAuthorization   (output, JSON-LD)
                          └── PaymentAuthorizationProcessor
                                        │
                                 FeatureEnricher      ← outside the measured boundary
                                        │
                                        ▼
                              ┌───────────────────┐
                              │   RiskEngine      │   ← the only abstraction in the project
                              └─────────┬─────────┘
                ┌───────────────────────┼───────────────────────┐
                ▼                       ▼                       ▼
        PhpRiskEngine           HttpGoRiskEngine        NativeGoRiskEngine
        in-process PHP          generated Jane client   PHP array ⇄ Go map
                │                       │                       │
                ▼                       ▼                       ▼
          PHP scorers          HTTP ─▶ risk-server      \BoundaryLab\Native\assess()
                                        │                       │
                                        └──────────┬────────────┘
                                                   ▼
                                               go/risk
                                     one package, compiled into both
```

The bottom line is the important one. `go/risk` is compiled **unchanged** into both the HTTP service
and the PHP extension, so Experiments 2 and 3 differ only by the boundary between them and the
caller. Without that, the benchmark would be comparing two programs instead of two boundaries.

There is deliberately **no ORM**. API Platform is often assumed to need one; it does not. A risk
decision is not a row.

Full reasoning and decision records: [`docs/architecture.md`](docs/architecture.md).

---

## 3. Quick start

Requires Docker and `make`. Nothing else — no local PHP, Go or Composer.

```sh
make start        # builds both images and waits until they are actually ready
make demo         # the three demos, back to back
make parity-test  # proves all three engines reach identical decisions
make bench        # measures what each boundary costs
make help         # everything else
```

The first build compiles a FrankenPHP binary with a Go extension in it, so expect a few minutes.
After that it is cached.

Ports default to **8099** (API) and **8098** (Go service), deliberately avoiding 8080/8081 because
those are taken on a surprising number of machines. Change them in `.env`.

---

## 4. The three implementations

### `make demo-php` — Experiment 1: the boring path

Scoring in PHP, in the same process. No serialisation, no network, nothing to configure. For the
default workload this is the **fastest** option available, and it is listed first not as a straw man
but as the honest default.

### `make demo-go-http` — Experiment 2: a Go service

The same scoring code, reached over HTTP. Compare `HttpGoRiskEngine` with `PhpRiskEngine`: the
difference is entirely serialisation, a correlation id, transport failure modes, a timeout policy, an
availability probe, and translating "the network misbehaved" into "no assessment is available".

That list is not incidental. It *is* the price side of the trade-off.

### `make demo-go-native` — Experiment 3: Go inside the PHP process

`\BoundaryLab\Native\assess()` is a Go function compiled into this FrankenPHP binary, running the
same `go/risk` package as the HTTP service. No socket, no JSON, no separate deployment.

It is not magic, and the talk is explicit about what it does *not* remove:

- **There is still a marshalling boundary.** FrankenPHP's extension API does not support objects as
  parameter or return types, so a `RiskInput` cannot be handed to Go. It is flattened to a PHP array
  and rebuilt on the other side. Measured cost: **~3 µs**.
- **There are no exceptions and no status codes** across the boundary. Failures come back in-band as
  `['error' => …]`. Losing both is one of the things the network boundary was quietly providing.
- **`frankenphp extension-init` is labelled EXPERIMENTAL by FrankenPHP itself.**

The demo prints the Go version and live goroutine count from inside the PHP worker, which is the
least deniable proof available that this is really happening.

---

## 5. The contract between PHP and Go

`contract/risk-engine.openapi.yaml` is the source of truth for the *internal* link. It is not the
public API — API Platform owns that.

Both sides are derived from it:

- **PHP** gets a type-safe client generated by [Jane](https://github.com/janephp/janephp)
  (`make contract-php`).
- **Go** is held to it by `go/internal/riskhttp/contract_test.go`, which compares the handler's
  structs to the document by reflection.

```sh
make contract-break
```

Renames one field in the contract and shows **both languages refusing to build** from that single
edit — Go via a failing contract test, PHP via PHPStan at the call site. Then puts it back.

The point is not that codegen is clever. It is that the failure moved from production to CI.

### The detail worth pausing on

`frankenphp extension-init` generates `assess(array $input): array` **as PHP** from the Go source.
That stub is fed to PHPStan, so **the Go compiler's view of the signature type-checks the PHP call
site**. The cross-language contract is not a document someone must remember to update — it is a build
artifact of one side.

The generator cannot express array *shapes*, so `api/stubs/native-risk.phpstan-stub.php` adds them by
hand. It earns its keep: it catches a one-character typo in an array key that would otherwise
silently make every merchant risk tier 0.

---

## 6. Correctness

```sh
make check   # everything CI runs
```

| | |
| --- | --- |
| `make test-php` | PHPUnit — unit, functional, public-contract |
| `make test-go` | `gofmt`, `go vet`, `go test` |
| `make stan` | PHPStan **level 9**, no baseline |
| `make parity-test` | all engines, all fixtures, both profiles |

Parity is the interesting one:

```
69 cases × 2 profiles × 3 engines = 414 assessments

php         d6a7ee872a773662a8ca2ff67c25973e90e186dc0535f8ada9dfa550a7847a7c
go-http     d6a7ee872a773662a8ca2ff67c25973e90e186dc0535f8ada9dfa550a7847a7c
go-native   d6a7ee872a773662a8ca2ff67c25973e90e186dc0535f8ada9dfa550a7847a7c
```

Score, status **and** reason code must match — agreement on the score alone would be too weak, since
two different rule sets can coincidentally total the same.

Three things stop that from being circular:

1. Unit tests on **both** sides assert values derived **by hand** from
   [`docs/risk-model.md`](docs/risk-model.md), so neither implementation is the reference.
2. `go/risk/fixtures_test.go` checks Go against the committed fixture, not against PHP at runtime.
3. A reachability test proves every reason code the contract declares can actually be produced. It
   earned its place immediately: the first version of the model had **five** reason codes that were
   published in the OpenAPI document and structurally impossible to emit.

Cross-language parity also forced the most interesting constraint in the project: **no floating point
anywhere, and integer width pinned to 32 bits.** PHP has no unsigned 64-bit integer — arithmetic past
`PHP_INT_MAX` silently becomes a float — while Go's `uint64` wraps. Those cannot be reconciled, so the
specification pins the width instead of hoping.

---

## 7. Results

Full numbers, method and caveats: [`docs/results.md`](docs/results.md). The three that matter:

**The native boundary costs about 3 µs.** Go's own benchmark scores the cheap workload in 68 ns, and
the same call through the extension takes 2.92 µs. For cheap work the boundary costs 40× the
computation, and **in-process PHP (0.92 µs) beats native Go (2.92 µs)**.

**The crossover is ~5 µs of work.** Sweeping the model size, PHP wins below roughly 256 trees and
native Go wins by about 2× above it. Go over HTTP never wins at any size measured — it would need a
workload ~25× heavier just to break even against in-process PHP.

**The engine is 0.2 % of a request.** Measured from the server's own `Server-Timing`:

| engine | engine time | share of request |
| --- | --- | --- |
| php | 18 µs | **0.20 %** |
| go-native | 54 µs | **0.43 %** |
| go-http | 6.41 ms | **19.53 %** |

The scoring logic — the part everyone argues about — is a fifth of one percent of the request. The
only way to make it visible in a latency budget is to move it across a network.

One more, because it is the practical one: **enabling the PHP JIT was worth 7.3× on the CPU-bound
workload; switching to native Go was worth a further 2.5×.** Check `opcache.jit` before you add a
language to your build.

---

## 8. What to use on Monday morning

```
Business logic, validation, orchestration?
    → PHP. In process. This is almost always the answer.

Do you actually need the answer synchronously?
    → If not, none of this matters. Queue it. This question outranks the rest.

Measured, profiled, CPU-bound hot path?
    → Turn on the JIT and measure again. That was 7.3× here, for one config line.
    → Still hot? Native is worth considering. Budget ~3 µs per crossing, and only
      cross when the work per call is comfortably larger than that.

Need independent deployment, independent scaling, fault isolation, a security
boundary, or a different team to own it?
    → Service boundary. Pay the network cost knowingly. You are buying properties,
      not speed — extracting this workload made it 158× slower.

Don't know yet?
    → PHP. In process. You can move it later; that is the whole point of the seam.
```

**Default to the simplest boundary until evidence justifies another one.** The evidence in
`docs/results.md` justified almost nothing, and that is the most useful thing it says.

---

## 9. Repository layout

```
api/          Symfony 8.1 + API Platform 4.3 (no ORM)
  src/Domain/     value objects, enums, the RiskEngine interface
  src/Engine/     the three implementations
  src/Lab/        parity, fixtures and benchmark commands
  stubs/          the Go-generated PHP contract, plus a typed view of it
go/
  risk/           the scoring core — compiled into BOTH Go adapters
  cmd/risk-server/  HTTP adapter          (Experiment 2)
  ext/            FrankenPHP adapter      (Experiment 3)
build/
  frankenphp/     pinned builder module for a FrankenPHP binary with the extension
contract/       the internal PHP ⇄ Go contract (OpenAPI)
fixtures/       the shared parity corpus
benchmark/      demos, parity, benchmarks, contract-break
docs/           research, architecture, the risk model, results, limitations
slides/         the deck
```

---

## 10. Read this before quoting anything

[`docs/limitations.md`](docs/limitations.md) lists what is experimental, what is stubbed, what the
benchmark does not measure, and the one place where the same knowledge deliberately lives twice.

The short version: the Go extension mechanism is officially experimental; the scoring model is
synthetic and the ENSEMBLE profile is uncalibrated by construction; feature enrichment is a static
stub; all card data is invented and PCI-DSS is explicitly out of scope; the benchmarks ran on a
laptop under Docker Desktop and say nothing about production capacity; and there is no
authentication on anything, so **do not expose this.**

Findings that might be worth upstreaming are collected there too — including that `gofmt` always
rewrites the `//export_php:` directives, because `export_php` contains an underscore and so fails
Go's directive test.

---

## 11. Versions

| | |
| --- | --- |
| PHP | 8.5.9 ZTS |
| Symfony | 8.1.6 |
| API Platform | 4.3.18 |
| FrankenPHP | 1.12.7 |
| Caddy | 2.11.4 |
| Go | 1.26.7 |
| Jane | 7.14.0 |
| PHPStan | 2.2.13, level 9 |
| PHPUnit | 12.5.34 |

Verified against upstream on 2026-09-04; see [`docs/research.md`](docs/research.md).

---

## 12. Licence

MIT. See [`LICENSE`](LICENSE).

Nothing here is affiliated with or endorsed by the API Platform, Symfony or FrankenPHP projects.
Where the repository describes their behaviour it cites the documentation it was checked against, and
where it disagrees with their documentation it says so.
