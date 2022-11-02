<?php

namespace Oro\Bundle\PaymentTermBundle;

use Oro\Bundle\PaymentTermBundle\DependencyInjection\Compiler\ClassMigrationPass;
use Oro\Bundle\PaymentTermBundle\DependencyInjection\Compiler\TwigSandboxConfigurationPass;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\HttpKernel\Bundle\Bundle;

class OroPaymentTermBundle extends Bundle
{
    /**
     * {@inheritdoc}
     */
    public function build(ContainerBuilder $container): void
    {
        parent::build($container);

        $container->addCompilerPass(new ClassMigrationPass());
        $container->addCompilerPass(new TwigSandboxConfigurationPass());
    }
}
