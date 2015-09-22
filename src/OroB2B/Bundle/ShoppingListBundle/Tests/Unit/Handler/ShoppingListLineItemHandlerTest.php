<?php

namespace OroB2B\Bundle\ShoppingListBundle\Tests\Unit\Handler;

use Doctrine\Bundle\DoctrineBundle\Registry;
use Doctrine\Common\Persistence\ManagerRegistry;
use Doctrine\ORM\AbstractQuery;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\QueryBuilder;

use Oro\Bundle\SecurityBundle\SecurityFacade;

use OroB2B\Bundle\AccountBundle\Entity\AccountUser;
use OroB2B\Bundle\ProductBundle\Entity\ProductUnit;
use OroB2B\Bundle\ProductBundle\Entity\ProductUnitPrecision;
use OroB2B\Bundle\ShoppingListBundle\Entity\LineItem;
use OroB2B\Bundle\ShoppingListBundle\Entity\ShoppingList;
use OroB2B\Bundle\ShoppingListBundle\Handler\ShoppingListLineItemHandler;
use OroB2B\Bundle\ShoppingListBundle\Manager\ShoppingListManager;

class ShoppingListLineItemHandlerTest extends \PHPUnit_Framework_TestCase
{
    /** @var ShoppingListLineItemHandler */
    protected $handler;

    /** @var \PHPUnit_Framework_MockObject_MockObject|SecurityFacade */
    protected $securityFacade;

    /** @var \PHPUnit_Framework_MockObject_MockObject|ShoppingListManager */
    protected $manager;

    /** @var \PHPUnit_Framework_MockObject_MockObject|ManagerRegistry */
    protected $managerRegistry;

    protected function setUp()
    {
        $this->securityFacade = $this->getMockBuilder('Oro\Bundle\SecurityBundle\SecurityFacade')
            ->disableOriginalConstructor()
            ->getMock();

        $this->manager = $this->getMockBuilder('OroB2B\Bundle\ShoppingListBundle\Manager\ShoppingListManager')
            ->disableOriginalConstructor()
            ->getMock();

        $this->managerRegistry = $this->getManagerRegistry();

        $this->handler = new ShoppingListLineItemHandler($this->managerRegistry, $this->manager, $this->securityFacade);
        $this->handler->setProductClass('OroB2B\Bundle\ProductBundle\Entity\Product');
        $this->handler->setShoppingListClass('OroB2B\Bundle\ShoppingListBundle\Entity\ShoppingList');
    }

    /**
     * @dataProvider idDataProvider
     * @param mixed $id
     */
    public function testGetShoppingList($id)
    {
        $shoppingList = new ShoppingList();
        $this->manager->expects($this->once())->method('getForCurrentUser')->willReturn($shoppingList);
        $this->assertSame($shoppingList, $this->handler->getShoppingList($id));
    }

    /**
     * @return array
     */
    public function idDataProvider()
    {
        return [[1], [null]];
    }

    /**
     * @expectedException \Symfony\Component\Security\Core\Exception\AccessDeniedException
     */
    public function testCreateForShoppingListWithoutPermission()
    {
        $this->securityFacade->expects($this->once())
            ->method('hasLoggedUser')
            ->willReturn(true);

        $this->securityFacade->expects($this->once())
            ->method('isGranted')
            ->willReturn(false);

        $this->handler->createForShoppingList(new ShoppingList());
    }

    /**
     * @expectedException \Symfony\Component\Security\Core\Exception\AccessDeniedException
     */
    public function testCreateForShoppingListWithoutUser()
    {
        $this->securityFacade->expects($this->once())
            ->method('hasLoggedUser')
            ->willReturn(false);

        $this->securityFacade->expects($this->never())
            ->method('isGranted');

        $this->handler->createForShoppingList(new ShoppingList());
    }

    /**
     * @param bool $isGrantedAdd
     * @param bool $expected
     * @param bool $isGrantedEdit
     * @param ShoppingList|null $shoppingList
     *
     * @dataProvider isAllowedDataProvider
     */
    public function testIsAllowed($isGrantedAdd, $expected, ShoppingList $shoppingList = null, $isGrantedEdit = false)
    {
        $this->securityFacade->expects($this->once())
            ->method('hasLoggedUser')
            ->willReturn(true);

        $this->securityFacade->expects($this->at(1))->method('isGranted')
            ->with('orob2b_shopping_list_line_item_frontend_add')
            ->willReturn($isGrantedAdd);

        if ($shoppingList && $isGrantedAdd) {
            $this->securityFacade
                ->expects($this->at(2))
                ->method('isGranted')
                ->with('EDIT', $shoppingList)
                ->willReturn($isGrantedEdit);
        }

        $this->assertEquals($expected, $this->handler->isAllowed($shoppingList));
    }

    /** @return array */
    public function isAllowedDataProvider()
    {
        return [
            [false, false],
            [true, true],
            [false, false, new ShoppingList(), false],
            [false, false, new ShoppingList(), true],
            [true, false, new ShoppingList(), false],
            [true, true, new ShoppingList(), true],
        ];
    }

    /**
     * @param array $productIds
     * @param array $productQuantities
     * @param array $expectedLineItems
     *
     * @dataProvider itemDataProvider
     */
    public function testCreateForShoppingList(
        array $productIds = [],
        array $productQuantities = [],
        array $expectedLineItems = []
    ) {
        /** @var \PHPUnit_Framework_MockObject_MockObject|ShoppingList $shoppingList */
        $shoppingList = $this->getMock('OroB2B\Bundle\ShoppingListBundle\Entity\ShoppingList');
        $shoppingList->expects($this->any())
            ->method('getId')
            ->willReturn(1);

        $accountUser = new AccountUser();

        $shoppingList->expects($this->any())
            ->method('getAccountUser')
            ->willReturn($accountUser);

        $this->securityFacade->expects($this->any())
            ->method('hasLoggedUser')
            ->willReturn(true);
        $this->securityFacade->expects($this->any())->method('isGranted')->willReturn(true);

        $this->manager->expects($this->once())->method('bulkAddLineItems')->with(
            $this->callback(
                function (array $lineItems) use ($expectedLineItems, $accountUser) {
                    /** @var LineItem $lineItem */
                    foreach ($lineItems as $key => $lineItem) {
                        /** @var LineItem $expectedLineItem */
                        $expectedLineItem = $expectedLineItems[$key];

                        $this->assertEquals($expectedLineItem->getQuantity(), $lineItem->getQuantity());
                        $this->assertEquals($accountUser, $lineItem->getAccountUser());
                        $this->assertInstanceOf('OroB2B\Bundle\ProductBundle\Entity\Product', $lineItem->getProduct());
                        $this->assertInstanceOf(
                            'OroB2B\Bundle\ProductBundle\Entity\ProductUnit',
                            $lineItem->getUnit()
                        );
                    }

                    return true;
                }
            ),
            $shoppingList,
            $this->isType('integer')
        );

        $this->handler->createForShoppingList($shoppingList, $productIds, $productQuantities);
    }

    /**
     * @return array
     */
    public function itemDataProvider()
    {
        return [
            [
                [1, 2],
                [1 => 5],
                [(new LineItem())->setQuantity(5), (new LineItem())->setQuantity(1)],
            ],
        ];
    }

    /**
     * @return Registry|\PHPUnit_Framework_MockObject_MockObject
     */
    protected function getManagerRegistry()
    {
        /** @var EntityManager|\PHPUnit_Framework_MockObject_MockObject $em */
        $em = $this->getMockBuilder('Doctrine\ORM\EntityManager')->disableOriginalConstructor()->getMock();

        /** @var AbstractQuery|\PHPUnit_Framework_MockObject_MockObject $query */
        $query = $this->getMockBuilder('Doctrine\ORM\AbstractQuery')
            ->disableOriginalConstructor()
            ->setMethods(['iterate'])
            ->getMockForAbstractClass();

        $product1 = $this->getEntity('OroB2B\Bundle\ProductBundle\Entity\Product', 1)
            ->addUnitPrecision(
                (new ProductUnitPrecision())->setUnit(new ProductUnit())
            );

        $product2 = $this->getEntity('OroB2B\Bundle\ProductBundle\Entity\Product', 2)
            ->addUnitPrecision(
                (new ProductUnitPrecision())->setUnit(new ProductUnit())
            );

        $iterableResult = [[$product1], [$product2]];
        $query->expects($this->any())
            ->method('iterate')
            ->willReturn($iterableResult);

        /** @var QueryBuilder|\PHPUnit_Framework_MockObject_MockObject $queryBuilder */
        $queryBuilder = $this->getMockBuilder('Doctrine\ORM\QueryBuilder')
            ->disableOriginalConstructor()
            ->getMock();

        $queryBuilder->expects($this->any())
            ->method('getQuery')
            ->willReturn($query);

        /** @var EntityRepository|\PHPUnit_Framework_MockObject_MockObject $productRepository */
        $productRepository = $this->getMockBuilder('Doctrine\ORM\EntityRepository')
            ->disableOriginalConstructor()
            ->setMethods(['getProductsQueryBuilder'])
            ->getMock();

        $productRepository->expects($this->any())
            ->method('getProductsQueryBuilder')
            ->willReturn($queryBuilder);

        /** @var EntityRepository|\PHPUnit_Framework_MockObject_MockObject $shoppingListRepository */
        $shoppingListRepository = $this->getMockBuilder('Doctrine\ORM\EntityRepository')
            ->disableOriginalConstructor()
            ->getMock();

        $em->expects($this->any())
            ->method('getRepository')
            ->will(
                $this->returnValueMap(
                    [
                        ['OroB2B\Bundle\ShoppingListBundle\Entity\ShoppingList', $shoppingListRepository],
                        ['OroB2B\Bundle\ProductBundle\Entity\Product', $productRepository],
                    ]
                )
            );

        $em->expects($this->any())->method('getReference')->will(
            $this->returnCallback(
                function ($className, $id) {
                    return $this->getEntity($className, $id);
                }
            )
        );

        /** @var \PHPUnit_Framework_MockObject_MockObject|Registry $managerRegistry */
        $managerRegistry = $this->getMockBuilder('Doctrine\Bundle\DoctrineBundle\Registry')
            ->disableOriginalConstructor()
            ->getMock();

        $managerRegistry->expects($this->any())
            ->method('getManagerForClass')
            ->willReturn($em);

        return $managerRegistry;
    }

    /**
     * @param string $className
     * @param int $id
     * @return object
     */
    protected function getEntity($className, $id)
    {
        $entity = new $className;

        $reflectionClass = new \ReflectionClass($className);
        $method = $reflectionClass->getProperty('id');
        $method->setAccessible(true);
        $method->setValue($entity, $id);

        return $entity;
    }
}
