<?php

namespace OroB2B\Bundle\CheckoutBundle;

use OroB2B\Bundle\CheckoutBundle\DependencyInjection\CheckoutCompilerPass;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\HttpKernel\Bundle\Bundle;

class OroB2BCheckoutBundle extends Bundle
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
