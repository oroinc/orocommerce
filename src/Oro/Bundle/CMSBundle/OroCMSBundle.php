<?php

namespace Oro\Bundle\CMSBundle;

use Oro\Bundle\CMSBundle\DependencyInjection\Compiler\AttributeBlockTypeMapperPass;
use Oro\Bundle\CMSBundle\DependencyInjection\Compiler\DbalTypeDefaultValuePass;
use Oro\Bundle\CMSBundle\DependencyInjection\Compiler\EntityExtendFieldTypePass;
use Oro\Bundle\CMSBundle\DependencyInjection\Compiler\ExtendFieldValidationLoaderPass;
use Oro\Bundle\LocaleBundle\DependencyInjection\Compiler\EntityFallbackFieldsStoragePass;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\HttpKernel\Bundle\Bundle;

class OroCMSBundle extends Bundle
{
    /**
     * {@inheritdoc}
     */
    public function build(ContainerBuilder $container): void
    {
        parent::build($container);

        $container->addCompilerPass(new EntityExtendFieldTypePass());
        $container->addCompilerPass(new DbalTypeDefaultValuePass());
        $container->addCompilerPass(new ExtendFieldValidationLoaderPass());
        $container->addCompilerPass(new AttributeBlockTypeMapperPass());
        $container->addCompilerPass(new EntityFallbackFieldsStoragePass([
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
