<?php

namespace Oro\Bundle\PricingBundle\Tests\Functional\Controller\Frontend;

use Oro\Bundle\FrontendTestFrameworkBundle\Migrations\Data\ORM\LoadCustomerUserData;
use Oro\Bundle\FrontendTestFrameworkBundle\Test\Client;
use Oro\Bundle\PricingBundle\Entity\CombinedProductPrice;
use Oro\Bundle\PricingBundle\Tests\Functional\DataFixtures\LoadCombinedProductPrices;
use Oro\Bundle\ProductBundle\Tests\Functional\DataFixtures\LoadProductData;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;

class ProductPriceTest extends WebTestCase
{
    /**
     * @var Client
     */
    protected $client;

    protected function setUp(): void
    {
        $this->initClient(
            [],
            $this->generateBasicAuthHeader(LoadCustomerUserData::AUTH_USER, LoadCustomerUserData::AUTH_PW)
        );
        $this->loadFixtures([
            LoadCombinedProductPrices::class
        ]);
    }

    public function testPriceProduct()
    {
        $productFirst = $this->getReference(LoadProductData::PRODUCT_1);
        $productSecond = $this->getReference(LoadProductData::PRODUCT_2);

        $response = $this->client->requestFrontendGrid(
            'frontend-product-search-grid',
            [
                'frontend-product-search-grid[_filter][sku][type]' => '1',
                'frontend-product-search-grid[_filter][sku][value]' => $productFirst->getSku(),
                'frontend-product-search-grid[_pager][_page]' => '1',
                'frontend-product-search-grid[_pager][_per_page]' => '100000',
            ],
            true
        );

        $this->getJsonResponseContent($response, 200);
        $items = json_decode($response->getContent(), true)['data'];
        $productFirstData = $this->assertIncludesWithReturn($productFirst->getId(), $items);

        $response = $this->client->requestFrontendGrid(
            'frontend-product-search-grid',
            [
                'frontend-product-search-grid[_filter][sku][type]' => '1',
                'frontend-product-search-grid[_filter][sku][value]' => $productSecond->getSku(),
                'frontend-product-search-grid[_pager][_page]' => '1',
                'frontend-product-search-grid[_pager][_per_page]' => '100000',
            ],
            true
        );

        $this->getJsonResponseContent($response, 200);
        $items = json_decode($response->getContent(), true)['data'];
        $productSecondData = $this->assertIncludesWithReturn($productSecond->getId(), $items);

        /** @var CombinedProductPrice $expectedPriceFirst */
        $expectedPriceFirst = $this->getReference(LoadCombinedProductPrices::PRICE_PRODUCT_7);
        /** @var CombinedProductPrice $expectedPriceSecond */
        $expectedPriceSecond = $this->getReference(LoadCombinedProductPrices::PRICE_PRODUCT_8);

        $this->assertEquals(
            $expectedPriceFirst->getPrice()->getValue(),
            $productFirstData['prices']['liter_1']['price']
        );

        $this->assertEquals(
            $expectedPriceSecond->getPrice()->getValue(),
            $productSecondData['prices']['liter_1']['price']
        );
    }

    /**
     * @param $id
     * @param $items
     * @return mixed
     */
    private function assertIncludesWithReturn($id, $items)
    {
        foreach ($items as $item) {
            if ($item['id'] == $id) {
                return $item;
            }
        }

        $this->assertTrue(false, 'Product ID = ' . $id . ' has not been found in the grid');

        return null;
    }
}
