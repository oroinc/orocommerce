<?php

namespace Oro\Bundle\UPSBundle\TimeInTransit\Result;

use Symfony\Component\HttpFoundation\ParameterBag;

class TimeInTransitResult extends ParameterBag implements TimeInTransitResultInterface
{
    const STATUS_KEY = 'status';
    const STATUS_DESCRIPTION_KEY = 'status_description';
    const ESTIMATED_ARRIVALS_KEY = 'estimated_arrivals';
    const AUTO_DUTY_CODE_KEY = 'auto_duty_code';
    const CUSTOMER_CONTEXT_KEY = 'customer_context';
    const TRANSACTION_IDENTIFIER_KEY = 'transaction_identifier';

    /**
     * {@inheritDoc}
     */
    public function getEstimatedArrivals()
    {
        return $this->has(self::ESTIMATED_ARRIVALS_KEY) ? (array)$this->get(self::ESTIMATED_ARRIVALS_KEY) : [];
    }

    /**
     * {@inheritDoc}
     */
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
    public function getAutoDutyCode()
    {
        return $this->has(self::AUTO_DUTY_CODE_KEY) ? (string)$this->get(self::AUTO_DUTY_CODE_KEY) : null;
    }

    /**
     * {@inheritDoc}
     */
    public function getStatus()
    {
        return (bool)$this->get(self::STATUS_KEY);
    }

    /**
     * {@inheritDoc}
     */
    public function getStatusDescription()
    {
        return (string)$this->get(self::STATUS_DESCRIPTION_KEY);
    }

    /**
     * {@inheritDoc}
     */
    public function getCustomerContext()
    {
        return $this->has(self::CUSTOMER_CONTEXT_KEY) ? (string)$this->get(self::CUSTOMER_CONTEXT_KEY) : null;
    }

    /**
     * {@inheritDoc}
     */
    public function getTransactionIdentifier()
    {
        return $this->has(self::TRANSACTION_IDENTIFIER_KEY)
            ? (string)$this->get(self::TRANSACTION_IDENTIFIER_KEY)
            : null;
    }
}
