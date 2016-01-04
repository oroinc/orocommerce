<?php

namespace OroB2B\Bundle\TaxBundle\Tests\Unit\Entity;

use Oro\Component\Testing\Unit\EntityTestCaseTrait;

use OroB2B\Bundle\TaxBundle\Entity\Tax;
use OroB2B\Bundle\TaxBundle\Entity\TaxApply;

class TaxApplyTest extends \PHPUnit_Framework_TestCase
{
    use EntityTestCaseTrait;

    public function testProperties()
    {
        $properties = [
            ['id', 1],
            ['tax', new Tax()],
            ['rate', 20],
            ['taxAmount', 20],
            ['taxableAmount', 30],
            ['createdAt', new \DateTime()],
            ['updatedAt', new \DateTime()],
        ];

        $this->assertPropertyAccessors($this->createTaxApply(), $properties);
    }

    public function testPreUpdate()
    {
        $taxValue = $this->createTaxApply();
        $taxValue->preUpdate();
        $this->assertInstanceOf('\DateTime', $taxValue->getUpdatedAt());
    }

    public function testPrePersist()
    {
        $taxValue = $this->createTaxApply();
        $taxValue->prePersist();
        $this->assertInstanceOf('\DateTime', $taxValue->getUpdatedAt());
        $this->assertInstanceOf('\DateTime', $taxValue->getCreatedAt());
    }

    /**
     * @return TaxApply
     */
    private function createTaxApply()
    {
        return new TaxApply();
    }
}
