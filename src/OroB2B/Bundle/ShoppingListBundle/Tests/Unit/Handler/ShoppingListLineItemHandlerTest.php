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
use OroB2B\Bundle\ProductBundle\Entity\Product;
use OroB2B\Bundle\ProductBundle\Entity\ProductUnit;
use OroB2B\Bundle\ProductBundle\Entity\ProductUnitPrecision;
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
     * @expectedException \InvalidArgumentException
     * @expectedExceptionMessage Wrong ShoppingList id
     *
     * @dataProvider invalidIdDataProvider
     * @param mixed $id
     */
    public function testGetShoppingListInvalidIdNumeric($id)
    {
        $this->handler->getShoppingList($id);
    }

    /**
     * @return array
     */
    public function invalidIdDataProvider()
    {
        return [[0], ['a'], [-1], ['0']];
    }

    public function testGetShoppingList()
    {
        $id = 1;

        $entity = $this->handler->getShoppingList($id);

        $this->assertInstanceOf('OroB2B\Bundle\ShoppingListBundle\Entity\ShoppingList', $entity);
        $this->assertEquals($id, $entity->getId());
    }

    /**
     * @expectedException \Symfony\Component\Security\Core\Exception\AccessDeniedException
     */
    public function testCreateForShoppingListWithoutPermission()
    {
        $this->securityFacade->expects($this->once())
            ->method('isGranted')
            ->willReturn(false);

        $this->handler->createForShoppingList(new ShoppingList());
    }

    public function testCreateForShoppingList()
    {
        /** @var \PHPUnit_Framework_MockObject_MockObject|ShoppingList $shoppingList */
        $shoppingList = $this->getMock('OroB2B\Bundle\ShoppingListBundle\Entity\ShoppingList');
        $shoppingList->expects($this->any())
            ->method('getId')
            ->willReturn(1);

        $shoppingList->expects($this->any())
            ->method('getAccountUser')
            ->willReturn(new AccountUser());

        $this->securityFacade->expects($this->at(0))
            ->method('isGranted')
            ->with('EDIT', $shoppingList)
            ->willReturn(true);

        $this->securityFacade->expects($this->at(1))
            ->method('isGranted')
            ->with('orob2b_shopping_list_line_item_frontend_add')
            ->willReturn(true);

        $this->handler->createForShoppingList($shoppingList, [1]);
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

        $product = (new Product())
            ->addUnitPrecision(
                (new ProductUnitPrecision())->setUnit(new ProductUnit())
            );

        $iterableResult = [[$product], [clone $product]];
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
