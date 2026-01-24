<?php

namespace Oro\Bundle\UPSBundle\Connection\Validator\Request\Factory;

use Oro\Bundle\UPSBundle\Client\Request\UpsClientRequestInterface;
use Oro\Bundle\UPSBundle\Entity\UPSTransport;

/**
 * Defines the contract for factories that create UPS connection validation requests.
 *
 * Implementations of this interface are responsible for creating properly configured {@see UpsClientRequestInterface}
 * instances that can be used to validate UPS API connectivity and credentials based on the provided transport settings.
 */
interface UpsConnectionValidatorRequestFactoryInterface
{
    /**
     * @param UPSTransport $transport
     *
     * @return UpsClientRequestInterface
     */
    public function createByTransport(UPSTransport $transport);
}
