<?php

namespace Oro\Bundle\ProductBundle\Tests\Functional\DataFixtures;

use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;
use Oro\Bundle\FilterBundle\Form\Type\Filter\TextFilterType;
use Oro\Bundle\ProductBundle\Entity\CollectionSortOrder;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\SegmentBundle\Entity\Segment;
use Oro\Bundle\SegmentBundle\Entity\SegmentSnapshot;
use Oro\Bundle\SegmentBundle\Entity\SegmentType;

class LoadProductCollectionWithSortOrderData extends AbstractFixture implements DependentFixtureInterface
{
    public const SEGMENT = 'product-collection-segment';

    public const PRODUCT = 'pr_collection_product';
    public const PRODUCT_REMOVED = 'removed_pr_collection_product';
    public const PRODUCT_ADDED = 'pr_collection_product_added';
    public const SORT_ORDER_REMOVED = 'product_removed_collection_sort_order';
    public const SORT_ORDER_ADDED = 'product_added_collection_sort_order';

    private static $products = [
        LoadProductData::PRODUCT_1 => self::PRODUCT,
        LoadProductData::PRODUCT_2 => self::PRODUCT_REMOVED,
        LoadProductData::PRODUCT_3 => self::PRODUCT_ADDED,
    ];

    private static $segmentDefinition = [
        'columns' => [
            [
                'func' => null,
                'label' => 'Label',
                'name' => 'sku',
                'sorting' => '',
            ],
        ],
        'filters' =>[
            [
                'columnName' => 'sku',
                'criterion' => [
                    'filter' => 'string',
                    'data' => [
                        'value' => 'pr_collection_product',
                        'type' => TextFilterType::TYPE_CONTAINS,
                    ],
                ],
            ],
        ]
    ];

    /**
     * {@inheritdoc}
     */
    public function getDependencies()
    {
        return [
            LoadProductData::class,
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function load(ObjectManager $manager)
    {
        foreach (self::$products as $reference => $productSku) {
            $product = $this->getReference($reference)->setSku($productSku);
            $manager->persist($product);
            $this->setReference($productSku, $product);
        }

        $segmentType = $manager->getRepository(SegmentType::class)->find(SegmentType::TYPE_STATIC);
        $segment = new Segment();
        $segment->setName(self::SEGMENT);
        $segment->setEntity(Product::class);
        $segment->setType($segmentType);
        $segment->setDefinition(json_encode(self::$segmentDefinition));
        $manager->persist($segment);
        $this->setReference(self::SEGMENT, $segment);

        $segmentSnapshot = new SegmentSnapshot($segment);
        $segmentSnapshot->setIntegerEntityId($this->getReference(self::PRODUCT)->getId());
        $manager->persist($segmentSnapshot);

        $segmentSnapshot2 = new SegmentSnapshot($segment);
        $segmentSnapshot2->setIntegerEntityId($this->getReference(self::PRODUCT_REMOVED)->getId());
        $manager->persist($segmentSnapshot2);

        $collectionSortOrder2 = new CollectionSortOrder();
        $collectionSortOrder2->setSegment($this->getReference(self::SEGMENT));
        $collectionSortOrder2->setProduct($this->getReference(self::PRODUCT_REMOVED));
        $collectionSortOrder2->setSortOrder(0);
        $manager->persist($collectionSortOrder2);
        $this->setReference(self::SORT_ORDER_REMOVED, $collectionSortOrder2);

        $collectionSortOrder3 = new CollectionSortOrder();
        $collectionSortOrder3->setSegment($this->getReference(self::SEGMENT));
        $collectionSortOrder3->setProduct($this->getReference(self::PRODUCT_ADDED));
        $collectionSortOrder3->setSortOrder(0.2);
        $manager->persist($collectionSortOrder3);
        $this->setReference(self::SORT_ORDER_ADDED, $collectionSortOrder3);

        $manager->flush();
    }
}
