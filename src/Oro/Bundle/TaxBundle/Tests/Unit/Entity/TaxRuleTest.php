<?php

namespace Oro\Bundle\TaxBundle\Tests\Unit\Entity;

use Oro\Bundle\TaxBundle\Entity\CustomerTaxCode;
use Oro\Bundle\TaxBundle\Entity\ProductTaxCode;
use Oro\Bundle\TaxBundle\Entity\Tax;
use Oro\Bundle\TaxBundle\Entity\TaxJurisdiction;
use Oro\Bundle\TaxBundle\Entity\TaxRule;
use Oro\Component\Testing\Unit\EntityTestCaseTrait;

class TaxRuleTest extends \PHPUnit\Framework\TestCase
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
