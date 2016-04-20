<?php

namespace OroB2B\Bundle\TaxBundle\Tests\Functional\Operation;

use Oro\Bundle\ActionBundle\Tests\Functional\ActionTestCase;

use OroB2B\Bundle\TaxBundle\Tests\Functional\DataFixtures\LoadTaxes;

/**
 * @dbIsolation
 */
class TaxDeleteOperationTest extends ActionTestCase
{
    protected function setUp()
    {
        $this->initClient([], $this->generateBasicAuthHeader());

        $this->loadFixtures(
            [
                'OroB2B\Bundle\TaxBundle\Tests\Functional\DataFixtures\LoadTaxes'
            ]
        );
    }

    public function testDelete()
    {
        $tax = $this->getReference(LoadTaxes::REFERENCE_PREFIX . '.' . LoadTaxes::TAX_1);
        $taxId = $tax->getId();

        $this->assertExecuteOperation(
            'DELETE',
            $taxId,
            $this->getContainer()->getParameter('orob2b_tax.entity.tax.class')
        );

        $this->assertEquals(
            [
                'success' => true,
                'message' => '',
                'messages' => [],
                'redirectUrl' => $this->getUrl('orob2b_tax_index')
            ],
            json_decode($this->client->getResponse()->getContent(), true)
        );
    }
}
