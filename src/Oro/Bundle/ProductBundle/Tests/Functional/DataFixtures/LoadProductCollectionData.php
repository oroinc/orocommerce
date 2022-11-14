<?php

namespace Oro\Bundle\ProductBundle\Tests\Functional\DataFixtures;

use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;
use Oro\Bundle\FilterBundle\Form\Type\Filter\TextFilterType;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\SegmentBundle\Entity\Segment;
use Oro\Bundle\SegmentBundle\Entity\SegmentSnapshot;
use Oro\Bundle\SegmentBundle\Entity\SegmentType;

class LoadProductCollectionData extends AbstractFixture implements DependentFixtureInterface
{
    public const SEGMENT = 'product-collection-segment';

    public const PRODUCT = 'pr_collection_product';
    public const PRODUCT_REMOVED = 'removed_pr_collection_product';
    public const PRODUCT_ADDED = 'pr_collection_product_added';

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
                        'type' => TextFilterType::TYPE_STARTS_WITH,
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
        $manager->flush();
    }
}
