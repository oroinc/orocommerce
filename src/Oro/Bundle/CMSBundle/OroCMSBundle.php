<?php

namespace Oro\Bundle\CMSBundle;

use Oro\Bundle\CMSBundle\DependencyInjection\Compiler\AttributeBlockTypeMapperPass;
use Oro\Bundle\CMSBundle\DependencyInjection\Compiler\EntityExtendFieldTypePass;
use Oro\Bundle\CMSBundle\DependencyInjection\Compiler\WidgetTagPass;
use Oro\Bundle\CMSBundle\Entity\ContentBlock;
use Oro\Bundle\CMSBundle\Entity\Page;
use Oro\Bundle\LocaleBundle\DependencyInjection\Compiler\DefaultFallbackExtensionPass;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\HttpKernel\Bundle\Bundle;

/**
 * The CMSBundle bundle class.
 */
class OroCMSBundle extends Bundle
{
    /**
     * {@inheritdoc}
     */
    public function build(ContainerBuilder $container)
    {
        parent::build($container);

        $container
            ->addCompilerPass(new WidgetTagPass())
            ->addCompilerPass(new EntityExtendFieldTypePass())
            ->addCompilerPass(new AttributeBlockTypeMapperPass())
            ->addCompilerPass(new DefaultFallbackExtensionPass([
                Page::class => [
                    'slugPrototype' => 'slugPrototypes',
                    'title' => 'titles'
                ],
                ContentBlock::class => [
                    'title' => 'titles'
                ]
            ]));
    }
}
