<?php

namespace Oro\Bundle\TaxBundle\Tests\Unit\Entity;

use Oro\Bundle\TaxBundle\Entity\CustomerTaxCode;
use Oro\Bundle\TaxBundle\Model\TaxCodeInterface;
use Oro\Component\Testing\Unit\EntityTestCaseTrait;

class CustomerTaxCodeTest extends \PHPUnit\Framework\TestCase
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

        $this->assertPropertyAccessors($this->createCustomerTaxCode(), $properties);
    }

    public function testToString()
    {
        $entity = new CustomerTaxCode();
        $this->assertEmpty((string)$entity);
        $entity->setCode('test');
        $this->assertEquals('test', (string)$entity);
    }

    /**
     * @return CustomerTaxCode
     */
    private function createCustomerTaxCode()
    {
        return new CustomerTaxCode();
    }

    public function testGetType()
    {
        $this->assertEquals(TaxCodeInterface::TYPE_ACCOUNT, $this->createCustomerTaxCode()->getType());
    }
}
