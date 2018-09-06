<?php

namespace Oro\Bundle\PaymentTermBundle;

use Oro\Bundle\PaymentTermBundle\DependencyInjection\Compiler\ClassMigrationPass;
use Oro\Bundle\PaymentTermBundle\DependencyInjection\Compiler\TwigSandboxConfigurationPass;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\HttpKernel\Bundle\Bundle;

/**
 * Bundle enables the OroCommerce management console administrator to configure and activate
 * the Payment Term payment methods for customer orders
 */
class OroPaymentTermBundle extends Bundle
{
    /** {@inheritdoc} */
    public function build(ContainerBuilder $container)
    {
        $container->addCompilerPass(new ClassMigrationPass())
            ->addCompilerPass(new TwigSandboxConfigurationPass());
    }
}
