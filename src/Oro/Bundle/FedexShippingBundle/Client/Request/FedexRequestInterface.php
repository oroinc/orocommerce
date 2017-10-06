<?php

namespace Oro\Bundle\FedexShippingBundle\Client\Request;

interface FedexRequestInterface
{
    /**
     * @return array
     */
    public function getRequestData(): array;
}
