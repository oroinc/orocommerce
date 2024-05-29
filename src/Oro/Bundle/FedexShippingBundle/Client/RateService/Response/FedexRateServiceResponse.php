<?php

namespace Oro\Bundle\FedexShippingBundle\Client\RateService\Response;

use Oro\Bundle\CurrencyBundle\Entity\Price;

/**
 * Response of FedEx Rate Rest API.
 */
class FedexRateServiceResponse implements FedexRateServiceResponseInterface
{
    private int $responseStatusCode;

    /**
     * @var Price[]
     */
    private array $prices;
    private array $errors;

    public function __construct(int $responseStatusCode = 200, array $prices = [], array $errors = [])
    {
        $this->responseStatusCode = $responseStatusCode;
        $this->prices = $prices;
        $this->errors = $errors;
    }

    public function getResponseStatusCode(): int
    {
        return $this->responseStatusCode;
    }

    public function getPrices(): array
    {
        return $this->prices;
    }

    public function isSuccessful(): bool
    {
        return $this->getResponseStatusCode() === 200;
    }

    public function getErrors(): array
    {
        return $this->errors;
    }
}
