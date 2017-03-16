<?php

namespace Oro\Bundle\SEOBundle;

use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\HttpKernel\Bundle\Bundle;

use Oro\Bundle\ProductBundle\DependencyInjection\CompilerPass\ContentNodeFieldsChangesCompilerPass;
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
            'metaDescription' => 'metaDescriptions',
            'metaKeyword' => 'metaKeywords'
        ];

        $container
            ->addCompilerPass(new DefaultFallbackExtensionPass([
                'Oro\Bundle\ProductBundle\Entity\Product' => $fields,
                'Oro\Bundle\CatalogBundle\Entity\Category' => $fields,
                'Oro\Bundle\CMSBundle\Entity\Page' => $fields,
            ]))
            ->addCompilerPass(new ContentNodeFieldsChangesCompilerPass(
                array_values($fields),
                'oro_product.event_listener.product_content_variant_reindex'
            ))
            ->addCompilerPass(new ContentNodeFieldsChangesCompilerPass(
                array_values($fields),
                'oro_catalog.event_listener.category_content_variant_index'
            ));
    }
}
