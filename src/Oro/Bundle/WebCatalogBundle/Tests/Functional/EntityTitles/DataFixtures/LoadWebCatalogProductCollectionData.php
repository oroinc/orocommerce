<?php

namespace Oro\Bundle\WebCatalogBundle\Tests\Functional\EntityTitles\DataFixtures;

use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Oro\Bundle\ProductBundle\ContentVariantType\ProductCollectionContentVariantType;

class LoadWebCatalogProductCollectionData extends AbstractLoadWebCatalogData implements DependentFixtureInterface
{
    /**
     * {@inheritdoc}
     */
    public function getDependencies()
    {
        return [
            LoadProductCollectionSegmentData::class,
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
        return  ProductCollectionContentVariantType::TYPE;
    }

    /**
     * {@inheritdoc}
     */
    protected function getEntitySetterMethod()
    {
        return 'setProductCollectionSegment';
    }

    /**
     * {@inheritdoc}
     */
    protected function getEntity()
    {
        return $this->getReference(LoadProductCollectionSegmentData::PRODUCT_COLLECTION_SEGMENT_1);
    }
}
