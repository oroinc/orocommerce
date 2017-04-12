<?php

namespace Oro\Bundle\ProductBundle\Tests\Functional\DataFixtures;

use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Common\Persistence\ObjectManager;

use Oro\Bundle\FrontendTestFrameworkBundle\Entity\TestContentVariant;
use Oro\Bundle\SegmentBundle\Entity\Segment;
use Oro\Bundle\SegmentBundle\Tests\Functional\DataFixtures\LoadSegmentData;
use Oro\Bundle\TestFrameworkBundle\Test\DataFixtures\AbstractFixture;

class LoadSegmentContentVariants extends AbstractFixture implements DependentFixtureInterface
{
    /**
     * {@inheritdoc}
     */
    public function getDependencies()
    {
        return [LoadSegmentData::class];
    }

    /**
     * {@inheritdoc}
     */
    public function load(ObjectManager $manager)
    {
        $this->createTestContentVariant($manager, 'test_segment_variant.1', $this->getReference('segment_dynamic'));
        $this->createTestContentVariant($manager, 'test_segment_variant.2', $this->getReference('segment_dynamic'));
        $this->createTestContentVariant($manager, 'test_segment_variant.3', $this->getReference('segment_static'));

        $manager->flush();
    }

    /**
     * @param ObjectManager $manager
     * @param string $reference
     * @param Segment|null $segment
     */
    private function createTestContentVariant(ObjectManager $manager, $reference, Segment $segment = null)
    {
        $testContentVariant = new TestContentVariant();
        $testContentVariant->setProductCollectionSegment($segment);

        $manager->persist($testContentVariant);
        $this->setReference($reference, $testContentVariant);
    }
}
