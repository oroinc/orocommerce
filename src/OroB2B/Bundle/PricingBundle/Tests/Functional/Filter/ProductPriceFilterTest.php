<?php

namespace OroB2B\Bundle\PricingBundle\Tests\Functional\Filter;

use Oro\Bundle\FilterBundle\Form\Type\Filter\NumberFilterType;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;

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
        $this->initClient([], array_merge($this->generateBasicAuthHeader(), ['HTTP_X-CSRF-Header' => 1]));
        $this->loadFixtures([
            'OroB2B\Bundle\ProductBundle\Tests\Functional\DataFixtures\LoadProducts',
            'OroB2B\Bundle\PricingBundle\Tests\Functional\DataFixtures\LoadProductPrices'
        ]);
    }

    /**
     * @dataProvider filterDataProvider
     * @param array $filter
     * @param array $expected
     */
    public function testFilter(array $filter, array $expected)
    {
        $response = $this->client->requestGrid('products-grid', $filter);
        $result = $this->getJsonResponseContent($response, 200);

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
                'expected' => ['product.1']
            ],
            'greater equal 10 USD per liter' => [
                'filter' => [
                    'products-grid[_filter][price_column_usd][value]' => 10,
                    'products-grid[_filter][price_column_usd][type]'  => NumberFilterType::TYPE_GREATER_EQUAL,
                    'products-grid[_filter][price_column_usd][unit]'  => 'liter'
                ],
                'expected' => ['product.1', 'product.2']
            ],
            'less 10 USD per liter' => [
                'filter' => [
                    'products-grid[_filter][price_column_usd][value]' => 10,
                    'products-grid[_filter][price_column_usd][type]'  => NumberFilterType::TYPE_LESS_THAN,
                    'products-grid[_filter][price_column_usd][unit]'  => 'liter'
                ],
                'expected' => ['product.3']
            ],
            'greater 10 USD per liter AND less 20 EUR per bottle' => [
                'filter' => [
                    'products-grid[_filter][price_column_usd][value]' => 10,
                    'products-grid[_filter][price_column_usd][type]'  => NumberFilterType::TYPE_GREATER_THAN,
                    'products-grid[_filter][price_column_usd][unit]'  => 'liter',
                    'products-grid[_filter][price_column_eur][value]' => 20,
                    'products-grid[_filter][price_column_eur][type]'  => NumberFilterType::TYPE_LESS_THAN,
                    'products-grid[_filter][price_column_eur][unit]'  => 'bottle'
                ],
                'expected' => ['product.1']
            ],
        ];
    }
}
