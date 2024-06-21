<?php

namespace Oro\Bundle\FedexShippingBundle\Client\RateService\Response;

use Oro\Bundle\CurrencyBundle\Entity\Price;

/**
 * Response of FedEx Rate SOAP API.
 * @deprecated. Will be removed when SOAP support will be dropped by FedEx.
 */
class FedexRateServiceSoapResponse implements FedexRateServiceResponseInterface
{
    const SEVERITY_SUCCESS = 'SUCCESS';
    const SEVERITY_NOTE = 'NOTE';
    const SEVERITY_WARNING = 'WARNING';
    const SEVERITY_ERROR = 'ERROR';
    const SEVERITY_FAILURE = 'FAILURE';

    const CONNECTION_ERROR = 111;
    const NO_SERVICES_ERROR = 556;
    const AUTHORIZATION_ERROR = 1000;
    const SERVICE_NOT_ALLOWED = 868;

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

    public function getPrices(): array
    {
        return $this->prices;
    }

    public function isSuccessful(): bool
    {
        return $this->getSeverityType() === self::SEVERITY_SUCCESS || $this->getSeverityType() === self::SEVERITY_NOTE;
    }

    public function getResponseStatusCode(): int
    {
        return $this->severityCode;
    }

    public function getErrors(): array
    {
        return [$this->severityType];
    }
}
