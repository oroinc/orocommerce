<?php

namespace Oro\Bundle\FedexShippingBundle\Client\RateService\Response;

use Oro\Bundle\CurrencyBundle\Entity\Price;

/**
 * Response of FedEx Rate SOAP API.
 *
 * phpcs:ignore
 * @deprecated. Will be removed when SOAP support will be dropped by FedEx.
 */
class FedexRateServiceSoapResponse implements FedexRateServiceResponseInterface
{
    public const SEVERITY_SUCCESS = 'SUCCESS';
    public const SEVERITY_NOTE = 'NOTE';
    public const SEVERITY_WARNING = 'WARNING';
    public const SEVERITY_ERROR = 'ERROR';
    public const SEVERITY_FAILURE = 'FAILURE';

    public const CONNECTION_ERROR = 111;
    public const NO_SERVICES_ERROR = 556;
    public const AUTHORIZATION_ERROR = 1000;
    public const SERVICE_NOT_ALLOWED = 868;

    /**
     * @var string
     */
    protected $severityType;

    /**
     * @var int
     */
    protected $severityCode;

    /**
     * @var Price[]
     */
    protected $prices;

    public function __construct(string $severityType, int $severityCode, array $prices = [])
    {
        $this->severityType = $severityType;
        $this->severityCode = $severityCode;
        $this->prices = $prices;
    }

    public function getSeverityType(): string
    {
        return $this->severityType;
    }

    public function getSeverityCode(): int
    {
        return $this->severityCode;
    }

    #[\Override]
    public function getPrices(): array
    {
        return $this->prices;
    }

    #[\Override]
    public function isSuccessful(): bool
    {
        return $this->getSeverityType() === self::SEVERITY_SUCCESS || $this->getSeverityType() === self::SEVERITY_NOTE;
    }

    #[\Override]
    public function getResponseStatusCode(): int
    {
        return $this->severityCode;
    }

    #[\Override]
    public function getErrors(): array
    {
        return [$this->severityType];
    }
}
