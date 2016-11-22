<?php

namespace Oro\Bundle\ProductBundle\Tests\Functional\DataFixtures;

use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Common\Persistence\ObjectManager;

use Oro\Bundle\ProductBundle\Entity\ProductUnit;

class LoadProductUnits extends AbstractFixture
{
    const BOTTLE = 'product_unit.bottle';
    const LITER = 'product_unit.liter';
    const MILLILITER = 'product_unit.milliliter';
    const BOX = 'product_unit.box';

    /**
     * {@inheritdoc}
     */
    public function load(ObjectManager $manager)
    {
        $this->createProductUnit($manager, self::MILLILITER, 0);
        $this->createProductUnit($manager, self::LITER, 3);
        $this->createProductUnit($manager, self::BOTTLE, 0);
        $this->createProductUnit($manager, self::BOX, 0);

        $manager->flush();
    }

    /**
     * @param ObjectManager $manager
     * @param string $reference
     * @param int $precision
     * @return ProductUnit
     */
    protected function createProductUnit(ObjectManager $manager, $reference, $precision)
    {
        $productUnit = new ProductUnit();
        $productUnit->setCode(explode('.', $reference)[1]);
        $productUnit->setDefaultPrecision($precision);

        $manager->persist($productUnit);
        $this->addReference($reference, $productUnit);

        return $productUnit;
    }
}
