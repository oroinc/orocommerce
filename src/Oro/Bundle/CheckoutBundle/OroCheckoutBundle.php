<?php

namespace Oro\Bundle\CheckoutBundle;

use Oro\Bundle\CheckoutBundle\DependencyInjection\Compiler;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\HttpKernel\Bundle\Bundle;

class OroCheckoutBundle extends Bundle
{
    /**
     * {@inheritdoc}
     */
    public function build(ContainerBuilder $container): void
    {
        parent::build($container);

        $container->addCompilerPass(new Compiler\TwigSandboxConfigurationPass());
        $container->addCompilerPass(new Compiler\CheckoutLineItemConverterPass());
    }
}
