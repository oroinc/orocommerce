<?php

namespace Oro\Bundle\PaymentTermBundle\DependencyInjection\Compiler;

use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;

/**
 * Registers class migration mappings for the Payment Term bundle.
 *
 * This compiler pass registers the migration of the legacy `PaymentBundle\Entity\PaymentTerm` class
 * to the new `PaymentTermBundle\Entity\PaymentTerm` location, and updates the payment method identifier
 * from `payment.paymentterm` to `paymentterm`.
 */
class ClassMigrationPass implements CompilerPassInterface
{
    #[\Override]
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
