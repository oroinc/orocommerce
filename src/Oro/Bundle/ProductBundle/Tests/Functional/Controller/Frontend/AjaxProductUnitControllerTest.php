<?php

namespace Oro\Bundle\ProductBundle\Tests\Functional\Controller\Frontend;

use Oro\Bundle\FrontendTestFrameworkBundle\Migrations\Data\ORM\LoadCustomerUserData;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\ProductBundle\Tests\Functional\DataFixtures\LoadProductUnitPrecisions;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;

class AjaxProductUnitControllerTest extends WebTestCase
{
    protected function setUp(): void
    {
        $this->initClient(
            [],
            $this->generateBasicAuthHeader(LoadCustomerUserData::AUTH_USER, LoadCustomerUserData::AUTH_PW)
        );
        $this->loadFixtures([LoadProductUnitPrecisions::class]);
    }

    /**
     * @dataProvider productUnitsDataProvider
     */
    public function testProductUnitsAction(string $productReference, array $expectedData, bool $isShort = false)
    {
        $product = $this->getProduct($productReference);

        $this->client->request(
            'GET',
            $this->getUrl('oro_product_frontend_ajaxproductunit_productunits', [
                'id' => $product->getId(),
                'short' => $isShort
            ])
        );

        $result = $this->client->getResponse();
        self::assertJsonResponseStatusCodeEquals($result, 200);

        $data = self::jsonToArray($result->getContent());

        self::assertArrayHasKey('units', $data);
        self::assertEquals($expectedData, $data['units']);
    }

    public function productUnitsDataProvider(): array
    {
        return [
            'product-1 full' => [
                'product-1',
                [
                    'bottle' => 2,
                    'liter' => 3,
                    'milliliter' => 0,
                ],
                false
            ],
            'product-2 full' => [
                'product-2',
                [
                    'bottle' => 1,
                    'box' => 1,
                    'liter' => 3,
                    'milliliter' => 0,
                ],
                false
            ],
            'product-1 short' => [
                'product-1',
                [
                    'bottle' => 2,
                    'liter' => 3,
                    'milliliter' => 0,
                ],
                true
            ],
            'product-2 short' => [
                'product-2',
                [
                    'bottle' => 1,
                    'box' => 1,
                    'liter' => 3,
                    'milliliter' => 0,
                ],
                true
            ]
        ];
    }

    private function getProduct(string $reference): Product
    {
        return $this->getReference($reference);
    }
}
