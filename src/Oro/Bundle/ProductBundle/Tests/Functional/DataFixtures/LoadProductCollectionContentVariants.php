<?php

namespace Oro\Bundle\ProductBundle\Tests\Functional\DataFixtures;

use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;
use Oro\Bundle\FrontendTestFrameworkBundle\Entity\TestContentNode;
use Oro\Bundle\FrontendTestFrameworkBundle\Entity\TestContentVariant;
use Oro\Bundle\OrganizationBundle\Entity\Organization;
use Oro\Bundle\ProductBundle\Entity\CollectionSortOrder;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\SegmentBundle\Entity\Segment;
use Oro\Bundle\SegmentBundle\Entity\SegmentSnapshot;
use Oro\Bundle\SegmentBundle\Entity\SegmentType;
use Oro\Bundle\SegmentBundle\Tests\Functional\DataFixtures\LoadSegmentData;
use Oro\Bundle\TestFrameworkBundle\Test\DataFixtures\AbstractFixture;
use Oro\Bundle\TestFrameworkBundle\Tests\Functional\DataFixtures\LoadOrganization;

class LoadProductCollectionContentVariants extends AbstractFixture implements DependentFixtureInterface
{
    public const PRODUCT_COLLECTION_TEST_VARIANT = 'product_collection_test_variant';
    public const PRODUCT_STATIC_SEGMENT = 'product_static_segment';
    public const TEST_VARIANT_WITHOUT_SEGMENT = 'test_variant_without_segment';
    public const TEST_VARIANT_WITH_TEST_SEGMENT_1 = 'test_segment_variant.1';
    public const TEST_VARIANT_WITH_TEST_SEGMENT_2 = 'test_segment_variant.2';
    public const TEST_VARIANT_WITH_TEST_SEGMENT_3 = 'test_segment_variant.3';

    #[\Override]
    public function getDependencies(): array
    {
        return [
            LoadProductData::class,
            LoadSegmentData::class,
            LoadContentNodeData::class,
            LoadOrganization::class
        ];
    }

    #[\Override]
    public function load(ObjectManager $manager): void
    {
        $this->createContentVariantWithProductSegment($manager);
        $this->createContentVariantWithoutSegment($manager);
        $this->createContentVariantWithTestSegment(
            $manager,
            self::TEST_VARIANT_WITH_TEST_SEGMENT_1,
            $this->getReference(LoadContentNodeData::FIRST_CONTENT_NODE),
            $this->getReference(LoadSegmentData::SEGMENT_DYNAMIC)
        );
        $this->createContentVariantWithTestSegment(
            $manager,
            self::TEST_VARIANT_WITH_TEST_SEGMENT_2,
            $this->getReference(LoadContentNodeData::FIRST_CONTENT_NODE),
            $this->getReference(LoadSegmentData::SEGMENT_DYNAMIC)
        );
        $this->createContentVariantWithTestSegment(
            $manager,
            self::TEST_VARIANT_WITH_TEST_SEGMENT_3,
            $this->getReference(LoadContentNodeData::SECOND_CONTENT_NODE),
            $this->getReference(LoadSegmentData::SEGMENT_STATIC)
        );

        $manager->flush();
    }

    private function createContentVariantWithProductSegment(ObjectManager $manager): void
    {
        $this->createProductSegment($manager);
        $this->createProductSegmentSnapshot($manager);
        $this->createCollectionSortOrder($manager);

        $testContentVariant = new TestContentVariant();
        $testContentVariant->setProductCollectionSegment($this->getReference(self::PRODUCT_STATIC_SEGMENT));
        $this->setReference(self::PRODUCT_COLLECTION_TEST_VARIANT, $testContentVariant);
        $manager->persist($testContentVariant);
    }

    private function createContentVariantWithoutSegment(ObjectManager $manager): void
    {
        $testContentVariant = new TestContentVariant();
        $this->setReference(self::TEST_VARIANT_WITHOUT_SEGMENT, $testContentVariant);
        $manager->persist($testContentVariant);
    }

    private function createCollectionSortOrder(ObjectManager $manager): void
    {
        $collectionSortOrder1 = new CollectionSortOrder();
        $collectionSortOrder1->setSegment($this->getReference(self::PRODUCT_STATIC_SEGMENT));
        $collectionSortOrder1->setProduct($this->getReference(LoadProductData::PRODUCT_1));
        $collectionSortOrder1->setSortOrder(0.1);
        $manager->persist($collectionSortOrder1);

        $collectionSortOrder2 = new CollectionSortOrder();
        $collectionSortOrder2->setSegment($this->getReference(self::PRODUCT_STATIC_SEGMENT));
        $collectionSortOrder2->setProduct($this->getReference(LoadProductData::PRODUCT_2));
        $collectionSortOrder2->setSortOrder(0.2);
        $manager->persist($collectionSortOrder2);
    }

    private function createProductSegment(ObjectManager $manager): void
    {
        /** @var Organization $organization */
        $organization = $this->getReference(LoadOrganization::ORGANIZATION);
        $owner = $organization->getBusinessUnits()->first();

        $segmentType = $manager->getRepository(SegmentType::class)->find(SegmentType::TYPE_STATIC);

        $entity = new Segment();
        $entity->setName('Product Static Segment');
        $entity->setDescription('Product static segment description.');
        $entity->setEntity(Product::class);
        $entity->setOwner($owner);
        $entity->setType($segmentType);
        $entity->setOrganization($organization);
        $entity->setDefinition(json_encode([
            'columns' => [
                ['func' => null, 'label' => 'Label', 'name' => 'id', 'sorting' => '']
            ],
            'filters' => []
        ], JSON_THROW_ON_ERROR));

        $this->setReference(self::PRODUCT_STATIC_SEGMENT, $entity);

        $manager->persist($entity);
    }

    private function createProductSegmentSnapshot(ObjectManager $manager): void
    {
        /** @var Segment $segment */
        $segment = $this->getReference(self::PRODUCT_STATIC_SEGMENT);

        /** @var Product[] $products */
        $products = $manager->getRepository($segment->getEntity())->findAll();

        $segmentSnapshot = new SegmentSnapshot($segment);

        foreach ($products as $product) {
            $snapshot = clone $segmentSnapshot;
            $snapshot->setIntegerEntityId($product->getId());

            $manager->persist($snapshot);
        }
    }

    private function createContentVariantWithTestSegment(
        ObjectManager $manager,
        string $reference,
        TestContentNode $node,
        ?Segment $segment = null
    ): void {
        $testContentVariant = new TestContentVariant();
        $testContentVariant->setProductCollectionSegment($segment);
        $testContentVariant->setNode($node);

        $manager->persist($testContentVariant);
        $this->setReference($reference, $testContentVariant);
    }
}
