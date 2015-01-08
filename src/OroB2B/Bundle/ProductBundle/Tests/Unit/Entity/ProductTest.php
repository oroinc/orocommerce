<?php

namespace OroB2B\Bundle\ProductBundle\Tests\Unit\Entity;

use OroB2B\Bundle\ProductBundle\Entity\Product;

class ProductTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @dataProvider flatPropertiesDataProvider
     * @param string $property
     * @param mixed $value
     * @param mixed $expected
     */
    public function testGetSet($property, $value, $expected)
    {
        $product = new Product();

        $this->assertNull(call_user_func_array([$product, 'get' . ucfirst($property)], []));
        call_user_func_array(array($product, 'set' . ucfirst($property)), array($value));
        $this->assertEquals($expected, call_user_func_array([$product, 'get' . ucfirst($property)], []));
    }

    public function flatPropertiesDataProvider()
    {
        $now = new \DateTime('now');

        return [
            // 'id'        => ['id', 1, 1],
            'sku'       => ['sku', 'sku-test-01', 'sku-test-01'],
            'createdAt' => ['createdAt', $now, $now],
            'updatedAt' => ['updatedAt', $now, $now],
        ];
    }

    public function testPrePersist()
    {
        $product = new Product();
        $product->prePersist();
        $this->assertInstanceOf('\DateTime', $product->getCreatedAt());
    }

    public function testPreUpdate()
    {
        $product = new Product();
        $product->preUpdate();
        $this->assertInstanceOf('\DateTime', $product->getUpdatedAt());
    }
}
