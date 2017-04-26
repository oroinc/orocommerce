<?php

namespace Oro\Bundle\ApruveBundle\Connection\Validator;

use Oro\Bundle\ApruveBundle\Connection\Validator\Result\ApruveConnectionValidatorResultInterface;
use Oro\Bundle\ApruveBundle\Entity\ApruveSettings;

interface ApruveConnectionValidatorInterface
{
    /**
     * @param ApruveSettings $settings
     *
     * @return ApruveConnectionValidatorResultInterface
     */
    public function validateConnectionByApruveSettings(ApruveSettings $settings);
}
