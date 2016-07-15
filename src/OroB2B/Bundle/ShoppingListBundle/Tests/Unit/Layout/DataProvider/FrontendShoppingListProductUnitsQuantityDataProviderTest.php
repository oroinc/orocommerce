<?php

namespace OroB2B\Bundle\ShoppingListBundle\Tests\Unit\Layout\DataProvider;

use Oro\Component\Layout\LayoutContext;
use Oro\Component\Testing\Unit\EntityTrait;

use OroB2B\Bundle\AccountBundle\Entity\AccountUser;
use OroB2B\Bundle\ProductBundle\Entity\Product;
use OroB2B\Bundle\ProductBundle\Entity\ProductUnit;
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

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject|SecurityFacade
     */
    protected $securityFacade;

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

        $this->securityFacade = $this->getMockBuilder('Oro\Bundle\SecurityBundle\SecurityFacade')
            ->disableOriginalConstructor()
            ->getMock();

        $this->provider = new FrontendShoppingListProductUnitsQuantityDataProvider(
            $this->shoppingListManager,
            $this->lineItemRepository,
            $this->securityFacade
        );
    }

    protected function tearDown()
    {
        unset(
            $this->provider,
            $this->shoppingListManager,
            $this->lineItemRepository,
            $this->securityFacade
        );
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
        $accountUser = new AccountUser();
        $context->data()->set('product', null, $product);

        $this->shoppingListManager
            ->expects($this->any())
            ->method('getCurrent')
            ->willReturn($shoppingList);

        $this->lineItemRepository->expects($product && $shoppingList ? $this->once() : $this->never())
            ->method('getOneProductItemsWithShoppingListNames')
            ->with($product, $accountUser)
            ->willReturn($lineItems);

        $this->securityFacade->expects($this->any())
            ->method('getLoggedUser')
            ->willReturn($accountUser);

        $this->assertEquals($expected, $this->provider->getData($context));
    }

    /**
     * @return array
     */
    public function getDataDataProvider()
    {
        /** @var  ShoppingList $activeShoppingList */
        $activeShoppingList = $this->createShoppingList(1, 'ShoppingList 1');
        /** @var  ShoppingList $otherShoppingList */
        $otherShoppingList = $this->createShoppingList(2, 'ShoppingList 2');
        return [
            'no_product_no_shopping_list' => [
                'product' => null,
                'shoppingList' => null,
            ],
            'no_shopping_list' => [
                'product' => new Product(),
                'shoppingList' => null
            ],
            'no_prices' => [
                'product' => new Product(),
                'shoppingList' => new ShoppingList(),
                'lineItems' => [],
                'expected' => []
            ],
            'single_shopping_list' => [
                'product' => new Product(),
                'shoppingList' => $activeShoppingList,
                'lineItems' => [
                    $this->createLineItem(1, 'code1', 42, $activeShoppingList),
                    $this->createLineItem(2, 'code2', 100, $activeShoppingList)
                ],
                'expected' => [
                    [
                        'shopping_list_id' => 1,
                        'shopping_list_label' => 'ShoppingList 1',
                        'is_current' => true,
                        'line_items' => [
                            ['line_item_id' => 1, 'unit' => 'code1', 'quantity' => 42],
                            ['line_item_id' => 2, 'unit' => 'code2', 'quantity' => 100],
                        ]
                    ]
                ]
            ],
            'a_few_shopping_lists' => [
                'product' => new Product(),
                'shoppingList' => $activeShoppingList,
                'lineItems' => [
                    $this->createLineItem(1, 'code1', 42, $activeShoppingList),
                    $this->createLineItem(2, 'code2', 100, $activeShoppingList),
                    $this->createLineItem(3, 'code3', 30, $otherShoppingList),
                ],
                'expected' => [
                    [
                        'shopping_list_id' => 1,
                        'shopping_list_label' => 'ShoppingList 1',
                        'is_current' => true,
                        'line_items' => [
                            ['line_item_id' => 1, 'unit' => 'code1', 'quantity' => 42],
                            ['line_item_id' => 2,'unit' => 'code2', 'quantity' => 100],
                        ]
                    ],
                    [
                        'shopping_list_id' => 2,
                        'shopping_list_label' => 'ShoppingList 2',
                        'is_current' => false,
                        'line_items' => [
                            ['line_item_id' => 3, 'unit' => 'code3', 'quantity' => 30],
                        ]
                    ]
                ]
            ],
        ];
    }

    /**
     * @param int $id
     * @param string $unit
     * @param int $quantity
     * @param ShoppingList $shoppingList
     * @return  LineItem
     */
    private function createLineItem($id, $unit, $quantity, $shoppingList)
    {
        $lineItem = $this
            ->getMockBuilder('OroB2B\Bundle\ShoppingListBundle\Entity\LineItem')
            ->setMethods(['getId', 'getUnit', 'getQuantity', 'getShoppingList'])
            ->getMock();
        $lineItem ->expects($this->any())
            ->method('getId')
            ->will($this->returnValue($id));
        $lineItem ->expects($this->any())
            ->method('getUnit')
            ->will($this->returnValue((new ProductUnit())->setCode($unit)));
        $lineItem ->expects($this->any())
            ->method('getQuantity')
            ->will($this->returnValue($quantity));
        $lineItem ->expects($this->any())
            ->method('getShoppingList')
            ->will($this->returnValue($shoppingList));

        return $lineItem;
    }
    
    /**
     * @param int $id
     * @param string $label
     * @return ShoppingList
     */
    protected function createShoppingList($id, $label)
    {
        $shoppingList = $this->getMockBuilder('OroB2B\Bundle\ShoppingListBundle\Entity\ShoppingList')
            ->disableOriginalConstructor()
            ->getMock();
        $shoppingList->expects($this->any())->method('getId')->willReturn($id);
        $shoppingList->expects($this->any())->method('getLabel')->willReturn($label);
        return $shoppingList;
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
