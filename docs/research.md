# État de ce journal

Journal historique de recherche antérieur à la passe de validation du 15 septembre 2026. Les mentions d’AutoMapper absent, les métriques et les recommandations anciennes ne décrivent plus nécessairement le dépôt actuel. Voir [CONFERENCE-VALIDATION.md](CONFERENCE-VALIDATION.md), [architecture.md](architecture.md) et [results.md](results.md) pour l’état vérifié. Les notes ci-dessous sont conservées pour traçabilité.

# Research log

Everything in this file was verified against upstream sources or executed locally on
**2026-09-04**. Nothing here is quoted from memory. Where a claim could not be verified,
it is marked `UNVERIFIED`.

Host used for all local verification:

| Item | Value |
| --- | --- |
| Machine | MacBook Air, Apple Silicon (`arm64`) |
| OS | macOS 27.0 (build 26A5406e) |
| Docker | 28.1.1, Docker Compose v2.35.1 |
| Host PHP | 8.5.9 (CLI, NTS) |
| Host Go | 1.24.1 darwin/arm64 |
| Host Composer | 2.10.2 |

> The host Go toolchain is **not** used to build the extension. All Go/CGO work happens
> inside the FrankenPHP builder image (Go 1.26.7 linux/arm64) so results are reproducible.

---

## 1. Version matrix (verified)

| Component | Version | How it was verified |
| --- | --- | --- |
| FrankenPHP | **v1.12.7** (2026-08-07) | `GET api.github.com/repos/php/frankenphp/releases/latest` |
| FrankenPHP builder image | `dunglas/frankenphp:1.12.7-builder-php8.5` | pulled and inspected |
| PHP (in builder) | **8.5.9 ZTS** (`Thread Safety => enabled`) | `php -i` inside image |
| Caddy (in FrankenPHP) | **v2.11.4** | `frankenphp version` |
| Go (in builder) | **1.26.7 linux/arm64** | `/usr/local/go/bin/go version` |
| API Platform | **4.3.18** (2026-09-04) | Packagist `p2/api-platform/core.json` |
| Symfony | **8.1.6** (2026-08-30) | Packagist `p2/symfony/framework-bundle.json` |
| Jane OpenAPI 3 | **7.14.0** (2026-08-31) | Packagist |
| jolicode/automapper | **10.2.0** (2026-04-27) | Packagist |
| symfony/object-mapper | **8.1.5** (2026-08-19) | Packagist |
| PHP (latest upstream) | 8.5.10 (2026-08-27) | `php.net/releases` |
| Go (latest upstream) | 1.27.1 | `go.dev/dl/?mode=json` |

### 1.1 FrankenPHP moved to the PHP organisation

The canonical repository is now **`github.com/php/frankenphp`**, not `dunglas/frankenphp`.
The **Go module path is still `github.com/dunglas/frankenphp`** — verified by reading
`go.mod` inside the builder image:

```
module github.com/dunglas/frankenphp
go 1.26.0
```

The Caddy integration is a **separate nested Go module**, `github.com/dunglas/frankenphp/caddy`,
which is what pins Caddy:

```
module github.com/dunglas/frankenphp/caddy
require (
    github.com/caddyserver/caddy/v2 v2.11.4
    github.com/dunglas/frankenphp v1.12.7
    ...
)
```

This matters for us: a custom build must require **both** modules explicitly.

### 1.2 API Platform 4.3 / Symfony 8.1 compatibility

`api-platform/core` 4.3.18 declares `symfony/*: ^7.4 || ^8.0` and `php: >=8.2`.
So Symfony 8.1 + PHP 8.5 is inside the supported range. Confirmed by an actual
`composer install` (see `api/composer.lock`), not only by reading constraints.

---

## 2. Writing PHP extensions in Go with FrankenPHP

Source: <https://frankenphp.dev/docs/extensions/>

### 2.1 It is officially supported, and officially EXPERIMENTAL

The feature is documented upstream, and the CLI marks it explicitly:

```
$ frankenphp help
  ...
  extension-init  Initializes a PHP extension from a Go file (EXPERIMENTAL)
```

That `(EXPERIMENTAL)` label is printed by FrankenPHP itself. We surface it in the talk
rather than hiding it — see `docs/limitations.md`.

### 2.2 The generator workflow (verified end to end)

`gen_stub.php` ships **inside the builder image** at
`/usr/local/lib/php/build/gen_stub.php`, so php-src does not need to be downloaded
separately (the upstream docs describe fetching php-src; the builder image makes that
step unnecessary).

```sh
GEN_STUB_SCRIPT=/usr/local/lib/php/build/gen_stub.php \
  frankenphp extension-init risk.go
```

From one annotated Go file this produced:

| File | Role |
| --- | --- |
| `risk.go` | our source, **never modified** |
| `risk_generated.go` | CGO wrappers + `frankenphp.RegisterExtension()` in `init()` |
| `risk.c` / `risk.h` | C glue |
| `risk_arginfo.h` | Zend arg info (from `gen_stub.php`) |
| `risk.stub.php` | PHP stub, for IDE + static analysis |

The directive comments drive everything:

```go
//export_php:namespace BoundaryLab\Native
//export_php:function assess(array $input): array
```

### 2.3 Type juggling: the constraint that shapes our architecture

From the upstream type table:

| PHP type | Go type | Direct? |
| --- | --- | --- |
| `int`, `float`, `bool` (+ nullable) | `int64`, `float64`, `bool` (+ pointers) | yes |
| `string` | `*C.zend_string` + `GoString()` / `PHPString()` | no |
| `array` | `AssociativeArray` / `map[string]any` / `[]any` | no |
| **`object`** | **struct** | **"Not yet implemented"** |

> Upstream, verbatim scope note: objects cannot currently be used as extension
> function/method parameter or return types. Supported: `string`, `int`, `float`, `bool`,
> `array`, `void`.

**Architectural consequence, and a load-bearing point of the talk:** even the
"zero-network" in-process boundary is **not** a zero-marshalling boundary. We cannot hand
a `RiskInput` value object to Go. We must flatten to a PHP array and rehydrate on the Go
side. So the honest framing is *"we removed the network and the JSON, we did not remove
the boundary"*.

### 2.4 Build path (verified, with two gotchas)

`xcaddy` is the documented build tool. Two problems were hit and solved:

**Gotcha 1 — `xcaddy` bootstrap fails in the builder image.**
`php-config --ldflags` returns `-Wl,-O1 -pie`. With `CGO_LDFLAGS` exported, `go install
xcaddy` itself fails:

```
unknown relocation type 313; compiled without -fpic?
```

**Resolution:** we do not use `xcaddy`. We ship an explicit builder module with a
5-line `main.go` (exactly what xcaddy generates anyway) and a committed `go.mod`/`go.sum`.
This removes a build dependency and makes the build **fully version-pinned and
reproducible**, which matters more for a conference artifact than convenience.

**Gotcha 2 — `memrchr` requires `_GNU_SOURCE`.**
Compiling the generated `risk.c` (which includes `php.h`) fails with:

```
zend_operators.h:236: error: implicit declaration of function 'memrchr';
```

**Resolution:** `CGO_CFLAGS="-D_GNU_SOURCE $(php-config --includes)"`.
This is not in the upstream extension docs. Reported in `docs/limitations.md`.

Working build invocation:

```sh
export CGO_ENABLED=1
export CGO_CFLAGS="-D_GNU_SOURCE $(php-config --includes)"
export CGO_LDFLAGS="$(php-config --ldflags) $(php-config --libs)"
go build -tags=nobadger,nomysql,nopgx,nomercure,nowatcher -o frankenphp .
```

### 2.5 Proof of concept: it works

Minimal extension, built into a custom FrankenPHP binary, executed with `php-cli`:

```php
var_dump(extension_loaded('poc'));                        // bool(true)
var_dump(function_exists('BoundaryLab\Poc\assess'));      // bool(true)
var_dump(\BoundaryLab\Poc\assess(['amount' => 42069]));
// array(2) { ["echoAmount"]=> int(42069) ["engine"]=> string(13) "go-native-poc" }
```

This was run before any application code was written, precisely because Act 3 of the talk
depends on it.

---

## 3. Contract generation between PHP and Go

### 3.1 The abstract's tooling claim needs one correction

The submitted abstract promises *"Jane PHP et AutoMapper"*. Status as of today:

| Package | Status | Evidence |
| --- | --- | --- |
| `jane-php/open-api-3` | **healthy**, 7.14.0 released 2026-08-31, supports Symfony 8 | Packagist |
| `jane-php/automapper` | **dead**, last release **v7.5.3, 2023-07-10** | Packagist |

`jane-php/automapper` was extracted out of Jane and now lives as **`jolicode/automapper`**
(10.2.0, requires PHP `^8.4`). Independently, Symfony now ships a first-party
**`symfony/object-mapper`** component (8.1.5, requires PHP `>=8.4.1`).

So the talk keeps Jane (still the right tool, still maintained) and corrects the mapper
half of the claim. That correction is itself content: it is a concrete example of the
"generated client boundary" tooling landscape moving under you.

### 3.2 Why a mapper is justified here at all

A mapper must earn its place, otherwise it is decoration. It earns it precisely at the
generated-client seam: Jane emits DTOs that are permissive by construction
(nullable-everything, no invariants) because they mirror the wire format. The domain type
is strict. Mapping generated-DTO → domain is exactly the boring, repetitive,
easy-to-get-wrong code worth generating.

We do **not** use a mapper between the domain and the API Platform resource: that mapping
is 4 fields and hand-written code is clearer.

### 3.3 Go side of the contract

`UNVERIFIED at time of writing` — candidate: `oapi-codegen` for generating Go server
types from the same OpenAPI document. Decision recorded in `docs/architecture.md` once
validated.

---

## 4. Benchmarking approach

### 4.1 Available tooling on the host

Checked: `wrk` MISSING, `oha` MISSING, `k6` MISSING, `vegeta` MISSING, `hey` MISSING,
`ab` present (`/usr/sbin/ab`), `hyperfine` MISSING.

`ab` is not acceptable (no p99, single-threaded, keep-alive quirks). Since nothing usable
is installed, the load generator must be containerised anyway — which is better for
reproducibility. **Decision: k6 via Docker**, because it reports p50/p90/p95/p99 natively,
is scriptable (needed to drive the engine-selection header), and can export raw JSON for
chart generation.

### 4.2 Two measurement levels, deliberately

A single end-to-end number would hide the entire point of the talk. So:

| Level | What it measures | Tool |
| --- | --- | --- |
| **L1 — micro** | cost of *the boundary crossing alone*, engine called directly, no HTTP, no API Platform | in-repo PHP CLI harness using `hrtime(true)` |
| **L2 — macro** | what a client actually feels, full JSON-LD request through API Platform | k6 in Docker |

The expected — and, if confirmed, the most useful — result is that L1 differences are
large and L2 differences are largely invisible, because API Platform + HTTP + JSON-LD
serialisation dominate. That asymmetry is the argument, not a footnote.

### 4.3 Two workload profiles, both honest

| Profile | What it is | Why |
| --- | --- | --- |
| `RULES` (default) | ~15 deterministic scoring rules | the realistic functional scenario |
| `ENSEMBLE` | evaluation of an integer-threshold decision-tree ensemble | a *real* shape of production fraud scoring (gradient-boosted ensembles), not a synthetic sleep or busy-loop |

`ENSEMBLE` is not a trick to flatter Go: evaluating a few hundred trees per authorisation
is what actual card-fraud scoring does. It is nonetheless reported separately and never
presented as "PHP vs Go" in general.

### 4.4 Determinism requirement

Cross-language parity forces a constraint that turned out to be interesting on its own:
**no floating point anywhere in the scoring path.** Money is in minor units, weights are
integers, and thresholds are integers. If the score used `log()` or float weights, PHP and
Go could disagree in the last bits and parity would be unprovable. Integer-only arithmetic
makes the two implementations bit-identical by construction.

---

## 5. Upstream references

- FrankenPHP — Writing PHP extensions in Go: <https://frankenphp.dev/docs/extensions/>
- FrankenPHP — Compile from sources: <https://frankenphp.dev/docs/compile/>
- FrankenPHP — Static/standalone binary: <https://frankenphp.dev/docs/static/>
- FrankenPHP — Extension workers: <https://frankenphp.dev/docs/extension-workers/>
- FrankenPHP — Use as a Go library: <https://frankenphp.dev/docs/library/>
- API Platform docs: <https://api-platform.com/docs/>
- Server-Timing (W3C): <https://www.w3.org/TR/server-timing/>

Content from upstream documentation was read and paraphrased here; it was not copied
verbatim beyond short identifiers and command lines.
