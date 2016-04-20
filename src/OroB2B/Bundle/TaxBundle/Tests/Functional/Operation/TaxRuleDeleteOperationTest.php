<?php

namespace OroB2B\Bundle\TaxBundle\Tests\Functional\Operation;

use Oro\Bundle\ActionBundle\Tests\Functional\ActionTestCase;

use OroB2B\Bundle\TaxBundle\Tests\Functional\DataFixtures\LoadTaxRules;

/**
 * @dbIsolation
 */
class TaxRuleDeleteOperationTest extends ActionTestCase
{
    protected function setUp()
    {
        $this->initClient([], $this->generateBasicAuthHeader());

        $this->loadFixtures(
            [
                'OroB2B\Bundle\TaxBundle\Tests\Functional\DataFixtures\LoadTaxRules'
            ]
        );
    }

    public function testDelete()
    {
        $taxRule = $this->getReference(
            LoadTaxRules::REFERENCE_PREFIX . '.' . LoadTaxRules::TAX_RULE_1
        );

        $this->assertDeleteOperation($taxRule->getId(), 'orob2b_tax.entity.tax_rule.class', 'orob2b_tax_rule_index');
    }
}
