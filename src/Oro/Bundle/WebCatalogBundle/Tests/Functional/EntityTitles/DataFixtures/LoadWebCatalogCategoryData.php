<?php

namespace Oro\Bundle\WebCatalogBundle\Tests\Functional\EntityTitles\DataFixtures;

use Oro\Bundle\CatalogBundle\ContentVariantType\CategoryPageContentVariantType;
use Oro\Bundle\CatalogBundle\Tests\Functional\DataFixtures\LoadCategoryData;
use Oro\Bundle\ScopeBundle\Tests\Functional\DataFixtures\LoadScopeData;
use Oro\Bundle\WebCatalogBundle\Tests\Functional\DataFixtures\LoadWebCatalogData;

class LoadWebCatalogCategoryData extends AbstractLoadWebCatalogData
{
    /**
     * {@inheritdoc}
     */
    public function getDependencies()
    {
        return [
            LoadCategoryData::class,
            LoadWebCatalogData::class,
            LoadScopeData::class
        ];
    }

    /**
     * {@inheritdoc}
     */
    protected function getRoute()
    {
        return 'oro_product_frontend_product_index';
    }

    /**
     * {@inheritdoc}
     */
    protected function getContentVariantType()
    {
        return CategoryPageContentVariantType::TYPE;
    }

    /**
     * {@inheritdoc}
     */
    protected function getEntitySetterMethod()
    {
        return 'setCategoryPageCategory';
    }

    /**
     * {@inheritdoc}
     */
    protected function getEntity()
    {
        return $this->getReference(LoadCategoryData::FIRST_LEVEL);
    }
}
