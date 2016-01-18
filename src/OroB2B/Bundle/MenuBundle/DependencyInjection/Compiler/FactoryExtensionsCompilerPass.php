<?php

namespace OroB2B\Bundle\MenuBundle\DependencyInjection\Compiler;

use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Reference;

class FactoryExtensionsCompilerPass implements CompilerPassInterface
{
    const TAG_NAME = 'orob2b_menu.factory_extension';
    const FACTORY_SERVICE_ID = 'orob2b_menu.factory_extension';

    /**
     * {@inheritdoc}
     */
    public function process(ContainerBuilder $container)
    {
        if (!$container->hasDefinition(self::FACTORY_SERVICE_ID)) {
            return;
        }

        $extensions = $container->findTaggedServiceIds(self::TAG_NAME);
        if (empty($extensions)) {
            return;
        }

        $service = $container->getDefinition(self::FACTORY_SERVICE_ID);

        foreach ($extensions as $extensionId => $tags) {
            foreach ($tags as $tag) {
                $priority = isset($tag['priority']) ? (int)$tag['priority'] : 0;
                $service->addMethodCall('addExtension', [new Reference($extensionId), $priority]);
            }
        }
    }
}
