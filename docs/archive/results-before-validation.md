# Historique antérieur à la validation du 15 septembre 2026

Ce document est conservé pour traçabilité. Il ne décrit plus nécessairement le code courant.

# Results

Every number here was produced by the scripts in `benchmark/` on the machine described in §1, and
the raw JSON is committed under `benchmark/results/`. Nothing is estimated.

**Read §5 before quoting any of it.**

---

## 1. Environment

| | |
| --- | --- |
| Machine | MacBook Air, Apple M3, 12 cores |
| OS | macOS 27.0 |
| Container runtime | Docker Desktop, Docker 28.1.1 |
| PHP | 8.5.9 ZTS, opcache on, **tracing JIT on**, 128 MB JIT buffer |
| Go | 1.26.7 linux/arm64 |
| FrankenPHP | 1.12.7, worker mode, 4 workers |
| API Platform / Symfony | 4.3.18 / 8.1.6 |
| Forest size | 256 trees unless stated |

This is a laptop under a desktop container runtime. **These are not production capacity figures.**
What the measurements are good for is the *relative* cost of three boundaries, and that ratio turns
out to be stable enough to be worth something.

---

## 2. Parity first

No performance number means anything if the engines disagree.

```
69 cases × 2 profiles × 3 engines = 414 assessments

php         138   d6a7ee872a773662a8ca2ff67c25973e90e186dc0535f8ada9dfa550a7847a7c
go-http     138   d6a7ee872a773662a8ca2ff67c25973e90e186dc0535f8ada9dfa550a7847a7c
go-native   138   d6a7ee872a773662a8ca2ff67c25973e90e186dc0535f8ada9dfa550a7847a7c

276 pairwise comparisons, 0 disagreements.
```

Same score, same status, same reason code, in every case, under both profiles. `make parity-test`.

---

## 3. Level 1 — what the boundary costs, isolated

`RiskEngine::assess()` called in a loop from a PHP CLI process that has all three engines available.
No HTTP server, no API Platform, no JSON-LD. 4000 iterations, 500 warm-up. p50 shown.

### 3.1 The cheap workload (`RULES`, ~15 additive rules)

| engine | p50 | vs PHP |
| --- | --- | --- |
| **php** | **0.92 µs** | — |
| go-native | 2.92 µs | **3.2× slower** |
| go-http | 145 µs | 158× slower |

**PHP wins, and it is not close.** For a workload this cheap the native Go boundary costs more than
the computation it is protecting: Go's own benchmark puts the same scoring at **68 ns**, so of the
2.92 µs spent in `go-native`, roughly **2.85 µs is boundary** — flattening a `RiskInput` into a PHP
array, walking it in Go, and building a PHP array back.

That is the single most useful number in this repository:

> **The native in-process boundary costs about 3 µs. Crossing it to save 68 ns of work is a bad
> trade, and no amount of Go being fast changes that.**

### 3.2 The expensive workload (`ENSEMBLE`), swept across forest sizes

p50, microseconds:

| trees | php | go-native | go-http | winner |
| --- | --- | --- | --- | --- |
| 16 | **0.96** | 3.63 | 212.9 | PHP, 3.8× |
| 64 | **1.92** | 3.63 | 157.3 | PHP, 1.9× |
| 256 | 5.58 | **5.50** | 146.6 | tie |
| 1024 | 26.79 | **12.75** | 245.6 | Go, 2.1× |
| 4096 | 135.38 | **63.12** | 370.0 | Go, 2.1× |

**The crossover is at roughly 256 trees, which is about 5 µs of work.** Below it, PHP wins because
the boundary dominates. Above it, native Go wins by about 2×, and the factor stops growing because
both sides are then doing real work.

`go-http` never wins. Not at any forest size measured. To break even against in-process PHP on
latency alone it would need the computation to exceed its own ~150 µs floor, which needs a workload
roughly **25× heavier than 256 trees** — and even at 4096 trees, PHP in-process (135 µs) is still
2.7× faster than Go across a network (370 µs).

### 3.3 The JIT is worth more than the language change

Same machine, same code, `ENSEMBLE` at 256 trees:

| configuration | p50 | |
| --- | --- | --- |
| PHP, opcache off, no JIT | 38.27 µs | |
| PHP, opcache + tracing JIT | **5.23 µs** | **7.3× faster** |
| Go, native, in process | 2.06 µs | 2.5× faster than JIT-ed PHP |

Without the JIT, Go looks **18.6×** faster. With it, **2.5×**.

If you are about to rewrite a hot path in another language, check `opcache.jit` first. It is one
line of configuration against a second runtime in your build, your deployment and your on-call
rotation.

---

## 4. Level 2 — what a client actually feels

k6, 1 virtual user, connection reused, 3 × 10 s per engine, measured from inside the compose
network. Milliseconds.

| engine | min | p50 | p95 | p99 | per-run p50 |
| --- | --- | --- | --- | --- | --- |
| php | 0.57 | **2.65** | 9.95 | 24.25 | 2.6 / 2.4 / 2.9 |
| go-native | 1.06 | **4.67** | 11.61 | 56.93 | 4.7 / 4.3 / 4.8 |
| go-http | 1.72 | **7.69** | 23.58 | 108.34 | 7.0 / 7.7 / 8.2 |

The ordering matches Level 1. The per-run spread is tight, so this is reproducible.

> One virtual user, deliberately. At 4 and 8 VUs the same configuration produced 14, 88 and
> 314 req/s across three identical runs: with a handful of PHP workers on a laptop, any real
> concurrency saturates them and saturated latency is queueing latency. Publishing that as an engine
> comparison would have been meaningless. Capacity under saturation needs a Linux host and is out of
> scope — see `docs/limitations.md` §9.

### 4.1 Attribution: how much of a request is the engine?

The server measures its own engine time and reports it in `Server-Timing`, so this is not inferred.
80 samples, loopback inside the container.

| engine | request total | engine | everything else | **engine share** |
| --- | --- | --- | --- | --- |
| php | 8.97 ms | 0.018 ms | 8.95 ms | **0.20 %** |
| go-native | 12.51 ms | 0.054 ms | 12.46 ms | **0.43 %** |
| go-http | 32.82 ms | 6.41 ms | 26.41 ms | **19.53 %** |

This is the result the whole repository exists to produce.

> **The scoring logic — the part everyone argues about — is one fifth of one percent of the
> request.** Eighteen microseconds inside nine milliseconds. Everything else is Caddy, the worker,
> Symfony, API Platform, validation and JSON-LD serialisation.
>
> The only way to make the engine visible in a latency budget is to put it on the far side of a
> network. Doing so moves it from 0.2 % to 19.5 % — not by making the work faster, but by adding
> transport around it.

Note also that the same engine costs **15–20× more measured inside the running server** than in the
CLI harness (php 0.9 µs → 18 µs; go-native 2.9 µs → 54 µs; go-http 145 µs → 6.4 ms). Thread
contention under a 24-thread worker pool, and the CPU that `curl` is stealing in the same container.
The *ratios* survive — php : go-native : go-http is 1 : 3.2 : 158 in the CLI and 1 : 3.0 : 356 in
the server — which is why Level 1 is a fair guide to relative cost even though its absolute values
are optimistic.

---

## 5. What these numbers do and do not support

**Supported:**

- All three engines produce identical decisions. That is proven, not measured.
- The native in-process boundary costs ~3 µs; the network boundary costs ~150 µs in isolation and
  several milliseconds inside a real request. They differ by two orders of magnitude.
- For cheap work, in-process PHP is the fastest option available, including faster than native Go.
- Native Go starts to pay somewhere around 5 µs of work per call, and then wins by about 2×.
- Enabling the PHP JIT is worth more (7.3×) than switching language is (a further 2.5×).
- The engine is a rounding error inside a real HTTP request unless you make it remote.

**Not supported by anything here:**

- Any claim about production throughput or capacity.
- Any claim about behaviour under saturation, backpressure or partial failure.
- Any claim that a Go microservice is a bad idea. It is slower *on latency* in this workload, and
  latency is not why anyone extracts a service. Isolation, independent deployment, independent
  scaling and clear ownership are, and none of them are measured here.
- Anything about `amd64`, musl, TLS, a real network, or a PHP version other than 8.5.

**The honest summary:** if you cannot measure the difference through your own API, you should not be
paying for the difference. In this workload, on this hardware, you cannot — and that is the finding,
not a disappointment.

---

## 6. Reproducing

```sh
make start           # build and run everything
make parity-test     # 414 assessments, three identical hashes
make bench           # Level 1 swept + Level 2
./benchmark/attribute.sh   # the engine-share table in §4.1
```

Raw JSON, including the recorded environment for each run, is in `benchmark/results/`.
