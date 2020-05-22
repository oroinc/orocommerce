<?php

namespace Oro\Bundle\ShoppingListBundle\Tests\Unit\Datagrid\Helper;

use Oro\Bundle\CurrencyBundle\Provider\DefaultCurrencyProviderInterface;
use Oro\Bundle\PricingBundle\Provider\WebsiteCurrencyProvider;
use Oro\Bundle\ShoppingListBundle\Datagrid\Helper\ShoppingListGridTotalCurrencyHelper;

class ShoppingListGridTotalCurrencyHelperTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var WebsiteCurrencyProvider|\PHPUnit\Framework\MockObject\MockObject
     */
    protected $websiteCurrencyProvider;

    /**
     * @var DefaultCurrencyProviderInterface|\PHPUnit\Framework\MockObject\MockObject
     */
    protected $defaultCurrencyProvider;

    /**
     * @var ShoppingListGridTotalCurrencyHelper
     */
    protected $helper;

    protected function setUp(): void
    {
        $this->defaultCurrencyProvider = $this->createMock(DefaultCurrencyProviderInterface::class);
        $this->websiteCurrencyProvider = $this->createMock(WebsiteCurrencyProvider::class);

        $this->helper = new ShoppingListGridTotalCurrencyHelper(
            $this->defaultCurrencyProvider,
            $this->websiteCurrencyProvider
        );
    }

    /**
     * @dataProvider getTestData
     *
     * @param string $defaultCurrency
     * @param array $websitesCurrencies
     * @param string $expected
     */
    public function testGetCurrencyStatement($defaultCurrency, array $websitesCurrencies, $expected)
    {
        $this->defaultCurrencyProvider->expects($this->once())
            ->method('getDefaultCurrency')
            ->willReturn($defaultCurrency);

        $this->websiteCurrencyProvider->expects($this->once())
            ->method('getAllWebsitesCurrencies')
            ->willReturn($websitesCurrencies);

        $this->assertEquals($expected, $this->helper->getCurrencyStatement());
    }

    public function getTestData()
    {
        return [
            [
                'defaultCurrency' => 'USD',
                'websitesCurrencies' => [],
                'expected' => "'USD'"
            ],
            [
                'defaultCurrency' => 'USD',
                'websitesCurrencies' => [1 => 'USD', 2 => 'USD', 3 => 'USD'],
                'expected' => "'USD'"
            ],
            [
                'defaultCurrency' => 'USD',
                'websitesCurrencies' => [1 => 'EUR'],
                'expected' => "CASE  WHEN shopping_list.website IN (1) THEN 'EUR'  ELSE 'USD' END"
            ],
            [
                'defaultCurrency' => 'USD',
                'websitesCurrencies' => [1 => 'EUR', 2 => 'EUR', 3 => 'EUR'],
                'expected' => "CASE  WHEN shopping_list.website IN (1,2,3) THEN 'EUR'  ELSE 'USD' END"
            ],
            [
                'defaultCurrency' => 'USD',
                'websitesCurrencies' => [1 => 'USD', 2 => 'EUR', 3 => 'EUR', 4 => 'EUR', 5 => 'UAH'],
                'expected' => "CASE  WHEN shopping_list.website IN (2,3,4) THEN 'EUR' " .
                    " WHEN shopping_list.website IN (5) THEN 'UAH'  ELSE 'USD' END"
            ],
        ];
    }
}
