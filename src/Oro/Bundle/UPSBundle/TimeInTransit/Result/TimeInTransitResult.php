<?php

namespace Oro\Bundle\UPSBundle\TimeInTransit\Result;

use Symfony\Component\HttpFoundation\ParameterBag;

/**
 * Base implementation of UPS TimeInTransit result
 */
class TimeInTransitResult extends ParameterBag implements TimeInTransitResultInterface
{
    public const STATUS_KEY = 'status';
    public const STATUS_DESCRIPTION_KEY = 'status_description';
    public const ESTIMATED_ARRIVALS_KEY = 'estimated_arrivals';
    public const AUTO_DUTY_CODE_KEY = 'auto_duty_code';
    public const CUSTOMER_CONTEXT_KEY = 'customer_context';
    public const TRANSACTION_IDENTIFIER_KEY = 'transaction_identifier';

    #[\Override]
    public function getEstimatedArrivals()
    {
        return $this->has(self::ESTIMATED_ARRIVALS_KEY) ? (array)$this->get(self::ESTIMATED_ARRIVALS_KEY) : [];
    }

    #[\Override]
    public function getEstimatedArrivalByService($serviceCode)
    {
        if (!array_key_exists($serviceCode, $this->getEstimatedArrivals())) {
            return null;
        }

        return $this->getEstimatedArrivals()[$serviceCode];
    }

    /**
     * @return string
     */
    #[\Override]
    public function getAutoDutyCode()
    {
        return $this->has(self::AUTO_DUTY_CODE_KEY) ? (string)$this->get(self::AUTO_DUTY_CODE_KEY) : null;
    }

    #[\Override]
    public function getStatus()
    {
        return (bool)$this->get(self::STATUS_KEY);
    }

    #[\Override]
    public function getStatusDescription()
    {
        return (string)$this->get(self::STATUS_DESCRIPTION_KEY);
    }

    #[\Override]
    public function getCustomerContext()
    {
        return $this->has(self::CUSTOMER_CONTEXT_KEY) ? (string)$this->get(self::CUSTOMER_CONTEXT_KEY) : null;
    }

    #[\Override]
    public function getTransactionIdentifier()
    {
        return $this->has(self::TRANSACTION_IDENTIFIER_KEY)
            ? (string)$this->get(self::TRANSACTION_IDENTIFIER_KEY)
            : null;
    }
}
