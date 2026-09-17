# syntax=docker/dockerfile:1.7

# The remote Go risk engine — Experiment 2.
#
# Worth comparing against build/api.Dockerfile. This image is ~15 MB and builds in seconds; that
# is the genuinely attractive side of a Go service boundary and the talk says so. The cost is not
# here, it is in the round trip and in everything the caller now has to own.

FROM golang:1.26-alpine AS builder

# The repository layout is mirrored inside the image, rather than flattening the module to the
# context root. The contract test and the fixture test locate their inputs with paths relative to
# the package (../../../contract, ../../fixtures), so reproducing the tree means those tests behave
# identically in Docker and on a laptop. Flattening it would require the tests to know they were
# running in a container, which is exactly the kind of environment-dependent test that passes
# everywhere except where it matters.
WORKDIR /src/go

COPY go/go.mod go/go.sum ./
RUN go mod download

COPY go/ ./
COPY contract/ /src/contract/
COPY fixtures/ /src/fixtures/

# The ext module is deliberately absent: it needs CGO and the PHP headers, and this binary must not
# link against libphp.
RUN CGO_ENABLED=0 GOOS=linux go build \
    -trimpath \
    -ldflags="-w -s" \
    -o /out/risk-server \
    ./cmd/risk-server

# Tests run inside the image build, so a broken engine cannot be shipped by a pipeline that happened
# to skip this module. -count=1 because the contract and fixture tests read files that Go's test
# cache does not track, and a cached PASS would be worthless here.
RUN go vet ./... && test -z "$(gofmt -l .)" && go test -count=1 ./...

# ==================================================================================================
FROM alpine:3.24 AS runtime

# curl only, for the health check.
RUN apk add --no-cache curl ca-certificates \
    && adduser -D -u 10001 risk

COPY --from=builder /out/risk-server /usr/local/bin/risk-server

# Unprivileged. A scoring service needs no filesystem and no root, and this is one of the things a
# process boundary genuinely buys: the risk engine cannot be a path to the API's filesystem.
USER risk

ENV RISK_SERVER_ADDR=:8081
EXPOSE 8081

HEALTHCHECK --interval=5s --timeout=3s --start-period=10s --retries=5 \
    CMD curl -fsS http://localhost:8081/healthz || exit 1

ENTRYPOINT ["risk-server"]
