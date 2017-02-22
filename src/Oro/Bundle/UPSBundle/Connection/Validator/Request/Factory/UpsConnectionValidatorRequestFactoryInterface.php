<?php

namespace Oro\Bundle\UPSBundle\Connection\Validator\Request\Factory;

use Oro\Bundle\UPSBundle\Client\Request\UpsClientRequestInterface;
use Oro\Bundle\UPSBundle\Entity\UPSTransport;

interface UpsConnectionValidatorRequestFactoryInterface
{
    /**
     * @param UPSTransport $transport
     *
     * @return UpsClientRequestInterface
     */
    public function createByTransport(UPSTransport $transport);
}
