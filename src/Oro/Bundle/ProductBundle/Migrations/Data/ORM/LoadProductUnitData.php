<?php

namespace Oro\Bundle\ProductBundle\Migrations\Data\ORM;

use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Persistence\ObjectManager;
use Oro\Bundle\ProductBundle\Entity\ProductUnit;

/**
 * Loads default product units into the database.
 *
 * This fixture creates the standard set of product units (each, hour, item, kg, piece, set)
 * with their default precision settings, providing the foundation for product unit management.
 */
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

    #[\Override]
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
