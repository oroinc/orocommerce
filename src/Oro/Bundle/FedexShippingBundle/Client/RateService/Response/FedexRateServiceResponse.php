<?php

namespace Oro\Bundle\FedexShippingBundle\Client\RateService\Response;

use Oro\Bundle\CurrencyBundle\Entity\Price;

class FedexRateServiceResponse implements FedexRateServiceResponseInterface
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
     * @var Price|null
     */
    protected $price;

    /**
     * @param string     $severityType
     * @param int        $severityCode
     * @param Price|null $price
     */
    public function __construct(string $severityType, int $severityCode, Price $price = null)
    {
        $this->severityType = $severityType;
        $this->severityCode = $severityCode;
        $this->price = $price;
    }

    /**
     * {@inheritDoc}
     */
    public function getSeverityType(): string
    {
        return $this->severityType;
    }

    /**
     * {@inheritDoc}
     */
    public function getSeverityCode(): int
    {
        return $this->severityCode;
    }

    /**
     * {@inheritDoc}
     */
    public function getPrice()
    {
        return $this->price;
    }

    /**
     * {@inheritDoc}
     */
    public function isSuccessful(): bool
    {
        return $this->getSeverityType() === self::SEVERITY_SUCCESS || $this->getSeverityType() === self::SEVERITY_NOTE;
    }
}
