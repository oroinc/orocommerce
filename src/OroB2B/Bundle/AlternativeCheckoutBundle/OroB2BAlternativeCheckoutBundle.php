<?php

namespace OroB2B\Bundle\AlternativeCheckoutBundle;

use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\HttpKernel\Bundle\Bundle;

use OroB2B\Bundle\CheckoutBundle\DependencyInjection\Compiler\CheckoutCompilerPass;

class OroB2BAlternativeCheckoutBundle extends Bundle
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
