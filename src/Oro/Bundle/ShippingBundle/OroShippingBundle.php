<?php

namespace Oro\Bundle\ShippingBundle;

use Symfony\Component\DependencyInjection\Compiler\PassConfig;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\HttpKernel\Bundle\Bundle;

use Oro\Bundle\ShippingBundle\DependencyInjection\CompilerPass\FreightClassExtensionPass;
use Oro\Bundle\ShippingBundle\DependencyInjection\CompilerPass\ShippingMethodsCompilerPass;
use Oro\Bundle\ShippingBundle\DependencyInjection\OroShippingExtension;

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

    /**
     * {@inheritdoc}
     */
    public function build(ContainerBuilder $container)
    {
        parent::build($container);

        $container
            ->addCompilerPass(new FreightClassExtensionPass(), PassConfig::TYPE_AFTER_REMOVING)
            ->addCompilerPass(new ShippingMethodsCompilerPass());
    }
}
