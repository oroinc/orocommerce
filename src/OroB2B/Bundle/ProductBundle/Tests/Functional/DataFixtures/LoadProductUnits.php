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
        $this->createProductUnit($manager, 'kg');
        $this->createProductUnit($manager, 'item');

        $manager->flush();
    }

    /**
     * @param ObjectManager $manager
     * @param string $code
     * @return ProductUnit
     */
    protected function createProductUnit(ObjectManager $manager, $code)
    {
        $productUnit = new ProductUnit();
        $productUnit->setCode($code);

        $manager->persist($productUnit);
        $this->addReference('product_unit.' . $code, $productUnit);

        return $productUnit;
    }
}
