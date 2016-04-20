<?php

namespace OroB2B\Bundle\TaxBundle\Tests\Functional\Operation;

use Oro\Bundle\ActionBundle\Tests\Functional\ActionTestCase;

use OroB2B\Bundle\TaxBundle\Tests\Functional\DataFixtures\LoadProductTaxCodes;

/**
 * @dbIsolation
 */
class ProductTaxCodeDeleteOperationTest extends ActionTestCase
{
    protected function setUp()
    {
        $this->initClient([], $this->generateBasicAuthHeader());

        $this->loadFixtures(
            [
                'OroB2B\Bundle\TaxBundle\Tests\Functional\DataFixtures\LoadProductTaxCodes'
            ]
        );
    }

    public function testDelete()
    {
        $productTaxCode = $this->getReference(LoadProductTaxCodes::REFERENCE_PREFIX . '.' . LoadProductTaxCodes::TAX_1);
        $productTaxCodeId = $productTaxCode->getId();

        $this->assertExecuteOperation(
            'DELETE',
            $productTaxCodeId,
            $this->getContainer()->getParameter('orob2b_tax.entity.product_tax_code.class')
        );

        $this->assertEquals(
            [
                'success' => true,
                'message' => '',
                'messages' => [],
                'redirectUrl' => $this->getUrl('orob2b_tax_product_tax_code_index')
            ],
            json_decode($this->client->getResponse()->getContent(), true)
        );
    }
}
