<?php

namespace Oro\Bundle\AlternativeCheckoutBundle;

use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\HttpKernel\Bundle\Bundle;

use Oro\Bundle\CheckoutBundle\DependencyInjection\Compiler\CheckoutCompilerPass;

class OroAlternativeCheckoutBundle extends Bundle
{
    /**
     * {@inheritdoc}
     */
    public function build(ContainerBuilder $container)
    {
        $container->addCompilerPass(new CheckoutCompilerPass());
        parent::build($container);
    }
}
