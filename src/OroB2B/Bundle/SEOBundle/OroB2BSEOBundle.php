<?php

namespace OroB2B\Bundle\SEOBundle;

use Oro\Bundle\LocaleBundle\DependencyInjection\Compiler\DefaultFallbackExtensionPass;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\HttpKernel\Bundle\Bundle;

class OroB2BSEOBundle extends Bundle
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
            'metaKeywords' => 'metaKeywords'
        ];

        $classes = [
            'OroB2B\Bundle\ProductBundle\Entity\Product' => $fields,
            'OroB2B\Bundle\CatalogBundle\Entity\Category' => $fields,
            'OroB2B\Bundle\CMSBundle\Entity\Page' => $fields
        ];

        $container->addCompilerPass(new DefaultFallbackExtensionPass($classes));
    }
}
