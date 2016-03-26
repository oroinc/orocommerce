<?php

namespace OroB2B\Bundle\PricingBundle\Tests\Functional\Filter;

use Oro\Bundle\FilterBundle\Form\Type\Filter\NumberFilterType;
use Oro\Bundle\FilterBundle\Form\Type\Filter\NumberRangeFilterType;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;

use OroB2B\Bundle\PricingBundle\Model\PriceListRequestHandler;

/**
 * @dbIsolation
 */
class FrontendProductPriceFilterTest extends WebTestCase
{
    /**
     * {@inheritDoc}
     */
    protected function setUp()
    {
        $this->initClient([], $this->generateBasicAuthHeader());
        $this->loadFixtures(['OroB2B\Bundle\PricingBundle\Tests\Functional\DataFixtures\LoadCombinedProductPrices']);
    }

    /**
     * @dataProvider filterDataProvider
     * @param array $filter
     * @param array $expected
     */
    public function testFilter(array $filter, array $expected)
    {
        $account = $this->getReference('account.level_1.2');
        $response = $this->client->requestGrid([
            'gridName' => 'frontend-products-grid',
            PriceListRequestHandler::ACCOUNT_ID_KEY => $account->getId(),
        ], $filter);
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
            'equal 1.1 USD per bottle' => [
                'filter' => [
                    'frontend-products-grid[_filter][minimum_price][value]' => 1.1,
                    'frontend-products-grid[_filter][minimum_price][type]'  => null,
                    'frontend-products-grid[_filter][minimum_price][unit]'  => 'bottle'
                ],
                'expected' => []
            ],
            'equal 10 USD per liter' => [
                'filter' => [
                    'frontend-products-grid[_filter][minimum_price][value]' => 10,
                    'frontend-products-grid[_filter][minimum_price][type]'  => null,
                    'frontend-products-grid[_filter][minimum_price][unit]'  => 'liter'
                ],
                'expected' => ['product.1']
            ],
            'greater equal 12.2 USD per liter' => [
                'filter' => [
                    'frontend-products-grid[_filter][minimum_price][value]' => 12.2,
                    'frontend-products-grid[_filter][minimum_price][type]' => NumberFilterType::TYPE_GREATER_EQUAL,
                    'frontend-products-grid[_filter][minimum_price][unit]' => 'liter'
                ],
                'expected' => ['product.2']
            ],
            'less 10 USD per liter' => [
                'filter' => [
                    'frontend-products-grid[_filter][minimum_price][value]' => 10,
                    'frontend-products-grid[_filter][minimum_price][type]'  => NumberFilterType::TYPE_LESS_THAN,
                    'frontend-products-grid[_filter][minimum_price][unit]'  => 'liter'
                ],
                'expected' => ['product.3']
            ],
            'greater 10 USD per liter AND less 20 EUR per bottle' => [
                'filter' => [
                    'frontend-products-grid[_filter][minimum_price][value]' => 1,
                    'frontend-products-grid[_filter][minimum_price][value_end]' => 10,
                    'frontend-products-grid[_filter][minimum_price][type]'  => NumberRangeFilterType::TYPE_BETWEEN,
                    'frontend-products-grid[_filter][minimum_price][unit]'  => 'liter',
                ],
                'expected' => ['product.1', 'product.3']
            ],
        ];
    }
}
