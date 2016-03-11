<?php

namespace OroB2B\Bundle\ShoppingListBundle\Tests\Unit\Layout\DataProvider;

use Oro\Component\Layout\LayoutContext;

use OroB2B\Bundle\ShoppingListBundle\DataProvider\FrontendProductPricesDataProvider;
use OroB2B\Bundle\ShoppingListBundle\Entity\ShoppingList;
use OroB2B\Bundle\ShoppingListBundle\Layout\DataProvider\FrontendShoppingListProductsPricesDataProvider;

class FrontendShoppingListProductsPricesDataProviderTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var \PHPUnit_Framework_MockObject_MockObject|FrontendProductPricesDataProvider
     */
    protected $frontendProductPricesDataProvider;

    /**
     * @var  FrontendShoppingListProductsPricesDataProvider
     */
    protected $provider;

    public function setUp()
    {
        $this->frontendProductPricesDataProvider = $this
            ->getMockBuilder('OroB2B\Bundle\ShoppingListBundle\DataProvider\FrontendProductPricesDataProvider')
            ->disableOriginalConstructor()
            ->getMock();

        $this->provider = new FrontendShoppingListProductsPricesDataProvider(
            $this->frontendProductPricesDataProvider
        );
    }

    /**
     * @expectedException \BadMethodCallException
     */
    public function testGetIdentifier()
    {
        $this->provider->getIdentifier();
    }

    /**
     * @expectedException \RuntimeException
     * @expectedExceptionMessage Undefined data item index: shoppingList.
     */
    public function testGetDataWithEmptyContext()
    {
        $context = new LayoutContext();
        $this->provider->getData($context);
    }

    /**
     * @dataProvider getDataDataProvider
     * @param ShoppingList|null $shoppingList
     */
    public function testGetData($shoppingList)
    {
        $context = new LayoutContext();
        $context->data()->set('shoppingList', null, $shoppingList);
        $expected = null;

        if ($shoppingList) {
            $expected = 'expectedData';
            $this->frontendProductPricesDataProvider
                ->expects($this->once())
                ->method('getProductsPrices')
                ->willReturn($expected);
        }

        $result = $this->provider->getData($context);
        $this->assertEquals($expected, $result);
    }

    /**
     * @return array
     */
    public function getDataDataProvider()
    {
        return [
            'with shoppingList' => [
                'shoppingList' => new ShoppingList(),
            ],
            'without shoppingList' => [
                'shoppingList' => null,
            ],
        ];
    }
}
