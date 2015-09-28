<?php

namespace OroB2B\Bundle\ProductBundle\Migrations\Data\ORM;

use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Common\Persistence\ObjectManager;

use OroB2B\Bundle\ProductBundle\Entity\ProductUnit;

class LoadProductUnitData extends AbstractFixture
{
    /**
     * @var array
     */
    protected $productUnits = [
        [
            'code'             => 'each',
            'defaultPrecision' => 0,
        ],
        [
            'code'             => 'hour',
            'defaultPrecision' => 0,
        ],
        [
            'code'             => 'item',
            'defaultPrecision' => 0,
        ],
        [
            'code'             => 'kg',
            'defaultPrecision' => 3,
        ],
        [
            'code'             => 'piece',
            'defaultPrecision' => 0,
        ],
        [
            'code'             => 'set',
            'defaultPrecision' => 0,
        ],
    ];

    /**
     * {@inheritdoc}
     */
    public function load(ObjectManager $manager)
    {
        foreach ($this->productUnits as $item) {
            $productUnit = new ProductUnit();
            $productUnit
                ->setCode($item['code'])
                ->setDefaultPrecision($item['defaultPrecision']);

            $manager->persist($productUnit);
        }

        if (!empty($this->productUnits)) {
            $manager->flush();
            $manager->clear();
        }
    }
}
