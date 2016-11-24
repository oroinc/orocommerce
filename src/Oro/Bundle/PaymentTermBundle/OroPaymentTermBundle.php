<?php

namespace Oro\Bundle\PaymentTermBundle;

use Oro\Bundle\PaymentTermBundle\DependencyInjection\Compiler\ClassMigrationPass;

use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\HttpKernel\Bundle\Bundle;

class OroPaymentTermBundle extends Bundle
{
    /** {@inheritdoc} */
    public function build(ContainerBuilder $container)
    {
        $container->addCompilerPass(new ClassMigrationPass());
    }
}
