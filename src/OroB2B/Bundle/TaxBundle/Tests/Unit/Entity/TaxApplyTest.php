<?php

namespace OroB2B\Bundle\TaxBundle\Tests\Unit\Entity;

use Oro\Component\Testing\Unit\EntityTestCaseTrait;

use OroB2B\Bundle\TaxBundle\Entity\Tax;
use OroB2B\Bundle\TaxBundle\Entity\TaxApply;
use OroB2B\Bundle\TaxBundle\Entity\TaxValue;

class TaxApplyTest extends \PHPUnit_Framework_TestCase
{
    use EntityTestCaseTrait;

    public function testProperties()
    {
        $properties = [
            ['id', 1],
            ['tax', new Tax()],
            ['taxValue', new TaxValue()],
            ['rate', 20],
            ['taxAmount', 20],
            ['taxableAmount', 30],
            ['createdAt', new \DateTime()],
            ['updatedAt', new \DateTime()],
        ];

        $this->assertPropertyAccessors($this->createTaxApply(), $properties);
    }

    /**
     * @return TaxApply
     */
    private function createTaxApply()
    {
        return new TaxApply();
    }
}
