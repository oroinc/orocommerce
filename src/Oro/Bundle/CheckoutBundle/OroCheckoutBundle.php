<?php

namespace Oro\Bundle\CheckoutBundle;

use Oro\Bundle\CheckoutBundle\DependencyInjection\Compiler as Compiler;
use Oro\Bundle\CheckoutBundle\DependencyInjection\OroCheckoutExtension;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\HttpKernel\Bundle\Bundle;

class OroCheckoutBundle extends Bundle
{
    /**
     * {@inheritdoc}
     */
    public function build(ContainerBuilder $container)
    {
        $container->addCompilerPass(new Compiler\CheckoutCompilerPass());
        $container->addCompilerPass(new Compiler\TwigSandboxConfigurationPass());
        $container->addCompilerPass(new Compiler\CheckoutStateDiffCompilerPass());
        $container->addCompilerPass(new Compiler\CheckoutLineItemConverterPass());
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
