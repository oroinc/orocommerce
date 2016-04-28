<?php

namespace OroB2B\Bundle\ShippingBundle;

use Symfony\Component\HttpKernel\Bundle\Bundle;

use OroB2B\Bundle\ShippingBundle\DependencyInjection\OroB2BShippingExtension;

class OroB2BShippingBundle extends Bundle
{
    /**
     * {@inheritdoc}
     */
    public function getContainerExtension()
    {
        if (!$this->extension) {
            $this->extension = new OroB2BShippingExtension();
        }

        return $this->extension;
    }
}
