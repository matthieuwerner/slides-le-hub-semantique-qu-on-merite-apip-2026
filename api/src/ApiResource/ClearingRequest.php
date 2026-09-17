<?php
declare(strict_types=1);
namespace App\ApiResource;
use Symfony\Component\Validator\Constraints as Assert;

final class ClearingRequest
{
    #[Assert\NotBlank]
    #[Assert\Regex('/^account_[a-zA-Z0-9_-]{1,64}\.[a-f0-9]{64}$/D')]
    public string $authorizationId='';
    #[Assert\NotBlank]
    #[Assert\Regex('/^[a-zA-Z0-9_-]{8,80}$/D')]
    public string $requestId='';
}
