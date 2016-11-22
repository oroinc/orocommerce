<?php

namespace Oro\Bundle\TaxBundle\Tests\Functional\Operation;

use Oro\Bundle\ActionBundle\Tests\Functional\ActionTestCase;
use Oro\Bundle\TaxBundle\Tests\Functional\DataFixtures\LoadAccountTaxCodes;

/**
 * @dbIsolation
 */
class AccountTaxCodeDeleteOperationTest extends ActionTestCase
{
    protected function setUp()
    {
        $this->initClient([], $this->generateBasicAuthHeader());
        $this->client->useHashNavigation(true);

        $this->loadFixtures(
            [
                'Oro\Bundle\TaxBundle\Tests\Functional\DataFixtures\LoadAccountTaxCodes'
            ]
        );
    }

    public function testDelete()
    {
        $accountTaxCode = $this->getReference(LoadAccountTaxCodes::REFERENCE_PREFIX . '.' . LoadAccountTaxCodes::TAX_1);

        $this->assertDeleteOperation(
            $accountTaxCode->getId(),
            'oro_tax.entity.account_tax_code.class',
            'oro_tax_account_tax_code_index'
        );
    }
}
