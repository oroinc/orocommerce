<?php

namespace OroB2B\Bundle\TaxBundle\Tests\Functional\Controller\Api\Rest;

use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;

use OroB2B\Bundle\TaxBundle\Tests\Functional\DataFixtures\LoadProductTaxCodes;

/**
 * @dbIsolation
 */
class ProductCodeControllerTest extends WebTestCase
{
    protected function setUp()
    {
        $this->initClient([], $this->generateWsseAuthHeader());

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
        $this->client->request(
            'DELETE',
            $this->getUrl('orob2b_api_tax_delete_producttaxcode', ['id' => $productTaxCodeId])
        );
        $result = $this->client->getResponse();
        $this->assertEmptyResponseStatusCodeEquals($result, 204);
    }
}
