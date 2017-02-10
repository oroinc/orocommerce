<?php

namespace Oro\Bundle\CustomerBundle\Tests\Unit\Entity;

use Oro\Component\Testing\Unit\EntityTestCaseTrait;
use Oro\Bundle\TaxBundle\Entity\CustomerTaxCode;
use Oro\Bundle\TaxBundle\Entity\ProductTaxCode;
use Oro\Bundle\TaxBundle\Entity\Tax;
use Oro\Bundle\TaxBundle\Entity\TaxJurisdiction;
use Oro\Bundle\TaxBundle\Entity\TaxRule;

class TaxRuleTest extends \PHPUnit_Framework_TestCase
{
    use EntityTestCaseTrait;

    /**
     * Test setters getters
     */
    public function testAccessors()
    {
        $this->assertPropertyAccessors($this->createTaxRuleEntity(), [
            ['id', 1],
            ['description', 'tax rule description'],
            ['productTaxCode', new ProductTaxCode()],
            ['customerTaxCode', new CustomerTaxCode()],
            ['tax', new Tax()],
            ['taxJurisdiction', new TaxJurisdiction()],
            ['createdAt', new \DateTime()],
            ['updatedAt', new \DateTime()],
        ]);
    }

    /**
     * @return TaxRule
     */
    protected function createTaxRuleEntity()
    {
        return new TaxRule();
    }
}
