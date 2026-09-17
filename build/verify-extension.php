<?php

declare(strict_types=1);

/*
 * Build-time gate: fail the image build if the Go extension is not really there.
 *
 * Without this the image builds happily, the demo starts happily, and the failure surfaces as a
 * 503 on stage. A build that cannot produce a working \BoundaryLab\Native\assess() has no reason
 * to succeed.
 */

$failures = [];

if (!\extension_loaded('risk')) {
    $failures[] = 'the "risk" extension is not loaded';
}

foreach (['BoundaryLab\Native\assess', 'BoundaryLab\Native\engine_info'] as $function) {
    if (!\function_exists($function)) {
        $failures[] = \sprintf('%s() is not defined', $function);
    }
}

if ([] === $failures) {
    /** @var array<string, mixed> $info */
    $info = \BoundaryLab\Native\engine_info();

    // The reference payload, asserted identically by the PHP unit tests, the Go unit tests and
    // the Go HTTP tests. If the extension is present but disagrees, that is worse than absent.
    /** @var array<string, mixed> $verdict */
    $verdict = \BoundaryLab\Native\assess([
        'amountMinor' => 42069,
        'currency' => 'EUR',
        'country' => 'FR',
        'cardBin' => '497010',
        'merchantId' => 'merchant_42',
        'deviceId' => 'device_123',
        'binCountry' => 'FR',
        'deviceTxCount24h' => 1,
        'merchantRiskTier' => 0,
        'profile' => 'RULES',
    ]);

    if (12 !== ($verdict['riskScore'] ?? null)
        || 'approved' !== ($verdict['status'] ?? null)
        || 'HIGH_AMOUNT' !== ($verdict['decisionReason'] ?? null)
    ) {
        $failures[] = \sprintf(
            'the native engine disagrees with the specification: %s',
            json_encode($verdict, \JSON_THROW_ON_ERROR),
        );
    }

    if ([] === $failures) {
        printf(
            "native engine OK — %s %s/%s, %d trees, verdict %d/%s\n",
            $info['goVersion'] ?? '?',
            $info['goos'] ?? '?',
            $info['goarch'] ?? '?',
            $info['treeCount'] ?? 0,
            $verdict['riskScore'],
            $verdict['status'],
        );
        exit(0);
    }
}

fwrite(\STDERR, "native engine verification FAILED:\n");
foreach ($failures as $failure) {
    fwrite(\STDERR, '  - '.$failure."\n");
}

exit(1);
