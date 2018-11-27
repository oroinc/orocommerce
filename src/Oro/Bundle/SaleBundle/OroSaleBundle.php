<?php

namespace Oro\Bundle\SaleBundle;

use Oro\Bundle\SaleBundle\DependencyInjection\Compiler\TwigSandboxConfigurationPass;
use Oro\Bundle\SaleBundle\DependencyInjection\OroSaleExtension;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\HttpKernel\Bundle\Bundle;

/**
 * The SaleBundle bundle class.
 */
class OroSaleBundle extends Bundle
{
    /**
     * {@inheritdoc}
     */
    public function getContainerExtension()
    {
        if (!$this->extension) {
            $this->extension = new OroSaleExtension();
        }

        return $this->extension;
    }

    /**
     * {@inheritdoc}
     */
    public function build(ContainerBuilder $container)
    {
        $container->addCompilerPass(new TwigSandboxConfigurationPass());
    }
}
