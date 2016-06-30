<?php

namespace OroB2B\Bundle\ShoppingListBundle\Tests\Unit\EventListener;

use Oro\Bundle\DataGridBundle\Datagrid\Common\DatagridConfiguration;
use Oro\Bundle\DataGridBundle\Datagrid\DatagridInterface;
use Oro\Bundle\DataGridBundle\Datagrid\ParameterBag;
use Oro\Bundle\DataGridBundle\Datasource\ResultRecord;
use Oro\Bundle\DataGridBundle\Event\OrmResultAfter;
use Oro\Bundle\DataGridBundle\Event\PreBuild;
use Oro\Bundle\DataGridBundle\Extension\Formatter\Property\PropertyInterface;
use Oro\Bundle\SecurityBundle\SecurityFacade;
use Oro\Component\Testing\Unit\EntityTrait;

use OroB2B\Bundle\AccountBundle\Entity\AccountUser;
use OroB2B\Bundle\ProductBundle\Entity\Product;
use OroB2B\Bundle\ProductBundle\Entity\ProductUnit;
use OroB2B\Bundle\ShoppingListBundle\Entity\LineItem;
use OroB2B\Bundle\ShoppingListBundle\Entity\ShoppingList;
use OroB2B\Bundle\ShoppingListBundle\EventListener\FrontendProductDatagridListener;

class FrontendProductDatagridListenerTest extends \PHPUnit_Framework_TestCase
{
    use EntityTrait;

    /**
     * @var SecurityFacade|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $securityFacade;

    /**
     * @var FrontendProductDatagridListener
     */
    protected $listener;

    public function setUp()
    {
        $this->securityFacade = $this->getMockBuilder('Oro\Bundle\SecurityBundle\SecurityFacade')
            ->disableOriginalConstructor()
            ->getMock();

        $this->listener = new FrontendProductDatagridListener(
            $this->securityFacade
        );
    }

    protected function tearDown()
    {
        unset($this->securityFacade, $this->listener);
    }

    public function testOnPreBuild()
    {
        $config = DatagridConfiguration::createNamed('grid-name', []);
        $event = new PreBuild($config, new ParameterBag());

        $this->listener->onPreBuild($event);

        $this->assertEquals(
            [
                'name' => 'grid-name',
                'properties' => [
                    FrontendProductDatagridListener::COLUMN_LINE_ITEMS => [
                        'type' => 'field',
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
        /** @var OrmResultAfter|\PHPUnit_Framework_MockObject_MockObject $event */
        $event = $this->getMockBuilder('Oro\Bundle\DataGridBundle\Event\OrmResultAfter')
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
        $datagrid = $this->getMock('Oro\Bundle\DataGridBundle\Datagrid\DatagridInterface');

        $records = [
            $this->getMock('Oro\Bundle\DataGridBundle\Datagrid\DatagridInterface')
        ];

        $shoppingListRepository = $this
            ->getMockBuilder('OroB2B\Bundle\ShoppingListBundle\Entity\Repository\ShoppingListRepository')
            ->disableOriginalConstructor()
            ->getMock();
        $shoppingListRepository->expects($this->once())
            ->method('findAvailableForAccountUser')
            ->with($user)
            ->willReturn(null);
        $em = $this->getMock('Doctrine\ORM\EntityManagerInterface');
        $em->expects($this->once())
            ->method('getRepository')
            ->with('OroB2BShoppingListBundle:ShoppingList')
            ->willReturn($shoppingListRepository);
        $query = $this->getMockBuilder('Doctrine\ORM\AbstractQuery')
            ->disableOriginalConstructor()
            ->setMethods(['getEntityManager'])
            ->getMockForAbstractClass();
        $query->expects($this->once())
            ->method('getEntityManager')
            ->willReturn($em);

        /** @var OrmResultAfter|\PHPUnit_Framework_MockObject_MockObject $event */
        $event = new OrmResultAfter($datagrid, $records, $query);

        $this->securityFacade->expects($this->once())
            ->method('getLoggedUser')
            ->willReturn($user);

        $this->listener->onResultAfter($event);
    }

    public function testOnResultAfterNoRecords()
    {
        $user = new AccountUser();

        /** @var DatagridInterface|\PHPUnit_Framework_MockObject_MockObject $datagrid */
        $datagrid = $this->getMock('Oro\Bundle\DataGridBundle\Datagrid\DatagridInterface');

        $records = [];

        $shoppingListRepository = $this
            ->getMockBuilder('OroB2B\Bundle\ShoppingListBundle\Entity\Repository\ShoppingListRepository')
            ->disableOriginalConstructor()
            ->getMock();
        $shoppingListRepository->expects($this->once())
            ->method('findAvailableForAccountUser')
            ->with($user)
            ->willReturn(new ShoppingList());
        $em = $this->getMock('Doctrine\ORM\EntityManagerInterface');
        $em->expects($this->once())
            ->method('getRepository')
            ->with('OroB2BShoppingListBundle:ShoppingList')
            ->willReturn($shoppingListRepository);
        $query = $this->getMockBuilder('Doctrine\ORM\AbstractQuery')
            ->disableOriginalConstructor()
            ->setMethods(['getEntityManager'])
            ->getMockForAbstractClass();
        $query->expects($this->once())
            ->method('getEntityManager')
            ->willReturn($em);

        /** @var OrmResultAfter|\PHPUnit_Framework_MockObject_MockObject $event */
        $event = new OrmResultAfter($datagrid, $records, $query);

        $this->securityFacade->expects($this->once())
            ->method('getLoggedUser')
            ->willReturn($user);

        $this->listener->onResultAfter($event);
    }

    public function testOnResultAfter()
    {
        $user = new AccountUser();
        $shoppingList = new ShoppingList();

        /** @var DatagridInterface|\PHPUnit_Framework_MockObject_MockObject $datagrid */
        $datagrid = $this->getMock('Oro\Bundle\DataGridBundle\Datagrid\DatagridInterface');

        $recordOne = new ResultRecord(['id' => 1]);
        $recordTwo = new ResultRecord(['id' => 2]);
        $records = [$recordOne, $recordTwo];

        $shoppingListRepository = $this
            ->getMockBuilder('OroB2B\Bundle\ShoppingListBundle\Entity\Repository\ShoppingListRepository')
            ->disableOriginalConstructor()
            ->getMock();
        $shoppingListRepository->expects($this->once())
            ->method('findAvailableForAccountUser')
            ->with($user)
            ->willReturn($shoppingList);

        /** @var Product $productOne */
        $productOne = $this->getEntity('OroB2B\Bundle\ProductBundle\Entity\Product', ['id' => 1]);
        $lineItemRepository = $this->getMockBuilder('LineItemRepository')
                                    ->setMethods(['getProductItemsWithShoppingListNames'])
                                    ->getMock();
        $shoppingList1 = $this->createShoppingList(1, 'Shopping List1');
        $shoppingList2 = $this->createShoppingList(2, 'Shopping List2');

        $lineItemRepository->expects($this->once())
            ->method('getProductItemsWithShoppingListNames')
            ->with([1, 2], $user)
            ->willReturn(
                [
                    (new LineItem())
                        ->setUnit((new ProductUnit())->setCode('unt1'))
                        ->setQuantity(1)
                        ->setShoppingList($shoppingList1)
                        ->setProduct($productOne),
                    (new LineItem())
                        ->setUnit((new ProductUnit())->setCode('unt2'))
                        ->setQuantity(2)
                        ->setShoppingList($shoppingList2)
                        ->setProduct($productOne),
                    (new LineItem())
                        ->setUnit((new ProductUnit())->setCode('unt3'))
                        ->setQuantity(5)
                        ->setShoppingList($shoppingList2)
                        ->setProduct($productOne)
                ]
            );

        $em = $this->getMock('Doctrine\ORM\EntityManagerInterface');
        $em->expects($this->any())
            ->method('getRepository')
            ->willReturnMap(
                [
                    ['OroB2BShoppingListBundle:ShoppingList', $shoppingListRepository],
                    ['OroB2BShoppingListBundle:LineItem', $lineItemRepository],
                ]
            );
        $query = $this->getMockBuilder('Doctrine\ORM\AbstractQuery')
            ->disableOriginalConstructor()
            ->setMethods(['getEntityManager'])
            ->getMockForAbstractClass();
        $query->expects($this->once())
            ->method('getEntityManager')
            ->willReturn($em);

        /** @var OrmResultAfter|\PHPUnit_Framework_MockObject_MockObject $event */
        $event = new OrmResultAfter($datagrid, $records, $query);

        $this->securityFacade->expects($this->once())
            ->method('getLoggedUser')
            ->willReturn($user);

        $this->listener->onResultAfter($event);

        $this->assertEquals(
            [
                [
                'shopping_list_id' => 1,
                'shopping_list_label' => 'Shopping List1',
                'line_items' => [['unit'=>'unt1','quantity' => 1]]
                ],
                [
                    'shopping_list_id' => 2,
                    'shopping_list_label' => 'Shopping List2',
                    'line_items' => [
                        ['unit'=>'unt2','quantity' => 2],
                        ['unit'=>'unt3','quantity' => 5],
                    ],
                ],
            ],
            $recordOne->getValue('shopping_lists')
        );
        $this->assertEmpty($recordTwo->getValue('shopping_lists'));
    }

    /*
     * @param int $id
     * @param string $label
     *
     * @return  Mock_ShoppingList
     *
     */
    private function createShoppingList($id, $label)
    {
        $shoppingList = $this
            ->getMockBuilder('OroB2B\Bundle\ShoppingListBundle\Entity\ShoppingList')
            ->setMethods(['getId', 'getLabel'])
            ->getMock();
        $shoppingList ->expects($this->any())
            ->method('getId')
            ->will($this->returnValue($id));
        $shoppingList ->expects($this->any())
            ->method('getLabel')
            ->will($this->returnValue($label));
        return $shoppingList;
    }
}
