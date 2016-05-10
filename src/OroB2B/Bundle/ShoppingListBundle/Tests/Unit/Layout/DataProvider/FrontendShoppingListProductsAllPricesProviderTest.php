<?php

namespace OroB2B\Bundle\ShoppingListBundle\Tests\Unit\Layout\DataProvider;

use Oro\Component\Layout\LayoutContext;
use Oro\Component\Testing\Unit\EntityTrait;

use OroB2B\Bundle\PricingBundle\Formatter\ProductPriceFormatter;
use OroB2B\Bundle\ShoppingListBundle\DataProvider\FrontendProductPricesDataProvider;
use OroB2B\Bundle\ShoppingListBundle\DataProvider\ShoppingListLineItemsDataProvider;
use OroB2B\Bundle\ShoppingListBundle\Entity\LineItem;
use OroB2B\Bundle\ShoppingListBundle\Entity\ShoppingList;
use OroB2B\Bundle\ShoppingListBundle\Layout\DataProvider\FrontendShoppingListProductsAllPricesProvider;

class FrontendShoppingListProductsAllPricesProviderTest extends \PHPUnit_Framework_TestCase
{
    use EntityTrait;

    /**
     * @var FrontendProductPricesDataProvider|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $frontendProductPricesDataProvider;

    /**
     * @var  FrontendShoppingListProductsAllPricesProvider
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

        $this->provider = new FrontendShoppingListProductsAllPricesProvider(
            $this->frontendProductPricesDataProvider,
            $this->shoppingListLineItemsDataProvider,
            $this->productPriceFormatter
        );
    }

    /**
     * @expectedException \RuntimeException
     * @expectedExceptionMessage Undefined data item index: entity.
     */
    public function testGetDataWithEmptyContext()
    {
        $context = new LayoutContext();
        $this->provider->getData($context);
    }

    public function testGetData()
    {
        /** @var ShoppingList $shoppingList */
        $shoppingList = $this->getEntity('OroB2B\Bundle\ShoppingListBundle\Entity\ShoppingList', ['id' => 2]);

        $context = new LayoutContext();
        $context->data()->set('entity', null, $shoppingList);
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

        $result = $this->provider->getData($context);
        $this->assertEquals($expected, $result);
    }

    public function testGetDataWithoutShoppingList()
    {
        $context = new LayoutContext();
        $context->data()->set('entity', null, null);
        $this->shoppingListLineItemsDataProvider->expects($this->never())
            ->method('getShoppingListLineItems');
        $this->frontendProductPricesDataProvider->expects($this->never())
            ->method('getProductsAllPrices');
        $this->productPriceFormatter->expects($this->never())
            ->method('formatProducts');

        $this->provider->getData($context);
    }
}
