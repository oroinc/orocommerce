<?php

namespace Oro\Bundle\ProductBundle\Tests\Functional\Acl\Voter;

use Oro\Bundle\FrontendTestFrameworkBundle\Migrations\Data\ORM\LoadCustomerUserData;
use Oro\Bundle\FrontendTestFrameworkBundle\Test\FrontendWebTestCase;
use Oro\Bundle\ProductBundle\Tests\Functional\DataFixtures\LoadProductData;

/**
 * @group CommunityEdition
 */
class ProductStatusVoterTest extends FrontendWebTestCase
{
    /**
     * {@inheritdoc}
     */
    protected function setUp(): void
    {
        $this->initClient(
            [],
            $this->generateBasicAuthHeader(LoadCustomerUserData::AUTH_USER, LoadCustomerUserData::AUTH_PW)
        );
        $this->setCurrentWebsite('default');
        $this->loadFixtures([LoadProductData::class]);
    }

    /**
     * @dataProvider statusDataProvider
     */
    public function testStatus(string $productReference, int $expectedCode)
    {
        $product = $this->getReference($productReference);
        $this->client->request(
            'GET',
            $this->getUrl('oro_product_frontend_product_view', ['id' => $product->getId()])
        );

        $response = $this->client->getResponse();
        $this->assertSame($response->getStatusCode(), $expectedCode);
    }

    public function statusDataProvider(): array
    {
        return [
            'enabled product' => [
                'productReference' => LoadProductData::PRODUCT_1,
                'expectedCode' => 200,
            ],
            'disabled product' => [
                'productReference' => LoadProductData::PRODUCT_5,
                'expectedCode' => 404,
            ],
        ];
    }
}
