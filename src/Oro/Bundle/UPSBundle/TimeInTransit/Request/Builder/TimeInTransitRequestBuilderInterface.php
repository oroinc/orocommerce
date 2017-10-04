<?php

namespace Oro\Bundle\UPSBundle\TimeInTransit\Request\Builder;

use Oro\Bundle\UPSBundle\Client\Request\UpsClientRequestInterface;

interface TimeInTransitRequestBuilderInterface
{
    /**
     * @return UpsClientRequestInterface
     */
    public function createRequest();

    /**
     * @param string $weight
     *
     * @return $this
     */
    public function setWeight($weight);

    /**
     * @param string $weightUnitCode
     *
     * @return $this
     */
    public function setWeightUnitCode($weightUnitCode);

    /**
     * @param string $maximumListSize
     *
     * @return $this
     */
    public function setMaximumListSize($maximumListSize);

    /**
     * @param string $transactionIdentifier
     *
     * @return $this
     */
    public function setTransactionIdentifier($transactionIdentifier);

    /**
     * @param string $customerContext
     *
     * @return $this
     */
    public function setCustomerContext($customerContext);
}
