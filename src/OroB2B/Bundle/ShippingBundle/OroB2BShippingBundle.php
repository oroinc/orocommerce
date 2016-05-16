<?php

namespace OroB2B\Bundle\ShippingBundle;

use Symfony\Component\DependencyInjection\Compiler\PassConfig;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\HttpKernel\Bundle\Bundle;

use OroB2B\Bundle\ShippingBundle\DependencyInjection\CompilerPass\FreightClassesPass;
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

    /**
     * {@inheritdoc}
     */
    public function build(ContainerBuilder $container)
    {
        parent::build($container);

        $container->addCompilerPass(new FreightClassesPass(), PassConfig::TYPE_AFTER_REMOVING);
    }
}
