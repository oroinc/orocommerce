<?php

namespace Oro\Bundle\WebCatalogBundle\Tests\Functional\EntityTitles\DataFixtures;

use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Oro\Bundle\ProductBundle\ContentVariantType\ProductCollectionContentVariantType;

class LoadWebCatalogProductCollectionData extends AbstractLoadWebCatalogData implements DependentFixtureInterface
{
    #[\Override]
    public function getDependencies()
    {
        return [
            LoadProductCollectionSegmentData::class,
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
        return  ProductCollectionContentVariantType::TYPE;
    }

    #[\Override]
    protected function getEntitySetterMethod()
    {
        return 'setProductCollectionSegment';
    }

    #[\Override]
    protected function getEntity()
    {
        return $this->getReference(LoadProductCollectionSegmentData::PRODUCT_COLLECTION_SEGMENT_1);
    }
}
