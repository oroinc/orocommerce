<?php

namespace OroB2B\Bundle\CatalogBundle;

use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\HttpKernel\Bundle\Bundle;

use Oro\Bundle\LocaleBundle\DependencyInjection\Compiler\DefaultFallbackExtensionPass;

use OroB2B\Bundle\CatalogBundle\Entity\Category;

class OroB2BCatalogBundle extends Bundle
{
    /**
     * {@inheritdoc}
     */
    public function build(ContainerBuilder $container)
    {
        parent::build($container);

        $container
            ->addCompilerPass(new DefaultFallbackExtensionPass([
                Category::class => [
                    'title' => 'titles',
                    'shortDescription' => 'shortDescriptions',
                    'longDescription' => 'longDescriptions',
                ],
            ]));
    }
}
