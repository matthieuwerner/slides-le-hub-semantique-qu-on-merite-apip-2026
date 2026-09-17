<?php

declare(strict_types=1);

/*
 * Jane configuration: generates a type-safe PHP client from the internal contract.
 *
 * Run with:  make contract-php   (or: vendor/bin/jane-openapi generate)
 *
 * The generated code is committed. That is a deliberate choice for a repository people will
 * clone from a conference: `git clone && make demo` has to work without a code-generation step,
 * and a reviewer should be able to read what the generator actually produced rather than take
 * a claim about it on trust.
 *
 * The point of generating at all is not convenience, it is *when* a mistake surfaces. A
 * hand-written client that misreads the contract fails in production on the first unusual
 * payload. A generated one plus PHPStan fails in CI, before merge. Moving the failure earlier
 * in time is the only thing this codegen step buys, and it is worth the build dependency.
 */

return [
    'openapi-file' => __DIR__.'/../contract/risk-engine.openapi.yaml',
    'namespace' => 'App\Infrastructure\Generated\RiskEngine',
    'directory' => __DIR__.'/src/Infrastructure/Generated/RiskEngine',

    // Strict types on generated models: the whole reason for generating is to get a type error
    // instead of a null dereference three layers away.
    'strict' => true,
];
