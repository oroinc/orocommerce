<?php

namespace Oro\Bundle\OrderBundle;

use Oro\Bundle\OrderBundle\DependencyInjection\Compiler\TwigSandboxConfigurationPass;
use Oro\Bundle\OrderBundle\DependencyInjection\OroOrderExtension;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\HttpKernel\Bundle\Bundle;

/**
 * Bundle adds the Order entity to the OroCommerce application and enables OroCommerce users in the management console
 * and customer users in the storefront to create and manage orders.
 */
class OroOrderBundle extends Bundle
{
    /**
     * {@inheritdoc}
     */
    public function build(ContainerBuilder $container)
    {
        $container->addCompilerPass(new TwigSandboxConfigurationPass());
        parent::build($container);
    }

    /**
     * {@inheritdoc}
     */
    public function getContainerExtension()
    {
        if (!$this->extension) {
            $this->extension = new OroOrderExtension();
        }

        return $this->extension;
    }
}
