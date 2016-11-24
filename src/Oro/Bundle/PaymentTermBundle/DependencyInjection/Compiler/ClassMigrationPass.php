<?php

namespace Oro\Bundle\PaymentTermBundle\DependencyInjection\Compiler;

use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;

class ClassMigrationPass implements CompilerPassInterface
{
    /** {@inheritdoc} */
    public function process(ContainerBuilder $container)
    {
        $container
            ->getDefinition('oro_frontend.class_migration')
            ->addMethodCall(
                'append',
                ['PaymentBundle\\Entity\\PaymentTerm', 'PaymentTermBundle\\Entity\\PaymentTerm']
            )
            ->addMethodCall('append', ['payment.paymentterm', 'paymentterm']);
    }
}
