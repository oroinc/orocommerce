<?php

namespace Oro\Bundle\ShoppingListBundle\Tests\Unit\EventListener;

use Doctrine\Bundle\DoctrineBundle\Registry;

use Oro\Bundle\DataGridBundle\Datagrid\Common\DatagridConfiguration;
use Oro\Bundle\DataGridBundle\Datagrid\DatagridInterface;
use Oro\Bundle\DataGridBundle\Datagrid\ParameterBag;
use Oro\Bundle\DataGridBundle\Datasource\ResultRecord;
use Oro\Bundle\DataGridBundle\Event\PreBuild;
use Oro\Bundle\DataGridBundle\Extension\Formatter\Property\PropertyInterface;
use Oro\Bundle\SearchBundle\Datagrid\Event\SearchResultAfter;
use Oro\Bundle\SearchBundle\Query\SearchQueryInterface;
use Oro\Bundle\SecurityBundle\SecurityFacade;
use Oro\Bundle\ShoppingListBundle\Entity\Repository\ShoppingListRepository;
use Oro\Component\Testing\Unit\EntityTrait;
use Oro\Bundle\CustomerBundle\Entity\AccountUser;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\ProductBundle\Entity\ProductUnit;
use Oro\Bundle\ShoppingListBundle\Entity\LineItem;
use Oro\Bundle\ShoppingListBundle\Entity\ShoppingList;
use Oro\Bundle\ShoppingListBundle\EventListener\FrontendProductDatagridListener;

class FrontendProductDatagridListenerTest extends \PHPUnit_Framework_TestCase
{
    use EntityTrait;

    /**
     * @var SecurityFacade|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $securityFacade;

    /**
     * @var Registry|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $registry;

    /**
     * @var FrontendProductDatagridListener
     */
    protected $listener;

    public function setUp()
    {
        $this->securityFacade = $this->getMockBuilder(SecurityFacade::class)
            ->disableOriginalConstructor()->getMock();

        $this->registry = $this->getMockBuilder(
            Registry::class
        )->disableOriginalConstructor()->getMock();

        $this->listener = new FrontendProductDatagridListener(
            $this->securityFacade,
            $this->registry
        );
    }

    protected function tearDown()
    {
        unset($this->securityFacade, $this->listener);
    }

    public function testOnPreBuild()
    {
        $config = DatagridConfiguration::createNamed('grid-name', []);
        $event  = new PreBuild($config, new ParameterBag());

        $this->listener->onPreBuild($event);

        $this->assertEquals(
            [
                'name'       => 'grid-name',
                'properties' => [
                    FrontendProductDatagridListener::COLUMN_LINE_ITEMS => [
                        'type'          => 'field',
                        'frontend_type' => PropertyInterface::TYPE_ROW_ARRAY
                    ]
                ],
            ],
            $config->toArray()
        );
    }

    /**
     * @dataProvider unsupportedUserDataProvider
     * @param mixed $user
     */
    public function testOnResultAfterNoUser($user)
    {
        /** @var SearchResultAfter|\PHPUnit_Framework_MockObject_MockObject $event */
        $event = $this->getMockBuilder(SearchResultAfter::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->securityFacade->expects($this->once())
            ->method('getLoggedUser')
            ->willReturn($user);
        $event->expects($this->never())
            ->method($this->anything());

        $this->listener->onResultAfter($event);
    }

    /**
     * @return array
     */
    public function unsupportedUserDataProvider()
    {
        return [
            [null],
            [new \stdClass()]
        ];
    }

    public function testOnResultAfterNoShoppingList()
    {
        $user = new AccountUser();

        /** @var DatagridInterface|\PHPUnit_Framework_MockObject_MockObject $datagrid */
        $datagrid = $this->getMock(DatagridInterface::class);

        $records = [
            $this->getMock(DatagridInterface::class)
        ];

        $shoppingListRepository = $this
            ->getMockBuilder(ShoppingListRepository::class)
            ->disableOriginalConstructor()
            ->getMock();
        $shoppingListRepository->expects($this->once())
            ->method('findAvailableForAccountUser')
            ->with($user)
            ->willReturn(null);
        $this->registry->expects($this->once())
            ->method('getRepository')
            ->with('OroShoppingListBundle:ShoppingList')
            ->willReturn($shoppingListRepository);
        $query = $this->getMockBuilder(SearchQueryInterface::class)
            ->disableOriginalConstructor()->getMock();

        /** @var SearchResultAfter|\PHPUnit_Framework_MockObject_MockObject $event */
        $event = new SearchResultAfter($datagrid, $query, $records);

        $this->securityFacade->expects($this->once())
            ->method('getLoggedUser')
            ->willReturn($user);

        $this->listener->onResultAfter($event);
    }

    public function testOnResultAfterNoRecords()
    {
        $user = new AccountUser();

        /** @var DatagridInterface|\PHPUnit_Framework_MockObject_MockObject $datagrid */
        $datagrid = $this->getMock(DatagridInterface::class);

        $records = [];

        $shoppingListRepository = $this
            ->getMockBuilder(ShoppingListRepository::class)
            ->disableOriginalConstructor()
            ->getMock();
        $shoppingListRepository->expects($this->once())
            ->method('findAvailableForAccountUser')
            ->with($user)
            ->willReturn(new ShoppingList());
        $this->registry->expects($this->once())
            ->method('getRepository')
            ->with('OroShoppingListBundle:ShoppingList')
            ->willReturn($shoppingListRepository);
        $query = $this->getMockBuilder(SearchQueryInterface::class)
            ->disableOriginalConstructor()->getMock();

        /** @var SearchResultAfter|\PHPUnit_Framework_MockObject_MockObject $event */
        $event = new SearchResultAfter($datagrid, $query, $records);

        $this->securityFacade->expects($this->once())
            ->method('getLoggedUser')
            ->willReturn($user);

        $this->listener->onResultAfter($event);
    }

    public function testOnResultAfter()
    {
        $user = new AccountUser();

        /** @var DatagridInterface|\PHPUnit_Framework_MockObject_MockObject $datagrid */
        $datagrid = $this->getMock(DatagridInterface::class);

        $recordOne = new ResultRecord(['id' => 1]);
        $recordTwo = new ResultRecord(['id' => 2]);
        $records   = [$recordOne, $recordTwo];

        /** @var Product $productOne */
        $productOne         = $this->getEntity(Product::class, ['id' => 1]);
        $lineItemRepository = $this->getMockBuilder('LineItemRepository')
            ->setMethods(['getProductItemsWithShoppingListNames'])
            ->getMock();
        $shoppingList1      = $this->createShoppingList(1, 'Shopping List1');
        $shoppingList2      = $this->createShoppingList(2, 'Shopping List2');

        $shoppingListRepository = $this
            ->getMockBuilder(ShoppingListRepository::class)
            ->disableOriginalConstructor()
            ->getMock();
        $shoppingListRepository->expects($this->once())
            ->method('findAvailableForAccountUser')
            ->with($user)
            ->willReturn($shoppingList1);

        $lineItemRepository->expects($this->once())
            ->method('getProductItemsWithShoppingListNames')
            ->with([1, 2], $user)
            ->willReturn(
                [
                    $this->createLineItem(1, 'unt1', 1, $shoppingList1, $productOne),
                    $this->createLineItem(2, 'unt2', 2, $shoppingList2, $productOne),
                    $this->createLineItem(3, 'unt3', 5, $shoppingList2, $productOne),
                ]
            );

        $this->registry = $this->getMockBuilder(Registry::class)
            ->disableOriginalConstructor()->getMock();

        $this->registry->expects($this->at(0))
            ->method('getRepository')
            ->with('OroShoppingListBundle:ShoppingList')
            ->will($this->returnValue($shoppingListRepository));

        $this->registry->expects($this->at(1))
            ->method('getRepository')
            ->with('OroShoppingListBundle:LineItem')
            ->will($this->returnValue($lineItemRepository));

        $query = $this->getMockBuilder(SearchQueryInterface::class)
            ->disableOriginalConstructor()->getMock();

        /** @var SearchResultAfter|\PHPUnit_Framework_MockObject_MockObject $event */
        $event = new SearchResultAfter($datagrid, $query, $records);

        $this->securityFacade->expects($this->once())
            ->method('getLoggedUser')
            ->willReturn($user);

        $this->listener = new FrontendProductDatagridListener(
            $this->securityFacade,
            $this->registry
        );

        $this->listener->onResultAfter($event);

        $this->assertEquals(
            [
                [
                    'id'         => 1,
                    'label'      => 'Shopping List1',
                    'is_current' => true,
                    'line_items' => [['id' => 1, 'unit' => 'unt1', 'quantity' => 1]]
                ],
                [
                    'id'         => 2,
                    'label'      => 'Shopping List2',
                    'is_current' => false,
                    'line_items' => [
                        ['id' => 2, 'unit' => 'unt2', 'quantity' => 2],
                        ['id' => 3, 'unit' => 'unt3', 'quantity' => 5],
                    ],
                ],
            ],
            $recordOne->getValue('shopping_lists')
        );
        $this->assertEmpty($recordTwo->getValue('shopping_lists'));
    }

    /**
     * @param int    $id
     * @param string $label
     * @return \PHPUnit_Framework_MockObject_MockObject
     */
    private function createShoppingList($id, $label)
    {
        $shoppingList = $this
            ->getMockBuilder(ShoppingList::class)
            ->setMethods(['getId', 'getLabel'])
            ->getMock();
        $shoppingList->expects($this->any())
            ->method('getId')
            ->will($this->returnValue($id));
        $shoppingList->expects($this->any())
            ->method('getLabel')
            ->will($this->returnValue($label));
        return $shoppingList;
    }

    /**
     * @param int          $id
     * @param string       $unit
     * @param int          $quantity
     * @param shoppingList $shoppingList
     * @param product      $product
     * @return \PHPUnit_Framework_MockObject_MockObject
     */
    private function createLineItem($id, $unit, $quantity, $shoppingList, $product)
    {
        $lineItem = $this
            ->getMockBuilder(LineItem::class)
            ->setMethods(['getId', 'getUnit', 'getQuantity', 'getShoppingList', 'getProduct'])
            ->getMock();
        $lineItem->expects($this->any())
            ->method('getId')
            ->will($this->returnValue($id));
        $lineItem->expects($this->any())
            ->method('getUnit')
            ->will($this->returnValue((new ProductUnit())->setCode($unit)));
        $lineItem->expects($this->any())
            ->method('getQuantity')
            ->will($this->returnValue($quantity));
        $lineItem->expects($this->any())
            ->method('getShoppingList')
            ->will($this->returnValue($shoppingList));
        $lineItem->expects($this->any())
            ->method('getProduct')
            ->will($this->returnValue($product));

        return $lineItem;
    }
}
