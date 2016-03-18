<?php

namespace OroB2B\Bundle\PaymentBundle;

use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\HttpKernel\Bundle\Bundle;

use OroB2B\Bundle\PaymentBundle\DependencyInjection\CompilerPass\PaymentMethodTypePass;

class OroB2BPaymentBundle extends Bundle
{
    /**
     * {@inheritdoc}
     */
    public function build(ContainerBuilder $container)
    {
        $container->addCompilerPass(new PaymentMethodTypePass());
    }
}
