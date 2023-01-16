<?php

namespace Oro\Bundle\ProductBundle\Tests\Functional\Controller;

use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\ProductBundle\Tests\Functional\DataFixtures\LoadProductUnitPrecisions;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;

class AjaxProductUnitControllerTest extends WebTestCase
{
    protected function setUp(): void
    {
        $this->initClient([], $this->generateBasicAuthHeader());
        $this->client->useHashNavigation(true);
        $this->loadFixtures([LoadProductUnitPrecisions::class]);
    }

    public function testGetAllProductUnitsAction()
    {
        $this->client->request('GET', $this->getUrl('oro_product_unit_all_product_units'));

        $result = $this->client->getResponse();
        $this->assertJsonResponseStatusCodeEquals($result, 200);

        $data = self::jsonToArray($result->getContent());

        $this->assertArrayHasKey('units', $data);
        $this->assertArrayHasKey('bottle', $data['units']);
        $this->assertArrayHasKey('box', $data['units']);
        $this->assertArrayHasKey('liter', $data['units']);
    }

    /**
     * @dataProvider productUnitsDataProvider
     */
    public function testGetProductUnitsAction(string $productReference, array $expectedData, bool $isShort = false)
    {
        $product = $this->getProduct($productReference);

        $this->client->request(
            'GET',
            $this->getUrl('oro_product_unit_product_units', ['id' => $product->getId(), 'short' => $isShort])
        );

        $result = $this->client->getResponse();
        $this->assertJsonResponseStatusCodeEquals($result, 200);

        $data = self::jsonToArray($result->getContent());

        $this->assertArrayHasKey('units', $data);
        $this->assertEquals($expectedData, $data['units']);
    }

    public function productUnitsDataProvider(): array
    {
        return [
            'product-1' => [
                'product-1',
                [
                    'bottle' => 2,
                    'liter' => 3,
                    'milliliter' => 0,
                ],
                false
            ],
            'product-2' => [
                'product-2',
                [
                    'bottle' => 1,
                    'box' => 1,
                    'liter' => 3,
                    'milliliter' => 0,
                ],
                false
            ],
            'product-1 short label' => [
                'product-1',
                [
                    'bottle' => 2,
                    'liter' => 3,
                    'milliliter' => 0,
                ],
                true
            ],
            'product-2 short label' => [
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
