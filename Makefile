# Boundary Lab
#
# Everything in this file is meant to work from a clean checkout with nothing installed but Docker.
# If a target needs a local PHP or Go toolchain, it says so.

SHELL := /bin/bash
.DEFAULT_GOAL := help
.SHELLFLAGS := -eu -o pipefail -c

API_PORT ?= 8099
RISK_ENGINE_PORT ?= 8098
API := http://localhost:$(API_PORT)
ENDPOINT := $(API)/api/payment-authorizations

COMPOSE := docker compose
DC_RUN := $(COMPOSE) run --rm --no-deps

# The FrankenPHP builder image, used for anything needing PHP-ZTS or CGO.
BUILDER := dunglas/frankenphp:1.12.7-builder-php8.5

# A throwaway PHP container for host-independent PHP tasks.
PHP_IN_DOCKER := docker run --rm -e APP_ENV=test -v "$(CURDIR)":/work -w /work/api boundary-lab/api:latest

# Pure-Go work runs in the official Go image, NOT in the FrankenPHP builder.
#
# The builder image is for the extension, where CGO and the PHP headers are mandatory. Using it for
# ordinary tests means every run compiles a large part of the standard library through the C
# toolchain: measured at over 25 minutes here, against seconds in the plain Go image.
#
# Two cache volumes, not one. bl-gocache is the module cache; bl-gobuild is the *build* cache.
# Without the second, each run recompiles from scratch even when nothing changed.
GO_IMAGE := golang:1.26-alpine
GO_IN_DOCKER := docker run --rm \
	-v "$(CURDIR)":/work \
	-v bl-gocache:/go/pkg/mod \
	-v bl-gobuild:/root/.cache/go-build \
	-w /work/go \
	-e CGO_ENABLED=0 \
	$(GO_IMAGE)

BOLD := \033[1m
DIM := \033[2m
GREEN := \033[32m
YELLOW := \033[33m
CYAN := \033[36m
RESET := \033[0m

# ==================================================================================================
## Help
# ==================================================================================================

.PHONY: help
help: ## Show this help
	@printf "$(BOLD)Boundary Lab$(RESET) — API Platform as a stable boundary over three runtimes\n\n"
	@printf "  $(BOLD)Quick start:$(RESET) make start && make demo\n\n"
	@awk 'BEGIN {FS = ":.*?## "} \
		/^## / { printf "\n$(BOLD)%s$(RESET)\n", substr($$0, 4); next } \
		/^[a-zA-Z0-9_-]+:.*?## / { printf "  $(CYAN)%-22s$(RESET) %s\n", $$1, $$2 }' $(MAKEFILE_LIST)
	@printf "\n"

# ==================================================================================================
## Lifecycle
# ==================================================================================================

.PHONY: build
build: ## Build both images (compiles FrankenPHP with the Go extension; slow the first time)
	@printf "$(YELLOW)Building. The API image compiles a FrankenPHP binary with the Go extension —\n"
	@printf "expect several minutes on a cold cache.$(RESET)\n"
	$(COMPOSE) build

.PHONY: start
start: ## Build if needed and start the stack, waiting until it is actually ready
	$(COMPOSE) up -d --build --wait
	@$(MAKE) --no-print-directory engines

.PHONY: stop
stop: ## Stop the stack
	$(COMPOSE) down --remove-orphans

.PHONY: restart
restart: stop start ## Restart the stack

.PHONY: logs
logs: ## Follow logs from both services
	$(COMPOSE) logs -f

.PHONY: shell
shell: ## Open a shell in the API container
	$(COMPOSE) exec api bash

.PHONY: engines
engines: ## Show which engines this deployment can serve, and prove what the native one is
	@printf "\n$(BOLD)Engine availability$(RESET)\n"
	@curl -fsS $(API)/_lab/engines | jq .

# ==================================================================================================
## The three demos
# ==================================================================================================

.PHONY: demo
demo: demo-php demo-php-http demo-go-http demo-go-native ## Run the three acts, with PHP HTTP before Go HTTP back to back
	@printf "\n$(GREEN)$(BOLD)Same endpoint. Same contract. Same effects. Four configurations.$(RESET)\n"
	@printf "$(DIM)The only thing that changed is which side of which boundary the work happened on.$(RESET)\n\n"

.PHONY: demo-php
demo-php: ## Demo 1 — scoring in PHP, in this process
	@./benchmark/demo.sh php

.PHONY: demo-php-http
demo-php-http: ## Demo 2a — extract the PHP engine behind the private HTTP contract
	@./benchmark/demo.sh php-http

.PHONY: demo-go-http
demo-go-http: ## Demo 2 — scoring in a Go service, over HTTP
	@./benchmark/demo.sh go-http

.PHONY: demo-go-native
demo-go-native: ## Demo 3 — scoring in a Go function, inside the PHP process
	@./benchmark/demo.sh go-native

.PHONY: openapi
openapi: ## Print the public OpenAPI document API Platform generates
	@curl -fsS -H 'Accept: application/vnd.openapi+json' $(API)/api/docs.jsonopenapi | jq .

.PHONY: jsonld
jsonld: ## Print the Hydra/JSON-LD entrypoint documentation
	@curl -fsS -H 'Accept: application/ld+json' $(API)/api/docs.jsonld | jq .

# ==================================================================================================
## Correctness
# ==================================================================================================

.PHONY: test
test: test-php test-go ## Run every test suite

.PHONY: test-php
test-php: ## PHPUnit: unit, functional and public-contract tests
	$(PHP_IN_DOCKER) php bin/phpunit

.PHONY: test-go
test-go: ## Go tests, vet and formatting for the scoring core and HTTP adapter
	@# sh, not bash: the Go image is Alpine-based and has no bash.
	@$(GO_IN_DOCKER) sh -c 'test -z "$$(gofmt -l .)" || { echo "gofmt needed on:"; gofmt -l .; exit 1; }'
	$(GO_IN_DOCKER) go vet ./...
	@# -count=1 because the contract and fixture tests read files Go's test cache does not track,
	@# and those are exactly the tests that catch drift against the committed contract.
	$(GO_IN_DOCKER) go test -count=1 ./...

.PHONY: parity-test
parity-test: ## Prove all available engines produce identical decisions for every fixture
	@./benchmark/parity.sh

.PHONY: stan
stan: ## PHPStan at max level, including the Go-generated extension stub
	$(PHP_IN_DOCKER) php bin/console cache:warmup --env=dev
	$(PHP_IN_DOCKER) vendor/bin/phpstan analyse --memory-limit=1G

.PHONY: check
check: test-go test-php stan parity-test ## Everything CI runs

# ==================================================================================================
## The contract between PHP and Go
# ==================================================================================================

.PHONY: contract-php
contract-php: ## Regenerate the type-safe PHP client from contract/risk-engine.openapi.yaml
	$(PHP_IN_DOCKER) vendor/bin/jane-openapi generate --config-file=jane-configuration.php
	@printf "$(GREEN)Generated. Now run 'make stan' to type-check the call sites against it.$(RESET)\n"

.PHONY: contract-break
contract-break: ## Demo — change the contract and watch BOTH languages refuse to build
	@./benchmark/contract-break.sh

.PHONY: check-stub
check-stub: ## Verify the committed PHP stub still matches what the Go generator produces
	@if [ ! -f go/ext/risk.stub.php ]; then \
		printf '$(YELLOW)go/ext/risk.stub.php is missing. Run "make build" to generate it.$(RESET)\n'; \
		exit 1; \
	fi
	@if diff -u api/stubs/native-risk.stub.php go/ext/risk.stub.php; then \
		printf '$(GREEN)The committed stub matches the Go source.$(RESET)\n'; \
	else \
		printf '$(YELLOW)Stale. Copy go/ext/risk.stub.php over api/stubs/native-risk.stub.php.$(RESET)\n'; \
		exit 1; \
	fi

.PHONY: fixtures
fixtures: ## Regenerate fixtures/risk-cases.json from the case corpus
	$(COMPOSE) exec -T api frankenphp php-cli bin/console lab:fixtures:generate
	@printf '$(DIM)Now run "make test-go" — the Go core independently verifies every value.$(RESET)\n'

# ==================================================================================================
## Measurement
# ==================================================================================================

.PHONY: bench
bench: parity-test ## Validated sequential comparison, raw samples and rotating run order (not capacity)
	@bash ./benchmark/compare.sh bench

.PHONY: proof-cycle
proof-cycle: ## Verify persisted lifecycle and concurrent commands, without benchmarking
	@node benchmark/card-cycle.mjs all

.PHONY: proof-public
proof-public: ## Verify public responses, exports, Provider and private-v2 compatibility
	@bash ./benchmark/compare.sh proof

.PHONY: demo-consumer
demo-consumer: ## Prepared tool consumer of the same public API, not a fourth engine
	@bash ./benchmark/demo.sh consumer

.PHONY: bench-micro
bench-micro: ## Level 1 only — the cost of each boundary crossing, no HTTP
	@./benchmark/micro.sh

.PHONY: bench-macro
bench-macro: ## Level 2 only — end-to-end latency through API Platform, using k6
	@./benchmark/macro.sh

.PHONY: bench-attribute
bench-attribute: ## How much of a request is actually the engine? (reads Server-Timing)
	@./benchmark/attribute.sh

# ==================================================================================================
## Slides
# ==================================================================================================

.PHONY: slides
slides: ## Present the deck locally
	cd slides && npm install && npm run dev

.PHONY: slides-build
slides-build: ## Export the deck to static HTML and PDF
	cd slides && npm install && npm run build && npm run export

# ==================================================================================================
## Housekeeping
# ==================================================================================================

.PHONY: clean
clean: ## Remove containers, build artifacts and generated glue
	$(COMPOSE) down --remove-orphans
	rm -f build/frankenphp/frankenphp
	rm -f go/ext/risk.c go/ext/risk.h go/ext/risk_arginfo.h go/ext/risk_generated.go
	rm -rf api/var/cache/* api/var/log/*
	rm -rf benchmark/results/tmp

.PHONY: fmt
fmt: ## Format Go and PHP
	$(GO_IN_DOCKER) gofmt -w .
	$(PHP_IN_DOCKER) vendor/bin/php-cs-fixer fix || true
