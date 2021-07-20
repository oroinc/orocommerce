<?php

namespace Oro\Bundle\FedexShippingBundle\Client\Request;

class FedexRequest implements FedexRequestInterface
{
    /**
     * @var array
     */
    private $requestData;

    public function __construct(array $requestData = [])
    {
        $this->requestData = $requestData;
    }

    /**
     * {@inheritDoc}
     */
    public function getRequestData(): array
    {
        return $this->requestData;
    }
}
