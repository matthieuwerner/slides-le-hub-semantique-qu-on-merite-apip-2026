# Historique antérieur à la validation du 15 septembre 2026

Ce document est conservé pour traçabilité. Il ne décrit plus nécessairement le code courant.

# Limitations, caveats and things that are stubbed

Read this before quoting anything from this repository.

A conference demo that hides its caveats is worse than one that has none, because the audience
cannot tell which claims to trust. Everything below is a real constraint, and several of them are
more interesting than the parts that worked smoothly.

---

## 1. The Go extension mechanism is officially EXPERIMENTAL

Not "feels experimental" — labelled so by FrankenPHP itself:

```
$ frankenphp help
  extension-init  Initializes a PHP extension from a Go file (EXPERIMENTAL)
```

It is documented upstream at <https://frankenphp.dev/docs/extensions/> and it works. But
`EXPERIMENTAL` means the API can change, and **nothing in this repository should be read as a
recommendation to put a Go extension into production on the strength of a talk.**

What the repository does argue is narrower and, I think, defensible: the *mechanism* is real enough
to reason about the architecture it enables.

## 2. Objects cannot cross the native boundary

FrankenPHP's extension API supports `string`, `int`, `float`, `bool`, `array` and `void` as parameter
and return types. Objects are documented as "not yet implemented".

Consequences, in order of how much they matter:

- A `RiskInput` cannot be handed to Go. It is flattened into a PHP array and Go walks that array to
  rebuild its struct. **The native boundary removes the network and the JSON. It does not remove
  marshalling.**
- There is **no exception mechanism** across the boundary, and no status code either. A failure is
  reported in-band as `['error' => '...']`. Losing exceptions and status codes is one of the things
  the network boundary was quietly providing.
- Array *shapes* cannot be expressed in the generated stub, so a typo in one of the ten array keys is
  invisible to the generator. Go reads a missing key as zero, which means `merchantRiskTier`
  misspelled would silently make every merchant tier 0 and nothing would throw.

  This is mitigated, not solved: `api/stubs/native-risk.phpstan-stub.php` restates the same
  signatures with array shapes attached, and it does catch that typo at PHPStan level 9 (verified).
  But that stub is hand-maintained, which is exactly the kind of thing this project argues against.

## 3. `-D_GNU_SOURCE` is required and undocumented

The generated `risk.c` includes `php.h`, which uses `memrchr`, a GNU extension. Without
`-D_GNU_SOURCE` in `CGO_CFLAGS` the build fails:

```
zend_operators.h:236: error: implicit declaration of function 'memrchr'
```

This is not in the upstream extension documentation. Worth a documentation PR.

## 4. `gofmt` rewrites the `//export_php:` directives

`gofmt` always turns `//export_php:function` into `// export_php:function`. The reason is precise:
Go treats a comment as a directive only if it matches `//[a-z0-9]+:`, and `export_php` contains an
underscore, so it fails the test and gets normalised like ordinary prose.

**Verified: `frankenphp extension-init` accepts both forms**, so this is cosmetic today. The
gofmt-clean spaced form is what is committed, which keeps `gofmt -l` usable in CI.

It is still a paper-cut worth reporting upstream: a prefix without an underscore (`//frankenphp:` or
`//exportphp:`) would make the directives gofmt-stable. If a future parser tightens to require the
unspaced form, this repository would break — which is why `build/verify-extension.php` fails the
image build if the extension does not end up working.

## 5. `runtime/frankenphp-symfony` does not support Symfony 8

The usual way to run Symfony in FrankenPHP worker mode does not install here. Its newest release
(1.0.0) requires `symfony/dependency-injection: ^5.4 || ^6.0 || ^7.0`, and this application is on
Symfony 8.1. Composer refuses.

So `api/worker.php` is hand-written — about twenty lines. For a talk that is arguably an improvement,
since worker mode stops being a package and becomes something you can read. But if you are copying
this into a real Symfony 8 project, know that you are hand-rolling something a maintained package
normally gives you.

## 6. The scoring model is synthetic, and the ENSEMBLE profile is uncalibrated

- The rules are plausible for card risk and deliberately simple. They are not a real fraud model.
- The `ENSEMBLE` profile generates its decision trees from a fixed seed with **uniformly random leaf
  values**. It therefore represents the *computational shape* of a gradient-boosted fraud model — the
  right number of comparisons and memory accesses — and **not** a trained model. Its scores cluster
  near 50 because random symmetric leaves average out, so most ENSEMBLE decisions come out
  `challenged`. That is expected and it is not a bug; it is also why the profile is never presented
  as a business scenario.
- All BINs, merchants and devices are invented. No real primary account number appears anywhere.
  **PCI-DSS scope is explicitly out of scope.**

## 7. Feature enrichment is a stub

`FeatureEnricher` looks up issuer country, device velocity and merchant tier in three static arrays.
A real system would hit a feature store.

This is deliberate: a real velocity counter would make every run of `make bench` produce different
numbers, and the benchmark would be measuring the feature store. Enrichment also runs *outside* the
measured boundary so that all three engines receive byte-identical input.

## 8. The reference tables exist twice

`api/src/Engine/Php/ReferenceData.php` and `go/risk/rules.go` both contain the supported currencies,
the sanctioned issuer list, the expected-currency map and the high-risk BIN prefixes. This is the one
place in the project where the same knowledge is duplicated.

The alternatives were worse: loading a shared JSON file would put I/O and parsing inside the function
whose cost is being measured, and the Go extension would need to locate that file from inside a PHP
process.

The mitigation is that `fixtures/risk-cases.json` exercises every entry in those tables, so a
divergence fails `make parity-test` rather than quietly changing decisions. It is a mitigation, not a
fix, and a reviewer is right to flag it.

## 9. What the benchmark does not measure

**The environment.** Everything was run on a laptop (Apple M3, macOS) under Docker Desktop. These are
not production capacity figures. Container networking on Docker Desktop for macOS is materially slower
and noisier than a Linux host, which inflates the `go-http` numbers and especially their tail.

How much noise: in one run the `go-http` p50 for the *cheap* `RULES` profile came out **higher** than
for the expensive `ENSEMBLE` profile. Since RULES cannot possibly cost more to compute, that
inversion is pure measurement noise, and it is the clearest evidence available that the HTTP figures
are dominated by transport variance rather than by workload. Read them as an order of magnitude, not
a value.

The *relative* cost of the three boundaries is what the conclusions rest on, and that ratio is large
enough to survive this much noise. Any claim needing better than 2× precision on the HTTP path should
not be made from this data.

**Not measured at all:**

- concurrency behaviour under saturation, or backpressure
- memory and CPU under sustained load
- TLS, which every real deployment has and which is not free
- a real network between the API and the Go service — everything here is one Docker host
- connection pool exhaustion, partial failures, retry storms
- what happens when the Go service is *slow* rather than absent, which is the failure mode that
  actually takes systems down
- cold start, beyond noting that every engine is warmed before measurement

**A note on `hrtime()`.** The Level 1 harness times individual calls, and that timer costs about
40 ns per sample on this machine. The figure is printed with the results and is **not** subtracted.
On the fastest path measured (PHP `RULES`, roughly 1 µs) that is around 4% — visible, and not enough
to change any conclusion. It is disclosed so a reader can decide that for themselves.

## 10. The engine override header

`X-Boundary-Lab-Engine` lets one request choose its engine. It defaults to **off** and `compose.yaml`
turns it on explicitly for the lab.

An endpoint whose behaviour can be steered by a request header has no business being reachable in
production. It exists so three live demos can address one process with no restarts, and so the
benchmark can measure every engine with the same warm state. It is a lab affordance and the talk says
so out loud.

## 11. Not production-ready, for reasons beyond the above

No authentication or authorisation on any endpoint — this is a local demo binding to a local port, and
adding a token would obscure the argument without making it safer. **Do not expose it.** Also absent:
rate limiting, an audit trail, idempotency keys (a real authorization API needs them — a blind retry
on timeout is how one payment becomes two, which is why the HTTP client here deliberately does *not*
retry), and persistence.

The `/_lab/*` endpoints expose runtime internals — PHP version, JIT status, Go version, goroutine
count. Useful on stage, not something to ship.

## 12. Single architecture, single PHP version

Built and measured only on `linux/arm64` with PHP 8.5.9 ZTS and Go 1.26.7. The Go extension build has
not been exercised on `amd64`, on Alpine/musl, or against another PHP version. The FrankenPHP
documentation notes that musl needs a larger stack size for Symfony, which this project has not had to
deal with.
