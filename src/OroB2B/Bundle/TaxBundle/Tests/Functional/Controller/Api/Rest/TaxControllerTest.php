<?php

namespace OroB2B\Bundle\TaxBundle\Tests\Functional\Controller\Api\Rest;

use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;

use OroB2B\Bundle\TaxBundle\Tests\Functional\DataFixtures\LoadTaxes;

/**
 * @dbIsolation
 */
class TaxControllerTest extends WebTestCase
{
    protected function setUp()
    {
        $this->initClient([], $this->generateWsseAuthHeader());

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
        $this->client->request(
            'DELETE',
            $this->getUrl('orob2b_api_tax_delete_tax', ['id' => $taxId])
        );
        $result = $this->client->getResponse();
        $this->assertEmptyResponseStatusCodeEquals($result, 204);
    }
}
