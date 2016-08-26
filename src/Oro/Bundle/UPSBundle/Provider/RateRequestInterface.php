<?php

namespace Oro\Bundle\UPSBundle\Provider;

interface RateRequestInterface
{
    public function setRequest();

    public function setShipment();

    public function toArray();
}
