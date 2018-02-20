<?php

namespace Oro\Bundle\AlternativeCheckoutBundle;

use Oro\Bundle\CheckoutBundle\DependencyInjection\Compiler\CheckoutCompilerPass;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\HttpKernel\Bundle\Bundle;

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
