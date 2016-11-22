<?php

namespace Oro\Bundle\CommerceMenuBundle\DependencyInjection\Compiler;

use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;

class AddFrontendClassMigrationPass implements CompilerPassInterface
{
    const FRONTEND_CLASS_MIGRATION_SERVICE_ID = 'oro_frontend.class_migration';

    /**
     * {@inheritdoc}
     */
    public function process(ContainerBuilder $container)
    {
        if ($container->hasDefinition(self::FRONTEND_CLASS_MIGRATION_SERVICE_ID)) {
            $definition = $container->findDefinition(self::FRONTEND_CLASS_MIGRATION_SERVICE_ID);

            $definition->addMethodCall('append', ['FrontendNavigation', 'CommerceMenu']);
            $definition->addMethodCall('append', ['frontendnavigation', 'commercemenu']);
        }
    }
}
