<?php

declare(strict_types=1);

namespace App\Engine\Http;

use App\Domain\AuthorizationStatus;
use App\Domain\DecisionReason;
use App\Domain\RiskAssessment;
use App\Domain\RiskEngineUnavailable;
use App\Domain\RiskInput;
use App\Domain\RiskProfile;
use App\Infrastructure\Generated\RiskEngine\Model\AssessRequest;
use App\Infrastructure\Generated\RiskEngine\Model\AssessResponse;
use App\Infrastructure\Generated\RiskEngine\Model\AssessResponseV2;

/**
 * Explicit conversion of private risk scales, enums and value objects.
 * JoliCode AutoMapper handles the separate scalar MerchantRiskProfile projection.
 * Generated client types do not establish business compatibility by themselves.
 */
final class WireMapper
{
    public function toDomainV2(AssessResponseV2 $response): RiskAssessment
    {
        $scaled = $response->getScore() * 100;
        if (!is_finite($scaled) || $scaled < 0 || $scaled > 100 || abs($scaled - round($scaled)) > 1e-7) {
            throw RiskEngineUnavailable::malformedResponse('score must be on the 0.01 grid within 0..1; no lossy rounding');
        }
        $status = match ($response->getOutcome()) {
            'ALLOW' => 'approved', 'REVIEW' => 'challenged', 'DENY' => 'declined',
            default => throw RiskEngineUnavailable::malformedResponse('unknown v2 outcome'),
        };
        // Keep explicit domain conversion. AutoMapper is used for the merchant projection.
        return $this->toDomain((new AssessResponse())->setRiskScore((int) round($scaled))
            ->setStatus($status)->setDecisionReason($response->getReason()));
    }

    public function toWire(RiskInput $input, RiskProfile $profile): AssessRequest
    {
        // Note the fluent setters: Jane generates mutable models with setters returning $this.
        // Not the style used anywhere else in this codebase, and a fair illustration of the
        // trade-off of generated code — you inherit the generator's idioms at the seam.
        return (new AssessRequest())
            ->setAmountMinor($input->amount->minorUnits)
            ->setCurrency($input->amount->currency->code)
            ->setCountry($input->country)
            ->setCardBin($input->cardBin->digits)
            ->setMerchantId($input->merchantId)
            ->setDeviceId($input->deviceId)
            ->setBinCountry($input->binCountry)
            ->setDeviceTxCount24h($input->deviceTxCount24h)
            ->setMerchantRiskTier($input->merchantRiskTier)
            ->setProfile($profile->value);
    }

    /**
     * @throws RiskEngineUnavailable when the response cannot be understood as a decision
     */
    public function toDomain(AssessResponse $response): RiskAssessment
    {
        $status = AuthorizationStatus::tryFrom($response->getStatus());
        if (null === $status) {
            // A status we do not recognise is not something to coerce into a default. Defaulting
            // to "approved" would approve payments on a deployment mismatch; defaulting to
            // "declined" would decline real traffic. Failing is the only safe option.
            throw RiskEngineUnavailable::malformedResponse(\sprintf(
                'unknown status "%s"', $response->getStatus(),
            ));
        }

        $reason = DecisionReason::tryFrom($response->getDecisionReason());
        if (null === $reason) {
            throw RiskEngineUnavailable::malformedResponse(\sprintf(
                'unknown decisionReason "%s"', $response->getDecisionReason(),
            ));
        }

        // RiskAssessment's constructor range-checks the score, so a peer returning 7000 is
        // caught here rather than surfacing as a nonsensical decision downstream.
        try {
            return new RiskAssessment($response->getRiskScore(), $status, $reason);
        } catch (\InvalidArgumentException $e) {
            throw RiskEngineUnavailable::malformedResponse($e->getMessage());
        }
    }
}
