<?php

namespace OroB2B\Bundle\ShoppingListBundle\Tests\Unit\Layout\DataProvider;

use Oro\Component\Testing\Unit\EntityTrait;

use OroB2B\Bundle\PricingBundle\Formatter\ProductPriceFormatter;
use OroB2B\Bundle\ShoppingListBundle\DataProvider\FrontendProductPricesDataProvider;
use OroB2B\Bundle\ShoppingListBundle\DataProvider\ShoppingListLineItemsDataProvider;
use OroB2B\Bundle\ShoppingListBundle\Entity\LineItem;
use OroB2B\Bundle\ShoppingListBundle\Entity\ShoppingList;
use OroB2B\Bundle\ShoppingListBundle\Layout\DataProvider\FrontendShoppingListProductsDataProvider;

class FrontendShoppingListProductsDataProviderTest extends \PHPUnit_Framework_TestCase
{
    use EntityTrait;

    /**
     * @var FrontendProductPricesDataProvider|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $frontendProductPricesDataProvider;

    /**
     * @var  FrontendShoppingListProductsDataProvider
     */
    protected $provider;

    /**
     * @var ShoppingListLineItemsDataProvider|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $shoppingListLineItemsDataProvider;

    /**
     * @var ProductPriceFormatter|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $productPriceFormatter;

    public function setUp()
    {
        $this->frontendProductPricesDataProvider = $this
            ->getMockBuilder('OroB2B\Bundle\ShoppingListBundle\DataProvider\FrontendProductPricesDataProvider')
            ->disableOriginalConstructor()
            ->getMock();
        $this->shoppingListLineItemsDataProvider = $this
            ->getMockBuilder('OroB2B\Bundle\ShoppingListBundle\DataProvider\ShoppingListLineItemsDataProvider')
            ->disableOriginalConstructor()->getMock();

        $this->productPriceFormatter = $this
            ->getMockBuilder('OroB2B\Bundle\PricingBundle\Formatter\ProductPriceFormatter')
            ->disableOriginalConstructor()->getMock();

        $this->provider = new FrontendShoppingListProductsDataProvider(
            $this->frontendProductPricesDataProvider,
            $this->shoppingListLineItemsDataProvider,
            $this->productPriceFormatter
        );
    }

    public function testGetAllPrices()
    {
        /** @var ShoppingList $shoppingList */
        $shoppingList = $this->getEntity('OroB2B\Bundle\ShoppingListBundle\Entity\ShoppingList', ['id' => 2]);

        /** @var LineItem[] $lineItems */
        $lineItems = [
            $this->getEntity('OroB2B\Bundle\ShoppingListBundle\Entity\LineItem', ['id' => 1]),
        ];
        $prices = ['price_1', 'price_2'];
        $expected = ['price_1', 'price_2'];

        $this->shoppingListLineItemsDataProvider->expects($this->once())
            ->method('getShoppingListLineItems')
            ->with($shoppingList)
            ->willReturn($lineItems);

        $this->frontendProductPricesDataProvider
            ->expects($this->once())
            ->method('getProductsAllPrices')
            ->with($lineItems)
            ->willReturn($prices);

        $this->productPriceFormatter->expects($this->once())
            ->method('formatProducts')
            ->with($prices)
            ->willReturn($expected);

        $result = $this->provider->getAllPrices($shoppingList);
        $this->assertEquals($expected, $result);
    }

    public function testGetAllPricesWithoutShoppingList()
    {
        $this->shoppingListLineItemsDataProvider->expects($this->never())
            ->method('getShoppingListLineItems');
        $this->frontendProductPricesDataProvider->expects($this->never())
            ->method('getProductsAllPrices');
        $this->productPriceFormatter->expects($this->never())
            ->method('formatProducts');

        $this->provider->getAllPrices();
    }

    /**
     * @dataProvider matchedPriceDataProvider
     * @param ShoppingList|null $shoppingList
     */
    public function testGetMatchedPrice($shoppingList)
    {
        $expected = null;

        if ($shoppingList) {
            $lineItems = [];

            $this->shoppingListLineItemsDataProvider->expects($this->once())
                ->method('getShoppingListLineItems')
                ->willReturn($lineItems);

            $expected = 'expectedData';
            $this->frontendProductPricesDataProvider
                ->expects($this->once())
                ->method('getProductsMatchedPrice')
                ->with($lineItems)
                ->willReturn($expected);
        }

        $result = $this->provider->getMatchedPrice($shoppingList);
        $this->assertEquals($expected, $result);
    }

    /**
     * @return array
     */
    public function matchedPriceDataProvider()
    {
        return [
            'with shoppingList' => [
                'entity' => new ShoppingList(),
            ],
            'without shoppingList' => [
                'entity' => null,
            ],
        ];
    }
}
