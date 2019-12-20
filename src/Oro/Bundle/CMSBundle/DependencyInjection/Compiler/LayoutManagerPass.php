<?php

namespace Oro\Bundle\CMSBundle\DependencyInjection\Compiler;

use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\DependencyInjection\Reference;

/**
 * Builds layout manager and factory builder which will render content widgets.
 */
class LayoutManagerPass implements CompilerPassInterface
{
    private const LAYOUT_FACTORY_BUILDER_SERVICE_ID = 'oro_layout.layout_factory_builder';
    private const CMS_LAYOUT_FACTORY_BUILDER_SERVICE_ID = 'oro_cms.layout_factory_builder';
    private const LAYOUT_MANGER_SERVICE_ID = 'oro_layout.layout_manager';
    private const CMS_LAYOUT_MANGER_SERVICE_ID = 'oro_cms.layout_manager';

    /**
     * {@inheritdoc}
     */
    public function process(ContainerBuilder $container): void
    {
        if ($container->hasDefinition(self::LAYOUT_FACTORY_BUILDER_SERVICE_ID)) {
            $layoutFactoryBuilderDef = $container->findDefinition(self::LAYOUT_FACTORY_BUILDER_SERVICE_ID);

            $cmsLayoutFactoryBuilderDef = new Definition();
            $cmsLayoutFactoryBuilderDef->setClass($layoutFactoryBuilderDef->getClass())
                ->setArguments($layoutFactoryBuilderDef->getArguments());

            foreach ($layoutFactoryBuilderDef->getMethodCalls() as $methodCall) {
                if ($methodCall[0] === 'addExtension' &&
                    isset($methodCall[1][0]) &&
                    (string) $methodCall[1][0] === 'oro_layout.theme_extension'
                ) {
                    $cmsLayoutFactoryBuilderDef->addMethodCall(
                        'addExtension',
                        [new Reference('oro_cms.layout_extension.content_widget')]
                    );

                    continue;
                }

                $cmsLayoutFactoryBuilderDef->addMethodCall($methodCall[0], $methodCall[1]);
            }

            $container->setDefinition(self::CMS_LAYOUT_FACTORY_BUILDER_SERVICE_ID, $cmsLayoutFactoryBuilderDef);
        }

        if ($container->hasDefinition(self::LAYOUT_MANGER_SERVICE_ID)) {
            $layoutManagerDef = $container->findDefinition(self::LAYOUT_MANGER_SERVICE_ID);

            $cmsLayoutManagerDef = new Definition();
            $cmsLayoutManagerDef->setClass($layoutManagerDef->getClass())
                ->setArguments($layoutManagerDef->getArguments())
                ->replaceArgument(0, new Reference(self::CMS_LAYOUT_FACTORY_BUILDER_SERVICE_ID));

            $container->setDefinition(self::CMS_LAYOUT_MANGER_SERVICE_ID, $cmsLayoutManagerDef);
        }
    }
}
