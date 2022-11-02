<?php

namespace Oro\Bundle\FedexShippingBundle\Client\Request;

interface FedexRequestInterface
{
    public function getRequestData(): array;
}
