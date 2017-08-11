<?php

namespace Oro\Bundle\FedexShippingBundle\Client\RateService\Response;

class FedexRateServiceResponse implements FedexRateServiceResponseInterface
{
    const SEVERITY_SUCCESS = 'SUCCESS';
    const SEVERITY_WARNING = 'WARNING';
    const SEVERITY_ERROR = 'ERROR';
    const SEVERITY_FAILURE = 'FAILURE';

    /**
     * @var string
     */
    protected $severityCode;

    /**
     * @var string
     */
    protected $severityMessage;

    /**
     * @var array
     */
    protected $prices;

    /**
     * @param string $severityCode
     * @param string $severityMessage
     * @param array  $prices
     */
    public function __construct(string $severityCode, string $severityMessage, array $prices = [])
    {
        $this->severityCode = $severityCode;
        $this->severityMessage = $severityMessage;
        $this->prices = $prices;
    }

    /**
     * {@inheritDoc}
     */
    public function getSeverityCode(): string
    {
        return $this->severityCode;
    }

    /**
     * {@inheritDoc}
     */
    public function getSeverityMessage(): string
    {
        return $this->severityMessage;
    }

    /**
     * {@inheritDoc}
     */
    public function getPrices(): array
    {
        return $this->prices;
    }
}
