<?php

namespace Oro\Bundle\TaxBundle\Tests\Unit\Entity;

use Oro\Component\Testing\Unit\EntityTestCaseTrait;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\TaxBundle\Entity\ProductTaxCode;
use Oro\Bundle\TaxBundle\Model\TaxCodeInterface;

class ProductTaxCodeTest extends \PHPUnit_Framework_TestCase
{
    use EntityTestCaseTrait;

    public function testProperties()
    {
        $properties = [
            ['id', 1],
            ['code', 'fr4a'],
            ['description', 'description'],
            ['createdAt', new \DateTime()],
            ['updatedAt', new \DateTime()],
        ];

        $this->assertPropertyAccessors($this->createProductTaxCode(), $properties);
    }

    /**
     * Test ProductTaxCode relations
     */
    public function testRelations()
    {
        $this->assertPropertyCollections($this->createProductTaxCode(), [
            ['products', new Product()],
        ]);
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

    public function testGetType()
    {
        $this->assertEquals(TaxCodeInterface::TYPE_PRODUCT, $this->createProductTaxCode()->getType());
    }
}
