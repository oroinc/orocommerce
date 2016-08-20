<?php

namespace Oro\Bundle\PricingBundle;

use Symfony\Component\HttpKernel\Bundle\Bundle;
use Symfony\Component\DependencyInjection\ContainerBuilder;

use Oro\Bundle\PricingBundle\DependencyInjection\OroPricingExtension;
use Oro\Bundle\PricingBundle\DependencyInjection\CompilerPass\SubtotalProviderPass;

class OroPricingBundle extends Bundle
{
    /**
     * {@inheritdoc}
     */
    public function getContainerExtension()
    {
        if (!$this->extension) {
            $this->extension = new OroPricingExtension();
        }

        return $this->extension;
    }

    /**
     * {@inheritdoc}
     */
    public function build(ContainerBuilder $container)
    {
        parent::build($container);

        $container->addCompilerPass(new SubtotalProviderPass());
    }
}
