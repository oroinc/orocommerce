<?php

namespace OroB2B\Bundle\TaxBundle\Tests\Unit\Entity;

use Oro\Component\Testing\Unit\EntityTestCaseTrait;

use OroB2B\Bundle\TaxBundle\Entity\TaxApply;
use OroB2B\Bundle\TaxBundle\Entity\TaxValue;

class TaxValueTest extends \PHPUnit_Framework_TestCase
{
    use EntityTestCaseTrait;

    public function testProperties()
    {
        $properties = [
            ['id', 1],
            ['totalIncludingTax', 20.1],
            ['totalExcludingTax', 20.2],
            ['shippingIncludingTax', 30.3],
            ['shippingExcludingTax', 30.4],
            ['entityClass', 'OroB2B\Bundle\SomeBundle\Entity\EntityClass'],
            ['entityId', 5],
            ['address', 'Kiev, SomeStreet str., 55'],
            ['totalTaxAmount', 40.5],
            ['shippingTaxAmount', 50.4],
            ['createdAt', new \DateTime()],
            ['updatedAt', new \DateTime()],
        ];

        $this->assertPropertyAccessors($this->createTaxValue(), $properties);
    }

    /**
     * Test TaxValue relations
     */
    public function testRelations()
    {
        $this->assertPropertyCollections($this->createTaxValue(), [
            ['appliedTaxes', new TaxApply()],
        ]);
    }

    public function testPreUpdate()
    {
        $taxValue = $this->createTaxValue();
        $taxValue->preUpdate();
        $this->assertInstanceOf('\DateTime', $taxValue->getUpdatedAt());
    }

    public function testPrePersist()
    {
        $taxValue = $this->createTaxValue();
        $taxValue->prePersist();
        $this->assertInstanceOf('\DateTime', $taxValue->getUpdatedAt());
        $this->assertInstanceOf('\DateTime', $taxValue->getCreatedAt());
    }

    /**
     * @return TaxValue
     */
    private function createTaxValue()
    {
        return new TaxValue();
    }
}
