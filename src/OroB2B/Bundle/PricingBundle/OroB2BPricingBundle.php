<?php

namespace OroB2B\Bundle\PricingBundle;

use Symfony\Component\HttpKernel\Bundle\Bundle;

use OroB2B\Bundle\PricingBundle\DependencyInjection\OroB2BPricingExtension;

class OroB2BPricingBundle extends Bundle
{
    /**
     * {@inheritdoc}
     */
    public function getContainerExtension()
    {
        if (!$this->extension) {
            $this->extension = new OroB2BPricingExtension();
        }

        return $this->extension;
    }
}
