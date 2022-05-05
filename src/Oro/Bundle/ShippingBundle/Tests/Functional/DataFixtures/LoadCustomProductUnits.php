<?php

namespace Oro\Bundle\ShippingBundle\Tests\Functional\DataFixtures;

use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Persistence\ObjectManager;
use Oro\Bundle\ProductBundle\Entity\ProductUnit;

class LoadCustomProductUnits extends AbstractFixture
{
    const WITH_SPECIAL_CHAR = 'product_unit.mètre';

    /**
     * {@inheritdoc}
     */
    public function load(ObjectManager $manager)
    {
        $this->createProductUnit($manager, self::WITH_SPECIAL_CHAR, 0);

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
