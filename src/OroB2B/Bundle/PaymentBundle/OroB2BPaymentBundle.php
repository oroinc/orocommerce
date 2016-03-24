<?php

namespace OroB2B\Bundle\PaymentBundle;

use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\HttpKernel\Bundle\Bundle;

use OroB2B\Bundle\PaymentBundle\DependencyInjection\CompilerPass\PaymentMethodTypePass;
use OroB2B\Bundle\PaymentBundle\DependencyInjection\OroB2BPaymentExtension;

class OroB2BPaymentBundle extends Bundle
{
    /** {@inheritdoc} */
    public function build(ContainerBuilder $container)
    {
        $container->addCompilerPass(new PaymentMethodTypePass());
    }

    /** {@inheritdoc} */
    public function getContainerExtension()
    {
        return new OroB2BPaymentExtension();
    }
}
