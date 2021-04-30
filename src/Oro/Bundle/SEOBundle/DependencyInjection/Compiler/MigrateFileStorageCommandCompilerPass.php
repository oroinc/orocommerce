<?php

namespace Oro\Bundle\SEOBundle\DependencyInjection\Compiler;

use Oro\Bundle\GaufretteBundle\Command\MigrateFileStorageCommand;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Reference;

/**
 * Adds SEO file storage config to the oro:gaufrette:migrate-filestorages migration command.
 */
class MigrateFileStorageCommandCompilerPass implements CompilerPassInterface
{
    /**
     * {@inheritDoc}
     */
    public function process(ContainerBuilder $container): void
    {
        $container->getDefinition(MigrateFileStorageCommand::class)
            ->addMethodCall(
                'addMapping',
                ['/public/sitemaps', 'sitemaps']
            )
            ->addMethodCall(
                'addFileManager',
                ['sitemaps', new Reference('oro_seo.file_manager')]
            );
    }
}
