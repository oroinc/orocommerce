<?php

namespace Oro\Bundle\ProductBundle\Tests\Functional\DataFixtures;

use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Common\Persistence\ObjectManager;
use Oro\Bundle\ProductBundle\ContentVariantType\ProductCollectionContentVariantType;
use Oro\Bundle\WebCatalogBundle\Entity\ContentNode;
use Oro\Bundle\WebCatalogBundle\Entity\ContentVariant;
use Oro\Bundle\WebCatalogBundle\Entity\WebCatalog;

class LoadProductCollectionContentVariantWithManuallyAddedData extends AbstractFixture implements
    DependentFixtureInterface
{
    const WEB_CATALOG = 'web-catalog';

    const CONTENT_VARIANT_WITH_FILTERS = 'content-variant-product-collection-with-filters';
    const CONTENT_VARIANT_WITH_MANUALLY_ADDED = 'content-variant-product-collection-manually-added';
    const CONTENT_VARIANT_WITH_MIXED = 'content-variant-product-collection-mixed';

    const PRODUCT_COLLECTION_VARIANT_RELATIONS = [
        self::CONTENT_VARIANT_WITH_FILTERS => LoadProductCollectionSegmentWithManuallyAddedData::SEGMENT_WITH_FILTERS,
        self::CONTENT_VARIANT_WITH_MANUALLY_ADDED
            => LoadProductCollectionSegmentWithManuallyAddedData::SEGMENT_WITH_MANUALLY_ADDED,
        self::CONTENT_VARIANT_WITH_MIXED => LoadProductCollectionSegmentWithManuallyAddedData::SEGMENT_WITH_MIXED,
    ];

    /**
     * {@inheritdoc}
     */
    public function getDependencies()
    {
        return [
            LoadProductCollectionSegmentWithManuallyAddedData::class,
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function load(ObjectManager $manager)
    {
        $webCatalog = new WebCatalog();
        $webCatalog->setName(self::WEB_CATALOG);
        $this->setReference(self::WEB_CATALOG, $webCatalog);
        $manager->persist($webCatalog);
        $manager->flush();

        $rootNode = new ContentNode();
        $rootNode->setWebCatalog($webCatalog);
        $rootNode->setDefaultTitle('root');
        $manager->persist($rootNode);

        foreach (self::PRODUCT_COLLECTION_VARIANT_RELATIONS as $variantName => $segmentName) {
            $contentVariant = new ContentVariant();
            $contentVariant->setType(ProductCollectionContentVariantType::TYPE);
            $contentVariant->setDefault(true);
            $contentVariant->setProductCollectionSegment($this->getReference($segmentName));
            $this->setReference($variantName, $contentVariant);
            $manager->persist($contentVariant);

            $contentNode = new ContentNode();
            $contentNode->setParentNode($rootNode);
            $contentNode->setWebCatalog($webCatalog);
            $contentNode->addContentVariant($contentVariant);
            $manager->persist($contentNode);
        }
        $manager->flush();
    }
}
