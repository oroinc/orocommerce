<?php

namespace Oro\Bundle\TaxBundle\Tests\Unit\Provider;

use Oro\Bundle\EntityBundle\Provider\EntityNameProviderInterface;
use Oro\Bundle\TaxBundle\Entity\CustomerTaxCode;
use Oro\Bundle\TaxBundle\Entity\ProductTaxCode;
use Oro\Bundle\TaxBundle\Entity\Tax;
use Oro\Bundle\TaxBundle\Entity\TaxJurisdiction;
use Oro\Bundle\TaxBundle\Entity\TaxRule;
use Oro\Bundle\TaxBundle\Provider\TaxRuleEntityNameProvider;

class TaxRuleEntityNameProviderTest extends \PHPUnit\Framework\TestCase
{
    private TaxRuleEntityNameProvider $provider;

    #[\Override]
    protected function setUp(): void
    {
        $this->provider = new TaxRuleEntityNameProvider();
    }

    public function testGetNameForUnsupportedEntity(): void
    {
        $this->assertFalse(
            $this->provider->getName(EntityNameProviderInterface::FULL, 'en', new \stdClass())
        );
    }

    public function testGetName(): void
    {
        $tax = new Tax();
        $tax->setCode('TAX');
        $taxJurisdiction = new TaxJurisdiction();
        $taxJurisdiction->setCode('JURISDICTION');
        $customerTaxCode = new CustomerTaxCode();
        $customerTaxCode->setCode('CUSTOMER_TAX_CODE');
        $productTaxCode = new ProductTaxCode();
        $productTaxCode->setCode('PRODUCT_TAX_CODE');
        $taxRule = new TaxRule();
        $taxRule->setTax($tax);
        $taxRule->setTaxJurisdiction($taxJurisdiction);
        $taxRule->setCustomerTaxCode($customerTaxCode);
        $taxRule->setProductTaxCode($productTaxCode);

        $this->assertEquals(
            'TAX JURISDICTION PRODUCT_TAX_CODE CUSTOMER_TAX_CODE',
            $this->provider->getName(EntityNameProviderInterface::FULL, null, $taxRule)
        );
    }

    public function testGetNameDQLForUnsupportedEntity(): void
    {
        self::assertFalse(
            $this->provider->getNameDQL(EntityNameProviderInterface::FULL, 'en', \stdClass::class, 'entity')
        );
    }

    public function testGetNameDQL(): void
    {
        self::assertEquals(
            '(SELECT CONCAT(rule_t.code, \' \', rule_j.code, \' \', rule_pc.code, \' \', rule_cc.code)'
            . ' FROM Oro\Bundle\TaxBundle\Entity\TaxRule rule_r'
            . ' INNER JOIN rule_r.tax rule_t'
            . ' INNER JOIN rule_r.taxJurisdiction rule_j'
            . ' INNER JOIN rule_r.productTaxCode rule_pc'
            . ' INNER JOIN rule_r.customerTaxCode rule_cc'
            . ' WHERE rule_r = rule)',
            $this->provider->getNameDQL(EntityNameProviderInterface::FULL, null, TaxRule::class, 'rule')
        );
    }
}
