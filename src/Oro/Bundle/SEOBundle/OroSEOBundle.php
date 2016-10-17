<?php

namespace Oro\Bundle\SEOBundle;

use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\HttpKernel\Bundle\Bundle;

use Oro\Bundle\LocaleBundle\DependencyInjection\Compiler\DefaultFallbackExtensionPass;

class OroSEOBundle extends Bundle
{
    /**
     * {@inheritdoc}
     */
    public function build(ContainerBuilder $container)
    {
        parent::build($container);

        $fields = [
            'metaTitle' => 'metaTitles',
            'metaDescription' => 'metaDescriptions',
            'metaKeyword' => 'metaKeywords'
        ];

        $container
            ->addCompilerPass(new DefaultFallbackExtensionPass([
                'Oro\Bundle\ProductBundle\Entity\Product' => $fields,
                'Oro\Bundle\CatalogBundle\Entity\Category' => $fields,
                'Oro\Bundle\CMSBundle\Entity\Page' => $fields,
            ]));
    }
}
