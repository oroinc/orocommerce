<?php

namespace OroB2B\Bundle\CatalogBundle\Tests\Functional\DataFixtures;

use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Common\Persistence\ObjectManager;

use OroB2B\Bundle\CatalogBundle\Entity\CategoryUnitPrecision;

class LoadCategoryUnitPrecisionData extends AbstractFixture implements DependentFixtureInterface
{
    /**
     * @var array
     */
    protected static $relations = [
        LoadCategoryData::FIRST_LEVEL => null,
        LoadCategoryData::SECOND_LEVEL1 => 'product_unit.box',
        LoadCategoryData::THIRD_LEVEL1 => null,
        LoadCategoryData::FOURTH_LEVEL1 => null,
    ];

    /** {@inheritdoc} */
    public function getDependencies()
    {
        return [
            __NAMESPACE__ . '\LoadCategoryData',
            'OroB2B\Bundle\ProductBundle\Tests\Functional\DataFixtures\LoadProductUnits',
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function load(ObjectManager $manager)
    {
        foreach (self::$relations as $categoryReference => $productUnitReference) {
            if ($productUnitReference) {
                $categoryUnitPrecision = new CategoryUnitPrecision();
                $categoryUnitPrecision->setUnit($this->getReference($productUnitReference))->setPrecision(0);
                $this->getReference($categoryReference)->setUnitPrecision($categoryUnitPrecision);
            }
        }

        $manager->flush();
    }
}
