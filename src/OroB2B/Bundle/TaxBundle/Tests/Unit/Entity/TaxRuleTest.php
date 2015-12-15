<?php

namespace OroB2B\Bundle\AccountBundle\Tests\Unit\Entity;

use Oro\Component\Testing\Unit\EntityTestCaseTrait;

use OroB2B\Bundle\TaxBundle\Entity\AccountTaxCode;
use OroB2B\Bundle\TaxBundle\Entity\ProductTaxCode;
use OroB2B\Bundle\TaxBundle\Entity\Tax;
use OroB2B\Bundle\TaxBundle\Entity\TaxJurisdiction;
use OroB2B\Bundle\TaxBundle\Entity\TaxRule;

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
