<?php

namespace OroB2B\Bundle\ProductBundle\Tests\UnitProvider;

use OroB2B\Bundle\ProductBundle\Provider\ProductUnitsProvider;
use OroB2B\Bundle\ProductBundle\Entity\Product;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

class ProductUnitsProviderTest extends KernelTestCase
{
    /** @var ProductUnitsProvider $productUnitsProvider */
    protected $productUnitsProvider;

    public function setup()
    {
        self::bootKernel();
        $em = static::$kernel->getContainer()
            ->get('doctrine')
            ->getManager();

        $this->productUnitsProvider = new ProductStatusProvider($em);
    }

    public function testGetAvailableProductStatus()
    {
        $expected = [
            'each' => 'product_unit.each.label.full',
            'kg' => 'product_unit.kg.label.full',
            'hour' => 'product_unit.hour.label.full',
            'item' => 'product_unit.item.label.full',
            'set' => 'product_unit.set.label.full',
            'piece' => 'product_unit.piece.label.full',

        ];

        $this->assertEquals($expected, $this->productUnitsProvider->getAvailableProductUnits());
    }
}
