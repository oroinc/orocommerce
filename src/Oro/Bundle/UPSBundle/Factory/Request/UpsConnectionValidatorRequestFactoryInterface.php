<?php

namespace Oro\Bundle\UPSBundle\Factory\Request;

use Oro\Bundle\UPSBundle\Entity\UPSTransport;
use Oro\Bundle\UPSBundle\Request\UpsClientRequestInterface;

interface UpsConnectionValidatorRequestFactoryInterface
{
    /**
     * @param UPSTransport $transport
     *
     * @return UpsClientRequestInterface
     */
    public function createByTransport(UPSTransport $transport);
}
