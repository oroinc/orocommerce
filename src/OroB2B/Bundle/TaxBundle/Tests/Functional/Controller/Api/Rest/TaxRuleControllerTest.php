<?php

namespace OroB2B\Bundle\TaxBundle\Tests\Functional\Controller\Api\Rest;

use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;

use OroB2B\Bundle\TaxBundle\Tests\Functional\DataFixtures\LoadTaxRules;

/**
 * @dbIsolation
 */
class TaxRuleControllerTest extends WebTestCase
{
    protected function setUp()
    {
        $this->initClient([], $this->generateWsseAuthHeader());

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
        $taxRuleId = $taxRule->getId();
        $this->client->request(
            'DELETE',
            $this->getUrl('orob2b_api_tax_delete_taxrule', ['id' => $taxRuleId])
        );
        $result = $this->client->getResponse();
        $this->assertEmptyResponseStatusCodeEquals($result, 204);
    }
}
