<?php

namespace Oro\Bundle\UPSBundle\Connection\Validator;

use Oro\Bundle\UPSBundle\Connection\Validator\Result\UpsConnectionValidatorResultInterface;
use Oro\Bundle\UPSBundle\Entity\UPSTransport;

interface UpsConnectionValidatorInterface
{
    /**
     * @param UPSTransport $transport
     *
     * @return UpsConnectionValidatorResultInterface
     */
    public function validateConnectionByUpsSettings(UPSTransport $transport);
}
