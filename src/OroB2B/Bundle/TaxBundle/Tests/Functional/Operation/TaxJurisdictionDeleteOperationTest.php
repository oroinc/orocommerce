<?php

namespace Oro\Bundle\TaxBundle\Tests\Functional\Operation;

use Oro\Bundle\ActionBundle\Tests\Functional\ActionTestCase;
use Oro\Bundle\TaxBundle\Tests\Functional\DataFixtures\LoadTaxes;
use Oro\Bundle\TaxBundle\Tests\Functional\DataFixtures\LoadTaxJurisdictions;

/**
 * @dbIsolation
 */
class TaxJurisdictionDeleteOperationTest extends ActionTestCase
{
    protected function setUp()
    {
        $this->initClient([], $this->generateBasicAuthHeader());

        $this->loadFixtures(
            [
                'Oro\Bundle\TaxBundle\Tests\Functional\DataFixtures\LoadTaxJurisdictions'
            ]
        );
    }

    public function testDelete()
    {
        $taxJurisdiction = $this->getReference(
            LoadTaxJurisdictions::REFERENCE_PREFIX . '.' . LoadTaxes::TAX_1
        );

        $this->assertDeleteOperation(
            $taxJurisdiction->getId(),
            'orob2b_tax.entity.tax_jurisdiction.class',
            'orob2b_tax_jurisdiction_index'
        );
    }
}
