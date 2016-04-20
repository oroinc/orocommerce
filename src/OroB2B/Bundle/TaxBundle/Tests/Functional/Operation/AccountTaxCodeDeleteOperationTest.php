<?php

namespace OroB2B\Bundle\TaxBundle\Tests\Functional\Operation;

use Oro\Bundle\ActionBundle\Tests\Functional\ActionTestCase;

use OroB2B\Bundle\TaxBundle\Tests\Functional\DataFixtures\LoadAccountTaxCodes;

/**
 * @dbIsolation
 */
class AccountTaxCodeDeleteOperationTest extends ActionTestCase
{
    protected function setUp()
    {
        $this->initClient([], $this->generateBasicAuthHeader());

        $this->loadFixtures(
            [
                'OroB2B\Bundle\TaxBundle\Tests\Functional\DataFixtures\LoadAccountTaxCodes'
            ]
        );
    }

    public function testDelete()
    {
        $accountTaxCode = $this->getReference(LoadAccountTaxCodes::REFERENCE_PREFIX . '.' . LoadAccountTaxCodes::TAX_1);
        $accountTaxCodeId = $accountTaxCode->getId();

        $this->assertExecuteOperation(
            'DELETE',
            $accountTaxCodeId,
            $this->getContainer()->getParameter('orob2b_tax.entity.account_tax_code.class')
        );

        $this->assertEquals(
            [
                'success' => true,
                'message' => '',
                'messages' => [],
                'redirectUrl' => $this->getUrl('orob2b_tax_account_tax_code_index')
            ],
            json_decode($this->client->getResponse()->getContent(), true)
        );
    }
}
