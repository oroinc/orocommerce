<?php

namespace Oro\Bundle\UPSBundle\Connection\Validator;

use Oro\Bundle\UPSBundle\Connection\Validator\Result\UpsConnectionValidatorResultInterface;
use Oro\Bundle\UPSBundle\Entity\UPSTransport;

/**
 * Interface for UPS Connection Validator
 */
interface UpsConnectionValidatorInterface
{
    public function validateConnectionByUpsSettings(UPSTransport $transport): UpsConnectionValidatorResultInterface;
}
