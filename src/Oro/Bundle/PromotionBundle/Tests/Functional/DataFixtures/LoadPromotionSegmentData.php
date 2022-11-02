<?php

namespace Oro\Bundle\PromotionBundle\Tests\Functional\DataFixtures;

use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\ProductBundle\Tests\Functional\DataFixtures\LoadProductData;
use Oro\Bundle\SegmentBundle\Entity\Segment;
use Oro\Bundle\SegmentBundle\Entity\SegmentSnapshot;
use Oro\Bundle\SegmentBundle\Entity\SegmentType;

class LoadPromotionSegmentData extends AbstractFixture implements DependentFixtureInterface
{
    const EMPTY_PROMOTION_SEGMENT = 'empty_promotion_segment';
    const NOT_EMPTY_PROMOTION_SEGMENT = 'not_empty_promotion_segment';

    /**
     * @var array
     */
    private static $segments = [
        self::EMPTY_PROMOTION_SEGMENT => [
            'products' => []
        ],
        self::NOT_EMPTY_PROMOTION_SEGMENT => [
            'products' => [
                LoadProductData::PRODUCT_1,
                LoadProductData::PRODUCT_3,
                LoadProductData::PRODUCT_7
            ]
        ]
    ];

    /**
     * {@inheritdoc}
     */
    public function load(ObjectManager $manager)
    {
        $segmentTypeRepository = $manager->getRepository(SegmentType::class);
        $segmentType = $segmentTypeRepository->find(SegmentType::TYPE_STATIC);

        foreach (self::$segments as $segmentName => $segmentInfo) {
            $segment = new Segment();
            $segment
                ->setName($segmentName)
                ->setType($segmentType)
                ->setEntity(Product::class)
                ->setDefinition('[]');

            $this->addReference($segmentName, $segment);

            $manager->persist($segment);
        }

        $manager->flush();

        foreach (self::$segments as $segmentName => $segmentInfo) {
            $this->createSnapshot($manager, $segmentName, $segmentInfo['products']);
        }

        $manager->flush();
    }

    /**
     * {@inheritdoc}
     */
    public function getDependencies()
    {
        return [LoadProductData::class];
    }

    /**
     * @param ObjectManager $manager
     * @param string $segmentName
     * @param array $productReferences
     */
    private function createSnapshot(ObjectManager $manager, $segmentName, array $productReferences)
    {
        /** @var Segment $segment */
        $segment = $this->getReference($segmentName);
        foreach ($productReferences as $productReference) {
            /** @var Product $product */
            $product = $this->getReference($productReference);

            $snapshot = new SegmentSnapshot($segment);
            $snapshot->setIntegerEntityId($product->getId());

            $manager->persist($snapshot);
        }
    }
}
