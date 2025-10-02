<?php

namespace Oro\Bundle\ProductBundle\Tests\Functional\Controller\Frontend;

use Oro\Bundle\FrontendTestFrameworkBundle\Migrations\Data\ORM\LoadCustomerUserData;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;

final class QuickAddValidationControllerTest extends WebTestCase
{
    protected function setUp(): void
    {
        $this->initClient(
            [],
            self::generateBasicAuthHeader(
                LoadCustomerUserData::AUTH_USER,
                LoadCustomerUserData::AUTH_PW
            )
        );
    }

    public function testValidateRowsAction(): void
    {
        $this->client->request('POST', $this->getUrl('oro_product_frontend_quick_add_validate_rows'), [
            'items' => [
                ['sku' => 'not_existing_sku', 'index' => 0]
            ],
        ]);

        self::assertResponseStatusCodeSame(200);
        $response = $this->client->getResponse();
        $responseData = self::getJsonResponseContent($response, 200);

        self::assertFalse($responseData['success']);
        self::assertCount(1, $responseData['items']);
        self::assertEquals(0, $responseData['items'][0]['index']);
    }
}
