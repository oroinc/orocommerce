<?php

namespace Oro\Bundle\UPSBundle;

use Oro\Bundle\UPSBundle\DependencyInjection\CompilerPass\ShippingChannelCompilerPass;

use Symfony\Component\DependencyInjection\Compiler\PassConfig;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\HttpKernel\Bundle\Bundle;

use Oro\Bundle\UPSBundle\DependencyInjection\OroUPSExtension;

class OroUPSBundle extends Bundle
{
    /**
     * {@inheritdoc}
     */
    public function getContainerExtension()
    {
        if (!$this->extension) {
            $this->extension = new OroUPSExtension();
        }

        return $this->extension;
    }

    /**
     * {@inheritdoc}
     */
    public function build(ContainerBuilder $container)
    {
        parent::build($container);

        $container->addCompilerPass(new ShippingChannelCompilerPass(), PassConfig::TYPE_AFTER_REMOVING);
    }
}
