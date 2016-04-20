<?php

namespace OroB2B\Bundle\TaxBundle\Tests\Functional\Operation;

use Oro\Bundle\ActionBundle\Tests\Functional\ActionTestCase;

use OroB2B\Bundle\TaxBundle\Tests\Functional\DataFixtures\LoadTaxes;
use OroB2B\Bundle\TaxBundle\Tests\Functional\DataFixtures\LoadTaxJurisdictions;

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
                'OroB2B\Bundle\TaxBundle\Tests\Functional\DataFixtures\LoadTaxJurisdictions'
            ]
        );
    }

    public function testDelete()
    {
        $taxJurisdiction = $this->getReference(
            LoadTaxJurisdictions::REFERENCE_PREFIX . '.' . LoadTaxes::TAX_1
        );
        $taxJurisdictionId = $taxJurisdiction->getId();

        $this->assertExecuteOperation(
            'DELETE',
            $taxJurisdictionId,
            $this->getContainer()->getParameter('orob2b_tax.entity.tax_jurisdiction.class')
        );

        $this->assertEquals(
            [
                'success' => true,
                'message' => '',
                'messages' => [],
                'redirectUrl' => $this->getUrl('orob2b_tax_jurisdiction_index')
            ],
            json_decode($this->client->getResponse()->getContent(), true)
        );
    }
}
