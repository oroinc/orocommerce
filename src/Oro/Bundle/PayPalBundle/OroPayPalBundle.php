<?php

namespace Oro\Bundle\PayPalBundle;

use Oro\Bundle\PayPalBundle\DependencyInjection\OroPayPalExtension;
use Symfony\Component\DependencyInjection\Extension\ExtensionInterface;
use Symfony\Component\HttpKernel\Bundle\Bundle;

class OroPayPalBundle extends Bundle
{
    /**
     * {@inheritdoc}
     */
    public function getContainerExtension(): ?ExtensionInterface
    {
        if (null === $this->extension) {
            $this->extension = new OroPayPalExtension();
        }

        return $this->extension;
    }
}
