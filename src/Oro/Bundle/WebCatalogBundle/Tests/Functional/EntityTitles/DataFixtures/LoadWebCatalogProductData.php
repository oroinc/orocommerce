<?php

namespace Oro\Bundle\WebCatalogBundle\Tests\Functional\EntityTitles\DataFixtures;

use Oro\Bundle\ProductBundle\ContentVariantType\ProductPageContentVariantType;
use Oro\Bundle\ProductBundle\Tests\Functional\DataFixtures\LoadProductData;
use Oro\Bundle\ScopeBundle\Tests\Functional\DataFixtures\LoadScopeData;
use Oro\Bundle\WebCatalogBundle\Tests\Functional\DataFixtures\LoadWebCatalogData;

class LoadWebCatalogProductData extends AbstractLoadWebCatalogData
{
    /**
     * {@inheritdoc}
     */
    public function getDependencies()
    {
        return [
            LoadWebCatalogData::class,
            LoadProductData::class,
            LoadScopeData::class
        ];
    }

    /**
     * {@inheritdoc}
     */
    protected function getRoute()
    {
        return 'oro_product_frontend_product_view';
    }

    /**
     * {@inheritdoc}
     */
    protected function getContentVariantType()
    {
        return ProductPageContentVariantType::TYPE;
    }

    /**
     * {@inheritdoc}
     */
    protected function getEntitySetterMethod()
    {
        return 'setProductPageProduct';
    }

    /**
     * {@inheritdoc}
     */
    protected function getEntity()
    {
        return $this->getReference(LoadProductData::PRODUCT_1);
    }
}
