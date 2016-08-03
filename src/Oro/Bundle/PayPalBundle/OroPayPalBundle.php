<?php

namespace Oro\Bundle\PayPalBundle;

use Symfony\Component\HttpKernel\Bundle\Bundle;

use Oro\Bundle\PayPalBundle\DependencyInjection\OroPayPalExtension;

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
