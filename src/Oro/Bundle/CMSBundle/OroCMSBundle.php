<?php

namespace Oro\Bundle\CMSBundle;

use Oro\Bundle\CMSBundle\DependencyInjection\Compiler\AttributeBlockTypeMapperPass;
use Oro\Bundle\CMSBundle\DependencyInjection\Compiler\EntityExtendFieldTypePass;
use Oro\Bundle\CMSBundle\DependencyInjection\Compiler\ExtendFieldValidationLoaderPass;
use Oro\Bundle\CMSBundle\DependencyInjection\Compiler\LayoutManagerPass;
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
            ->addCompilerPass(new EntityExtendFieldTypePass())
            ->addCompilerPass(new ExtendFieldValidationLoaderPass())
            ->addCompilerPass(new AttributeBlockTypeMapperPass())
            ->addCompilerPass(new LayoutManagerPass())
            ->addCompilerPass(new DefaultFallbackExtensionPass([
                'Oro\Bundle\CMSBundle\Entity\Page' => [
                    'slugPrototype' => 'slugPrototypes',
                    'title' => 'titles'
                ],
                'Oro\Bundle\CMSBundle\Entity\ContentBlock' => [
                    'title' => 'titles'
                ]
            ]));
    }
}
