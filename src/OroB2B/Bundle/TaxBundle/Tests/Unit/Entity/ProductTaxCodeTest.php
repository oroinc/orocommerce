<?php

namespace OroB2B\Bundle\TaxBundle\Tests\Unit\Entity;

use Oro\Component\Testing\Unit\EntityTestCaseTrait;

use OroB2B\Bundle\ProductBundle\Entity\Product;
use OroB2B\Bundle\TaxBundle\Entity\ProductTaxCode;

class ProductTaxCodeTest extends \PHPUnit_Framework_TestCase
{
    use EntityTestCaseTrait;

    public function testProperties()
    {
        $properties = [
            ['id', '1'],
            ['code', 'fr4a'],
            ['description', 'description'],
            ['product', new Product()],
            ['createdAt', new \DateTime()],
            ['updatedAt', new \DateTime()],
        ];

        $this->assertPropertyAccessors($this->createProductTaxCode(), $properties);
    }

    public function testToString()
    {
        $entity = new ProductTaxCode();
        $this->assertEmpty((string)$entity);
        $entity->setCode('test');
        $this->assertEquals('test', (string)$entity);
    }

    public function testPreUpdate()
    {
        $productTaxCode = $this->createProductTaxCode();
        $productTaxCode->preUpdate();
        $this->assertInstanceOf('\DateTime', $productTaxCode->getUpdatedAt());
    }

    public function testPrePersist()
    {
        $productTaxCode = $this->createProductTaxCode();
        $productTaxCode->prePersist();
        $this->assertInstanceOf('\DateTime', $productTaxCode->getUpdatedAt());
        $this->assertInstanceOf('\DateTime', $productTaxCode->getCreatedAt());
    }

    /**
     * @return ProductTaxCode
     */
    private function createProductTaxCode()
    {
        return new ProductTaxCode();
    }
}
