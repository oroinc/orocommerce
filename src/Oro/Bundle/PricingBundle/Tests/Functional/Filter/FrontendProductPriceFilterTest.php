<?php

namespace Oro\Bundle\PricingBundle\Tests\Functional\Filter;

use Oro\Bundle\CustomerBundle\Tests\Functional\DataFixtures\LoadCustomerUserData;
use Oro\Bundle\DataGridBundle\Extension\Sorter\AbstractSorterExtension;
use Oro\Bundle\FilterBundle\Form\Type\Filter\NumberRangeFilterType;
use Oro\Bundle\FrontendTestFrameworkBundle\Test\FrontendWebTestCase;
use Oro\Bundle\PricingBundle\Tests\Functional\DataFixtures\LoadCombinedProductPrices;
use Symfony\Component\HttpFoundation\Request;

class FrontendProductPriceFilterTest extends FrontendWebTestCase
{
    protected function setUp(): void
    {
        $this->initClient();
        $this->getContainer()->get('request_stack')->push(Request::create(''));
        $this->loadFixtures([
            LoadCombinedProductPrices::class,
            LoadCustomerUserData::class,
        ]);
    }

    /**
     * @dataProvider productGridProvider
     */
    public function testProductGrid(array $expected, array $filter)
    {
        $this->markTestIncomplete("BB-6164");
        $response = $this->client->requestGrid(
            [
                'gridName' => 'frontend-product-search-grid',
            ],
            $filter,
            true,
            'oro_frontend_datagrid_index'
        );
        $result = $this->getJsonResponseContent($response, 200);
        $this->assertSame($expected, array_column($result['data'], 'sku'));
    }

    public function productGridProvider(): array
    {
        $sort = 'frontend-product-search-grid[_sort_by]';
        $filter = 'frontend-product-search-grid[_filter]';

        return [
            'sort by price' => [
                'expected' => [
                    'продукт-7',
                    'product-3',
                    'product-1',
                    'product-2',
                    'product-6',
                    'product-8',
                ],
                'filter' => [
                    $sort.'[minimal_price_sort]' => AbstractSorterExtension::DIRECTION_ASC,
                ]
            ],
            'filter by price sort by sku' => [
                'expected' => [
                    'product-3',
                    'product-1',
                ],
                'filter' => [
                    $filter.'[minimal_price][value]' => 12,
                    $filter.'[minimal_price][type]' => NumberRangeFilterType::TYPE_LESS_THAN,
                    $filter.'[minimal_price][unit]' => 'liter',
                    $sort.'[sku]' => AbstractSorterExtension::DIRECTION_DESC
                ]
            ],
            'filter and sort by price' => [
                'expected' => [
                    'product-2',
                    'product-1',
                ],
                'filter' => [
                    $filter.'[minimal_price][value]' => 8,
                    $filter.'[minimal_price][value_end]' => 15,
                    $filter.'[minimal_price][type]' => NumberRangeFilterType::TYPE_BETWEEN,
                    $filter.'[minimal_price][unit]' => 'liter',
                    $sort.'[minimal_price_sort]' => AbstractSorterExtension::DIRECTION_DESC,
                ],
            ],
        ];
    }
}
