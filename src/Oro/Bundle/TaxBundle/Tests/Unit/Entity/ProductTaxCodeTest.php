<?php

namespace Oro\Bundle\TaxBundle\Tests\Unit\Entity;

use Oro\Bundle\TaxBundle\Entity\ProductTaxCode;
use Oro\Bundle\TaxBundle\Model\TaxCodeInterface;
use Oro\Component\Testing\Unit\EntityTestCaseTrait;

class ProductTaxCodeTest extends \PHPUnit\Framework\TestCase
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

    public function testToString()
    {
        $entity = new ProductTaxCode();
        $this->assertEmpty((string)$entity);
        $entity->setCode('test');
        $this->assertEquals('test', (string)$entity);
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
