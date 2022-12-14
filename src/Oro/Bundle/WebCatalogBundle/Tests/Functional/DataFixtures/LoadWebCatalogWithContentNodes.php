<?php

declare(strict_types=1);

namespace Oro\Bundle\WebCatalogBundle\Tests\Functional\DataFixtures;

use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;
use Oro\Bundle\ProductBundle\ContentVariantType\ProductCollectionContentVariantType;
use Oro\Bundle\ProductBundle\ContentVariantType\ProductPageContentVariantType;
use Oro\Bundle\ProductBundle\Entity\CollectionSortOrder;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\ProductBundle\Tests\Functional\DataFixtures\LoadFrontendProductData;
use Oro\Bundle\ProductBundle\Tests\Functional\DataFixtures\LoadProductData;
use Oro\Bundle\SegmentBundle\Entity\Segment;
use Oro\Bundle\SegmentBundle\Entity\SegmentType;
use Oro\Bundle\SegmentBundle\Tests\Functional\DataFixtures\LoadSegmentData;
use Oro\Bundle\WebCatalogBundle\Entity\ContentNode;
use Oro\Bundle\WebCatalogBundle\Entity\ContentVariant;
use Oro\Bundle\WebCatalogBundle\Entity\WebCatalog;

class LoadWebCatalogWithContentNodes extends AbstractFixture implements DependentFixtureInterface
{
    public const WEB_CATALOG_NAME = 'web_catalog_name';
    public const CONTENT_NODE_1 = 'content_node_1';
    public const CONTENT_NODE_2 = 'content_node_2';
    public const CONTENT_NODE_3 = 'content_node_3';
    public const CONTENT_VARIANT_1 = 'content_variant_1';
    public const CONTENT_VARIANT_2 = 'content_variant_2';
    public const CONTENT_VARIANT_3 = 'content_variant_3';
    public const PRODUCT_STATIC_SEGMENT = 'product_static_segment';

    private static array $contentVariants = [
        self::CONTENT_NODE_1 => self::CONTENT_VARIANT_1,
        self::CONTENT_NODE_2 => self::CONTENT_VARIANT_2,
        self::CONTENT_NODE_3 => self::CONTENT_VARIANT_3,
    ];

    private static array $productsForVariant = [
        self::CONTENT_VARIANT_1 => LoadProductData::PRODUCT_1,
        self::CONTENT_VARIANT_2 => LoadProductData::PRODUCT_2,
    ];

    public function getDependencies(): array
    {
        return [
            LoadFrontendProductData::class,
            LoadSegmentData::class
        ];
    }

    public function load(ObjectManager $manager): void
    {
        $webCatalog = new WebCatalog();
        $webCatalog->setName(self::WEB_CATALOG_NAME);
        $manager->persist($webCatalog);
        $this->setReference(self::WEB_CATALOG_NAME, $webCatalog);

        foreach (self::$contentVariants as $nodeReference => $variantReference) {
            if (array_key_exists($variantReference, self::$productsForVariant)) {
                $product = $this->getReference(self::$productsForVariant[$variantReference]);

                $contentVariant = new ContentVariant();
                $contentVariant->setType(ProductPageContentVariantType::TYPE);
                $contentVariant->setProductPageProduct($product);
                $contentVariant->setDefault(true);
            } else {
                $contentVariant = $this->buildProductCollectionVariant($manager);
            }

            $contentNode = new ContentNode();
            $contentNode->setWebCatalog($webCatalog);
            $contentNode->addContentVariant($contentVariant);

            $manager->persist($contentVariant);
            $manager->persist($contentNode);
            $this->setReference($variantReference, $contentVariant);
            $this->setReference($nodeReference, $contentNode);
        }

        $manager->flush();
    }

    private function buildProductCollectionVariant(ObjectManager $manager): ContentVariant
    {
        $segmentType = $manager->getRepository(SegmentType::class)->find(SegmentType::TYPE_STATIC);
        $segment = new Segment();
        $segment->setName('Product Static Segment');
        $segment->setEntity(Product::class);
        $segment->setType($segmentType);
        $segment->setDefinition(json_encode([
            'columns' => [
                [
                    'func' => null,
                    'label' => 'Label',
                    'name' => 'id',
                    'sorting' => ''
                ]
            ],
            'filters' =>[]
        ]));
        $this->setReference(self::PRODUCT_STATIC_SEGMENT, $segment);
        $manager->persist($segment);

        $collectionSortOrder = new CollectionSortOrder();
        $collectionSortOrder->setProduct($this->getReference(LoadProductData::PRODUCT_1));
        $collectionSortOrder->setSegment($segment);
        $collectionSortOrder->setSortOrder(1);
        $manager->persist($collectionSortOrder);

        $collectionSortOrder = new CollectionSortOrder();
        $collectionSortOrder->setProduct($this->getReference(LoadProductData::PRODUCT_2));
        $collectionSortOrder->setSegment($segment);
        $collectionSortOrder->setSortOrder(0.2);
        $manager->persist($collectionSortOrder);

        $contentVariant = new ContentVariant();
        $contentVariant->setType(ProductCollectionContentVariantType::TYPE);
        $contentVariant->setProductCollectionSegment($segment);
        $contentVariant->setDefault(true);

        return $contentVariant;
    }
}
