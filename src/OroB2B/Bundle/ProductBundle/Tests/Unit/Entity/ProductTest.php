<?php

namespace OroB2B\Bundle\ProductBundle\Tests\Unit\Entity;

use Oro\Bundle\OrganizationBundle\Entity\Organization;
use Oro\Bundle\UserBundle\Entity\User;
use OroB2B\Bundle\ProductBundle\Entity\Product;

class ProductTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @dataProvider flatPropertiesDataProvider
     * @param string $property
     * @param mixed $value
     */
    public function testGetSet($property, $value)
    {
        $product = new Product();

        $this->assertNull(call_user_func_array([$product, 'get' . ucfirst($property)], []));
        call_user_func_array(array($product, 'set' . ucfirst($property)), array($value));
        $this->assertEquals($value, call_user_func_array([$product, 'get' . ucfirst($property)], []));
    }

    public function flatPropertiesDataProvider()
    {
        $now = new \DateTime('now');

        return [
            'sku'          => ['sku', 'sku-test-01'],
            'owner'        => ['owner', new User()],
            'organization' => ['organization', new Organization()],
            'createdAt'    => ['createdAt', $now],
            'updatedAt'    => ['updatedAt', $now],
        ];
    }

    public function testGetId()
    {
        $productId = 123;
        $product = new Product();
        $this->assertNull($product->getId());

        $class = new \ReflectionClass($product);
        $prop = $class->getProperty('id');
        $prop->setAccessible(true);
        $prop->setValue($product, $productId);

        $this->assertEquals($productId, $product->getId());
    }

    public function testPrePersist()
    {
        $product = new Product();
        $product->prePersist();
        $this->assertInstanceOf('\DateTime', $product->getCreatedAt());
        $this->assertInstanceOf('\DateTime', $product->getUpdatedAt());
    }

    public function testPreUpdate()
    {
        $product = new Product();
        $product->preUpdate();
        $this->assertInstanceOf('\DateTime', $product->getUpdatedAt());
    }
}
