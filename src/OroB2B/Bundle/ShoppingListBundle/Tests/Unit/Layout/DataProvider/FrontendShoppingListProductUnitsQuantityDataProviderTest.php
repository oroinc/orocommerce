<?php

namespace OroB2B\Bundle\ShoppingListBundle\Tests\Unit\Layout\DataProvider;

use Oro\Component\Layout\LayoutContext;
use Oro\Component\Testing\Unit\EntityTrait;

use OroB2B\Bundle\ProductBundle\Entity\Product;
use OroB2B\Bundle\ShoppingListBundle\Entity\LineItem;
use OroB2B\Bundle\ShoppingListBundle\Entity\Repository\LineItemRepository;
use OroB2B\Bundle\ShoppingListBundle\Entity\ShoppingList;
use OroB2B\Bundle\ShoppingListBundle\Layout\DataProvider\FrontendShoppingListProductUnitsQuantityDataProvider;
use OroB2B\Bundle\ShoppingListBundle\Manager\ShoppingListManager;

class FrontendShoppingListProductUnitsQuantityDataProviderTest extends \PHPUnit_Framework_TestCase
{
    use EntityTrait;

    /** @var ShoppingListManager|\PHPUnit_Framework_MockObject_MockObject */
    protected $shoppingListManager;

    /** @var LineItemRepository|\PHPUnit_Framework_MockObject_MockObject */
    protected $lineItemRepository;

    /** @var FrontendShoppingListProductUnitsQuantityDataProvider */
    protected $provider;

    protected function setUp()
    {
        $this->shoppingListManager = $this
            ->getMockBuilder('OroB2B\Bundle\ShoppingListBundle\Manager\ShoppingListManager')
            ->disableOriginalConstructor()
            ->getMock();

        $this->lineItemRepository = $this
            ->getMockBuilder('OroB2B\Bundle\ShoppingListBundle\Entity\Repository\LineItemRepository')
            ->disableOriginalConstructor()
            ->getMock();

        $this->provider = new FrontendShoppingListProductUnitsQuantityDataProvider(
            $this->shoppingListManager,
            $this->lineItemRepository
        );
    }

    protected function tearDown()
    {
        unset($this->provider, $this->shoppingListManager, $this->lineItemRepository);
    }

    /**
     * @expectedException \BadMethodCallException
     */
    public function testGetIdentifier()
    {
        $this->provider->getIdentifier();
    }

    /**
     * @dataProvider getDataDataProvider
     *
     * @param Product|null $product
     * @param ShoppingList|null $shoppingList
     * @param array $lineItems
     * @param array|null $expected
     */
    public function testGetData(
        $product,
        $shoppingList,
        array $lineItems = [],
        array $expected = null
    ) {
        $context = new LayoutContext();
        $context->data()->set('product', null, $product);

        $this->shoppingListManager->expects($product ? $this->once() : $this->never())
            ->method('getCurrent')
            ->willReturn($shoppingList);

        $this->lineItemRepository->expects($product && $shoppingList ? $this->once() : $this->never())
            ->method('getItemsByShoppingListAndProduct')
            ->with($shoppingList, $product)
            ->willReturn($lineItems);

        $this->assertEquals($expected, $this->provider->getData($context));
    }

    /**
     * @return array
     */
    public function getDataDataProvider()
    {
        return [
            [
                'product' => null,
                'shoppingList' => null
            ],
            [
                'product' => new Product(),
                'shoppingList' => null
            ],
            [
                'product' => new Product(),
                'shoppingList' => new ShoppingList(),
                'lineItems' => [],
                'expected' => []
            ],
            [
                'product' => new Product(),
                'shoppingList' => new ShoppingList(),
                'lineItems' => [$this->createLineItem('code1', 42), $this->createLineItem('code2', 100)],
                'expected' => ['code1' => 42, 'code2' => 100]
            ],
        ];
    }

    /**
     * @param string $code
     * @param int $quantity
     * @return LineItem
     */
    protected function createLineItem($code, $quantity)
    {
        return $this->getEntity(
            'OroB2B\Bundle\ShoppingListBundle\Entity\LineItem',
            [
                'unit' => $this->getEntity(
                    'OroB2B\Bundle\ProductBundle\Entity\ProductUnit',
                    ['code' => $code]
                ),
                'quantity' => $quantity
            ]
        );
    }

    /**
     * @expectedException \RuntimeException
     * @expectedExceptionMessage Undefined data item index: product.
     */
    public function testGetDataWithEmptyContext()
    {
        $this->provider->getData(new LayoutContext());
    }
}
