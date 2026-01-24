<?php

namespace Oro\Bundle\UPSBundle\TimeInTransit\Request\Builder;

use Oro\Bundle\UPSBundle\Client\Request\UpsClientRequestInterface;

/**
 * Defines the contract for building UPS Time In Transit API requests.
 *
 * Implementations of this interface provide a fluent interface for constructing Time In Transit requests
 * with various parameters such as shipment weight, addresses, and request metadata.
 * The builder pattern allows for flexible request construction
 * while ensuring all required parameters are properly set before creating the final request.
 */
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
