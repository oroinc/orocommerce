<?php

namespace Oro\Bundle\CustomerBundle\Tests\Unit\Entity;

use Oro\Component\Testing\Unit\EntityTestCaseTrait;
use Oro\Bundle\TaxBundle\Entity\AccountTaxCode;
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
            ['accountTaxCode', new AccountTaxCode()],
            ['tax', new Tax()],
            ['taxJurisdiction', new TaxJurisdiction()],
            ['createdAt', new \DateTime()],
            ['updatedAt', new \DateTime()],
        ]);
    }

    public function testPrePersist()
    {
        $tax = $this->createTaxRuleEntity();
        $tax->prePersist();
        $this->assertInstanceOf('DateTime', $tax->getCreatedAt());
        $this->assertInstanceOf('DateTime', $tax->getUpdatedAt());
    }

    public function testPreUpdate()
    {
        $tax = $this->createTaxRuleEntity();
        $tax->preUpdate();
        $this->assertInstanceOf('DateTime', $tax->getUpdatedAt());
    }

    /**
     * @return Tax
     */
    protected function createTaxRuleEntity()
    {
        return new TaxRule();
    }
}
