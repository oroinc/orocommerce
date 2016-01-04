<?php

namespace OroB2B\Bundle\TaxBundle\Tests\Unit\Entity;

use Oro\Component\Testing\Unit\EntityTestCaseTrait;

use OroB2B\Bundle\TaxBundle\Entity\TaxApply;
use OroB2B\Bundle\TaxBundle\Entity\TaxItemValue;

class TaxItemValueTest extends \PHPUnit_Framework_TestCase
{
    use EntityTestCaseTrait;

    public function testProperties()
    {
        $properties = [
            ['id', 1],
            ['unitPriceIncludingTax', 20],
            ['unitPriceExcludingTax', 30],
            ['unitPriceTaxAmount', 40],
            ['unitPriceAdjustment', 50],
            ['rowTotalIncludingTax', 60],
            ['rowTotalExcludingTax', 70],
            ['rowTotalTaxAmount', 80],
            ['rowTotalAdjustment', 90],
            ['entityClass', 'OroB2B\Bundle\SomeBundle\Entity\EntityClass'],
            ['entityId', 5],
            ['address', 'Kiev, SomeStreet str., 55'],
            ['createdAt', new \DateTime()],
            ['updatedAt', new \DateTime()],
        ];

        $this->assertPropertyAccessors($this->createTaxItemValue(), $properties);
    }

    public function testRelations()
    {
        $this->assertPropertyCollections($this->createTaxItemValue(), [
            ['appliedTaxes', new TaxApply()],
        ]);
    }

    public function testPreUpdate()
    {
        $taxValue = $this->createTaxItemValue();
        $taxValue->preUpdate();
        $this->assertInstanceOf('\DateTime', $taxValue->getUpdatedAt());
    }

    public function testPrePersist()
    {
        $taxValue = $this->createTaxItemValue();
        $taxValue->prePersist();
        $this->assertInstanceOf('\DateTime', $taxValue->getUpdatedAt());
        $this->assertInstanceOf('\DateTime', $taxValue->getCreatedAt());
    }

    /**
     * @return TaxItemValue
     */
    private function createTaxItemValue()
    {
        return new TaxItemValue();
    }
}
