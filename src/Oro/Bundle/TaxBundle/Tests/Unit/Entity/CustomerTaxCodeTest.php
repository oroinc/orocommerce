<?php

namespace Oro\Bundle\TaxBundle\Tests\Unit\Entity;

use Oro\Component\Testing\Unit\EntityTestCaseTrait;
use Oro\Bundle\CustomerBundle\Entity\Customer;
use Oro\Bundle\CustomerBundle\Entity\CustomerGroup;
use Oro\Bundle\TaxBundle\Entity\CustomerTaxCode;
use Oro\Bundle\TaxBundle\Model\TaxCodeInterface;

class CustomerTaxCodeTest extends \PHPUnit_Framework_TestCase
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

    /**
     * Test CustomerTaxCode relations
     */
    public function testRelations()
    {
        $this->assertPropertyCollections($this->createCustomerTaxCode(), [
            ['customers', new Customer()],
            ['customerGroups', new CustomerGroup()],
        ]);
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
