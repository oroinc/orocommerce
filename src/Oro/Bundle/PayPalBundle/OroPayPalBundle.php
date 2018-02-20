<?php

namespace Oro\Bundle\PayPalBundle;

use Oro\Bundle\PayPalBundle\DependencyInjection\OroPayPalExtension;
use Symfony\Component\HttpKernel\Bundle\Bundle;

class OroPayPalBundle extends Bundle
{
    /**
     * {@inheritdoc}
     */
    public function getContainerExtension()
    {
        if (!$this->extension) {
            $this->extension = new OroPayPalExtension();
        }

        return $this->extension;
    }
}
