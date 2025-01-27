<?php

namespace Oro\Bundle\CheckoutBundle\Api\Model;

/**
 * Represents a response for the sub-resource that validates whether checkout is ready to payment.
 */
final class CheckoutPaymentResponse
{
    private array $errors = [];

    public function __construct(
        private readonly string $message,
        private readonly ?string $paymentUrl = null
    ) {
    }

    public function getMessage(): string
    {
        return $this->message;
    }

    public function getPaymentUrl(): ?string
    {
        return $this->paymentUrl;
    }

    public function getErrors(): array
    {
        return $this->errors;
    }

    public function setErrors(array $errors): void
    {
        $this->errors = $errors;
    }
}
