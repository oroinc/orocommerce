<?php

namespace Oro\Bundle\ValidationBundle;

use Oro\Bundle\ValidationBundle\DependencyInjection\OroValidationExtension;
use Symfony\Component\HttpKernel\Bundle\Bundle;

class OroValidationBundle extends Bundle
{
    /** {@inheritdoc} */
    public function getContainerExtension()
    {
        return new OroValidationExtension();
    }
}
