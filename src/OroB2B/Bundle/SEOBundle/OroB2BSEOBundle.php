<?php

namespace OroB2B\Bundle\SEOBundle;

use OroB2B\Bundle\FallbackBundle\DependencyInjection\Compiler\DefaultFallbackExtensionPass;
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

        $options = [
            'metaTitles', 'metaDescriptions', 'metaKeywords'
        ];

        $classes = [
            'OroB2B\Bundle\ProductBundle\Entity\Product',
            'OroB2B\Bundle\CatalogBundle\Entity\Category',
            'OroB2B\Bundle\CMSBundle\Entity\Page'
        ];

        $container->addCompilerPass(new DefaultFallbackExtensionPass($options, $classes));
    }
}
