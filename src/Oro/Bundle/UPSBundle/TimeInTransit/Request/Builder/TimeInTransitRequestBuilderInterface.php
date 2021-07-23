<?php

namespace Oro\Bundle\UPSBundle\TimeInTransit\Request\Builder;

use Oro\Bundle\UPSBundle\Client\Request\UpsClientRequestInterface;

interface TimeInTransitRequestBuilderInterface
{
    public function createRequest(): UpsClientRequestInterface;

    /**
     * @param int $weight
     * @param string $weightUnitCode
     *
     * @return $this
     */
    public function setWeight(int $weight, string $weightUnitCode);

    /**
     * @param string $maximumListSize
     *
     * @return $this
     */
    public function setMaximumListSize(string $maximumListSize);

    /**
     * @param string $transactionIdentifier
     *
     * @return $this
     */
    public function setTransactionIdentifier(string $transactionIdentifier);

    /**
     * @param string $customerContext
     *
     * @return $this
     */
    public function setCustomerContext(string $customerContext);
}
