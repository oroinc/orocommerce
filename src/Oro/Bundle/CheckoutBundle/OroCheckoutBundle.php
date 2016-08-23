<?php

namespace Oro\Bundle\CheckoutBundle;

use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\HttpKernel\Bundle\Bundle;

use Oro\Bundle\CheckoutBundle\DependencyInjection\OroCheckoutExtension;
use Oro\Bundle\CheckoutBundle\DependencyInjection\Compiler\TwigSandboxConfigurationPass;
use Oro\Bundle\CheckoutBundle\DependencyInjection\Compiler\CheckoutCompilerPass;

class OroCheckoutBundle extends Bundle
{
    /**
     * {@inheritdoc}
     */
    public function build(ContainerBuilder $container)
    {
        $container->addCompilerPass(new CheckoutCompilerPass());
        $container->addCompilerPass(new TwigSandboxConfigurationPass());
        parent::build($container);
    }

    /**
     * {@inheritdoc}
     */
    public function getContainerExtension()
    {
        if (!$this->extension) {
            $this->extension = new OroCheckoutExtension();
        }

        return $this->extension;
    }
}
