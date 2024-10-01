<?php

namespace Oro\Bundle\WebCatalogBundle\Tests\Functional\EntityTitles\DataFixtures;

use Oro\Bundle\ProductBundle\ContentVariantType\ProductPageContentVariantType;
use Oro\Bundle\ProductBundle\Tests\Functional\DataFixtures\LoadProductData;
use Oro\Bundle\ScopeBundle\Tests\Functional\DataFixtures\LoadScopeData;
use Oro\Bundle\WebCatalogBundle\Tests\Functional\DataFixtures\LoadWebCatalogData;

class LoadWebCatalogProductData extends AbstractLoadWebCatalogData
{
    #[\Override]
    public function getDependencies()
    {
        return [
            LoadWebCatalogData::class,
            LoadProductData::class,
            LoadScopeData::class
        ];
    }

    #[\Override]
    protected function getRoute()
    {
        return 'oro_product_frontend_product_view';
    }

    #[\Override]
    protected function getContentVariantType()
    {
        return ProductPageContentVariantType::TYPE;
    }

    #[\Override]
    protected function getEntitySetterMethod()
    {
        return 'setProductPageProduct';
    }

    #[\Override]
    protected function getEntity()
    {
        return $this->getReference(LoadProductData::PRODUCT_1);
    }
}
