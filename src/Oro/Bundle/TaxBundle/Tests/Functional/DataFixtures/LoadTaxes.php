<?php

namespace Oro\Bundle\TaxBundle\Tests\Functional\DataFixtures;

use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Common\Persistence\ObjectManager;
use Oro\Bundle\TaxBundle\Entity\Tax;

class LoadTaxes extends AbstractFixture
{
    const TAX_1 = 'TAX1';
    const TAX_2 = 'TAX2';
    const TAX_3 = 'TAX3';
    const TAX_4 = 'TAX4';

    const DESCRIPTION_1 = 'Tax description 1';
    const DESCRIPTION_2 = 'Tax description 2';
    const DESCRIPTION_3 = 'Tax description 3';
    const DESCRIPTION_4 = 'Tax description 4';

    const RATE_1 = 0.104;
    const RATE_2 = 0.2;
    const RATE_3 = 0.075;
    const RATE_4 = 0.9;

    const REFERENCE_PREFIX = 'tax';

    /**
     * {@inheritdoc}
     */
    public function load(ObjectManager $manager)
    {
        $this->createTax($manager, self::TAX_1, self::DESCRIPTION_1, self::RATE_1);
        $this->createTax($manager, self::TAX_2, self::DESCRIPTION_2, self::RATE_2);
        $this->createTax($manager, self::TAX_3, self::DESCRIPTION_3, self::RATE_3);
        $this->createTax($manager, self::TAX_4, self::DESCRIPTION_4, self::RATE_4);

        $manager->flush();
    }

    /**
     * @param ObjectManager $manager
     * @param string        $code
     * @param string        $description
     * @param int           $rate
     * @return Tax
     */
    protected function createTax(ObjectManager $manager, $code, $description, $rate)
    {
        $tax = new Tax();
        $tax->setCode($code);
        $tax->setDescription($description);
        $tax->setRate($rate);

        $manager->persist($tax);
        $this->addReference(self::REFERENCE_PREFIX . '.' . $code, $tax);

        return $tax;
    }
}
