<?php

namespace OroB2B\Bundle\PricingBundle\Tests\Functional\Filter;

use Oro\Bundle\FilterBundle\Form\Type\Filter\NumberFilterType;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;

use OroB2B\Bundle\PricingBundle\Entity\PriceList;
use OroB2B\Bundle\PricingBundle\Model\PriceListRequestHandler;

/**
 * @dbIsolation
 */
class ProductPriceFilterTest extends WebTestCase
{
    /**
     * {@inheritDoc}
     */
    protected function setUp()
    {
        $this->initClient([], $this->generateBasicAuthHeader());
        $this->loadFixtures([
            'OroB2B\Bundle\ProductBundle\Tests\Functional\DataFixtures\LoadProducts',
            'OroB2B\Bundle\PricingBundle\Tests\Functional\DataFixtures\LoadProductPrices'
        ]);
    }

    /**
     * @dataProvider filterDataProvider
     * @param array $filter
     * @param string $priceListReference
     * @param array $expected
     */
    public function testFilter(array $filter, $priceListReference, array $expected)
    {
        /** @var PriceList $priceList */
        $priceList = $this->getReference($priceListReference);

        $response = $this->client->requestGrid(
            [
                'gridName' => 'products-grid',
                PriceListRequestHandler::PRICE_LIST_KEY => $priceList->getId(),
            ],
            $filter
        );
        $result = $this->getJsonResponseContent($response, 200);

        $this->assertArrayHasKey('data', $result);
        $this->assertSameSize($expected, $result['data']);

        foreach ($result['data'] as $product) {
            $this->assertContains($product['sku'], $expected);
        }
    }

    /**
     * @return array
     */
    public function filterDataProvider()
    {
        return [
            'equal 10 USD per liter' => [
                'filter' => [
                    'products-grid[_filter][price_column_usd][value]' => 10,
                    'products-grid[_filter][price_column_usd][type]'  => null,
                    'products-grid[_filter][price_column_usd][unit]'  => 'liter'
                ],
                'priceListReference' => 'price_list_1',
                'expected' => ['product.1']
            ],
            'greater equal 10 USD per liter' => [
                'filter' => [
                    'products-grid[_filter][price_column_usd][value]' => 10,
                    'products-grid[_filter][price_column_usd][type]'  => NumberFilterType::TYPE_GREATER_EQUAL,
                    'products-grid[_filter][price_column_usd][unit]'  => 'liter'
                ],
                'priceListReference' => 'price_list_1',
                'expected' => ['product.1', 'product.2']
            ],
            'less 10 USD per liter' => [
                'filter' => [
                    'products-grid[_filter][price_column_usd][value]' => 10,
                    'products-grid[_filter][price_column_usd][type]'  => NumberFilterType::TYPE_LESS_THAN,
                    'products-grid[_filter][price_column_usd][unit]'  => 'liter'
                ],
                'priceListReference' => 'price_list_1',
                'expected' => ['product.3']
            ],
            'greater 10 USD per liter AND less 20 EUR per bottle' => [
                'filter' => [
                    'products-grid[_filter][price_column_usd][value]' => 10,
                    'products-grid[_filter][price_column_usd][type]'  => NumberFilterType::TYPE_GREATER_EQUAL,
                    'products-grid[_filter][price_column_usd][unit]'  => 'liter',
                    'products-grid[_filter][price_column_eur][value]' => 20,
                    'products-grid[_filter][price_column_eur][type]'  => NumberFilterType::TYPE_LESS_THAN,
                    'products-grid[_filter][price_column_eur][unit]'  => 'bottle'
                ],
                'priceListReference' => 'price_list_1',
                'expected' => ['product.1']
            ],
        ];
    }
}
