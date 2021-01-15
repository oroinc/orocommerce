<?php

namespace Oro\Bundle\CatalogBundle\Tests\Functional\DataFixtures;

use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;
use Oro\Bundle\CatalogBundle\Entity\Category;
use Oro\Bundle\FrontendTestFrameworkBundle\Entity\TestContentVariant;
use Oro\Bundle\TestFrameworkBundle\Test\DataFixtures\AbstractFixture;

class LoadCategoryContentVariants extends AbstractFixture implements DependentFixtureInterface
{
    /** {@inheritdoc} */
    public function getDependencies()
    {
        return [LoadCategoryProductData::class];
    }

    /** {@inheritdoc} */
    public function load(ObjectManager $manager)
    {
        $this->createTestContentVariant(
            $manager,
            'test_category_variant.1',
            $this->getReference(LoadCategoryData::FIRST_LEVEL)
        );
        $this->createTestContentVariant(
            $manager,
            'test_category_variant.2',
            $this->getReference(LoadCategoryData::SECOND_LEVEL1)
        );
        $this->createTestContentVariant(
            $manager,
            'test_category_variant.3',
            $this->getReference(LoadCategoryData::SECOND_LEVEL2)
        );
        $this->createTestContentVariant(
            $manager,
            'test_category_variant.4',
            $this->getReference(LoadCategoryData::FOURTH_LEVEL1)
        );
        $this->createTestContentVariant(
            $manager,
            'test_category_variant.5'
        );
        $this->createTestContentVariant(
            $manager,
            'test_category_variant.6'
        );

        $manager->flush();
    }

    /**
     * @param ObjectManager $manager
     * @param string $reference
     * @param Category $category
     */
    private function createTestContentVariant(ObjectManager $manager, $reference, Category $category = null)
    {
        $testContentVariant = new TestContentVariant();
        $testContentVariant->setCategoryPageCategory($category);

        $manager->persist($testContentVariant);
        $this->setReference($reference, $testContentVariant);
    }
}
