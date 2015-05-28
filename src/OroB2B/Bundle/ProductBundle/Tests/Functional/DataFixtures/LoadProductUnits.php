<?php

namespace OroB2B\Bundle\ProductBundle\Tests\Functional\DataFixtures;

use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Common\Persistence\ObjectManager;

use OroB2B\Bundle\ProductBundle\Entity\ProductUnit;

class LoadProductUnits extends AbstractFixture
{
    /**
     * {@inheritdoc}
     */
    public function load(ObjectManager $manager)
    {
        $this->createProductUnit($manager, 'kg', 3);
        $this->createProductUnit($manager, 'item', 0);

        $manager->flush();
    }

    /**
     * @param ObjectManager $manager
     * @param string $code
     * @param int $precision
     * @return ProductUnit
     */
    protected function createProductUnit(ObjectManager $manager, $code, $precision)
    {
        $productUnit = new ProductUnit();
        $productUnit->setCode($code);
        $productUnit->setDefaultPrecision($precision);

        $manager->persist($productUnit);
        $this->addReference('product_unit.' . $code, $productUnit);

        return $productUnit;
    }
}
