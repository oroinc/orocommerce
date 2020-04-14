<?php

namespace Oro\Bundle\ProductBundle\Tests\Functional\Controller\Frontend;

use Oro\Bundle\FrontendTestFrameworkBundle\Migrations\Data\ORM\LoadCustomerUserData;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\ProductBundle\Entity\ProductUnit;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;

class AjaxProductUnitControllerTest extends WebTestCase
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

        $this->loadFixtures(['Oro\Bundle\ProductBundle\Tests\Functional\DataFixtures\LoadProductUnitPrecisions']);
    }

    /**
     * @param string $productReference
     * @param array $expectedData
     * @param bool $isShort
     *
     * @dataProvider productUnitsDataProvider
     */
    public function testProductUnitsAction($productReference, array $expectedData, $isShort = false)
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
        static::assertJsonResponseStatusCodeEquals($result, 200);

        $data = json_decode($result->getContent(), true);

        static::assertArrayHasKey('units', $data);
        static::assertEquals($expectedData, $data['units']);
    }

    /**
     * @return array
     */
    public function productUnitsDataProvider()
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

    /**
     * @param string $reference
     * @return Product
     */
    protected function getProduct($reference)
    {
        return $this->getReference($reference);
    }

    /**
     * @param string $reference
     * @return ProductUnit
     */
    protected function getProductUnit($reference)
    {
        return $this->getReference($reference);
    }
}
