<?php

namespace Oro\Bundle\ProductBundle\Tests\Functional\DataFixtures;

use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;
use Oro\Bundle\FrontendTestFrameworkBundle\Entity\TestContentNode;
use Oro\Bundle\FrontendTestFrameworkBundle\Entity\TestContentVariant;
use Oro\Bundle\FrontendTestFrameworkBundle\Entity\TestWebCatalog;
use Oro\Bundle\SegmentBundle\Entity\Segment;
use Oro\Bundle\TestFrameworkBundle\Test\DataFixtures\AbstractFixture;

class LoadContentVariantSegmentsWithRelationsData extends AbstractFixture implements DependentFixtureInterface
{
    /**
     * {@inheritdoc}
     */
    public function getDependencies()
    {
        return [
            LoadWebCatalogsData::class,
            LoadSegmentsWithRelationsData::class
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function load(ObjectManager $manager)
    {
        $this->createTestContentVariant(
            $manager,
            $this->getWebCatalog(LoadWebCatalogsData::FIRST_WEB_CATALOG),
            $this->getReference(LoadSegmentsWithRelationsData::FIRST_SEGMENT)
        );

        $this->createTestContentVariant(
            $manager,
            $this->getWebCatalog(LoadWebCatalogsData::FIRST_WEB_CATALOG),
            $this->getReference(LoadSegmentsWithRelationsData::SECOND_SEGMENT)
        );

        $this->createTestContentVariant(
            $manager,
            $this->getWebCatalog(LoadWebCatalogsData::SECOND_WEB_CATALOG),
            $this->getReference(LoadSegmentsWithRelationsData::THIRD_SEGMENT)
        );

        $this->createTestContentVariant(
            $manager,
            $this->getWebCatalog(LoadWebCatalogsData::FIRST_WEB_CATALOG),
            $this->getReference(LoadSegmentsWithRelationsData::NO_RELATIONS_SEGMENT)
        );

        $this->createTestContentVariant(
            $manager,
            $this->getWebCatalog(LoadWebCatalogsData::FIRST_WEB_CATALOG),
            $this->getReference(LoadSegmentsWithRelationsData::WITH_CRITERIA_SEGMENT)
        );

        $manager->flush();
    }

    private function createTestContentVariant(
        ObjectManager $manager,
        TestWebCatalog $webCatalog,
        Segment $segment = null
    ) {
        $contentNode = new TestContentNode();
        $contentNode->setWebCatalog($webCatalog);

        $testContentVariant = new TestContentVariant();
        $testContentVariant->setProductCollectionSegment($segment);
        $testContentVariant->setNode($contentNode);

        $manager->persist($contentNode);
        $manager->persist($testContentVariant);
    }

    /**
     * @param string $reference
     * @return TestWebCatalog
     */
    private function getWebCatalog($reference)
    {
        /** @var TestWebCatalog $webCatalog */
        $webCatalog = $this->getReference($reference);

        return $webCatalog;
    }
}
