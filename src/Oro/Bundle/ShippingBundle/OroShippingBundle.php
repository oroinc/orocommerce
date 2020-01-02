<?php

namespace Oro\Bundle\ShippingBundle;

use Oro\Bundle\ShippingBundle\DependencyInjection\OroShippingExtension;
use Symfony\Component\HttpKernel\Bundle\Bundle;

/**
 * The ShippingBundle bundle class.
 */
class OroShippingBundle extends Bundle
{
    /**
     * {@inheritdoc}
     */
    public function getContainerExtension()
    {
        if (!$this->extension) {
            $this->extension = new OroShippingExtension();
        }

        return $this->extension;
    }
}
