<?php

namespace OroB2B\Bundle\ShoppingListBundle\Tests\Unit\Datagrid\Extension\MassAction;

use Doctrine\Bundle\DoctrineBundle\Registry;
use Doctrine\ORM\AbstractQuery;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\QueryBuilder;

use Symfony\Component\Routing\RouterInterface;
use Symfony\Component\Translation\TranslatorInterface;

use Oro\Bundle\SecurityBundle\SecurityFacade;
use Oro\Bundle\DataGridBundle\Extension\MassAction\MassActionHandlerArgs;

use OroB2B\Bundle\AccountBundle\Entity\AccountUser;
use OroB2B\Bundle\ProductBundle\Entity\Product;
use OroB2B\Bundle\ProductBundle\Entity\ProductUnit;
use OroB2B\Bundle\ProductBundle\Entity\ProductUnitPrecision;
use OroB2B\Bundle\ShoppingListBundle\Entity\ShoppingList;
use OroB2B\Bundle\ShoppingListBundle\Manager\ShoppingListManager;
use OroB2B\Bundle\ShoppingListBundle\DataGrid\Extension\MassAction\AddProductsMassAction;
use OroB2B\Bundle\ShoppingListBundle\DataGrid\Extension\MassAction\AddProductsMassActionHandler;

class AddProductsMassActionHandlerTest extends \PHPUnit_Framework_TestCase
{
    /** @var AddProductsMassActionHandler */
    protected $handler;

    /** @var  MassActionHandlerArgs */
    protected $args;

    /** @var \PHPUnit_Framework_MockObject_MockObject|SecurityFacade */
    protected $securityFacade;

    /** @var \PHPUnit_Framework_MockObject_MockObject|ShoppingListManager  */
    protected $shoppingListManager;

    protected function setUp()
    {
        $this->securityFacade = $this->getSecurityFacade();
        $this->shoppingListManager = $this->getShoppingListManager();

        $this->handler = new AddProductsMassActionHandler(
            $this->getManagerRegistry(),
            $this->shoppingListManager,
            $this->getTranslator(),
            $this->securityFacade,
            $this->getRouter()
        );
    }

    public function testHandle()
    {
        $shoppingList = $this->shoppingListManager->getForCurrentUser();

        $this->securityFacade->expects($this->at(0))
            ->method('isGranted')
            ->with('EDIT', $shoppingList)
            ->willReturn(true);

        $this->securityFacade->expects($this->at(1))
            ->method('isGranted')
            ->with('orob2b_shopping_list_line_item_frontend_add')
            ->willReturn(true);

        $args = $this->getMassActionArgs();
        $args->expects($this->any())
            ->method('getData')
            ->willReturn(['shoppingList' => null]);

        $response = $this->handler->handle($args);
        $this->assertTrue($response->isSuccessful());
        $this->assertEquals(2, $response->getOptions()['count']);
    }

    public function testHandleNoPermissions()
    {
        $this->securityFacade->expects($this->once())
            ->method('isGranted')
            ->willReturn(false);

        $args = $this->getMassActionArgs();
        $args->expects($this->any())
            ->method('getData')
            ->willReturn(['shoppingList' => 1]);

        $response = $this->handler->handle($args);
        $this->assertFalse($response->isSuccessful());
        $this->assertEquals(0, $response->getOptions()['count']);
    }

    /**
     * @return \PHPUnit_Framework_MockObject_MockObject|MassActionHandlerArgs
     */
    protected function getMassActionArgs()
    {
        $args = $this->getMockBuilder('Oro\Bundle\DataGridBundle\Extension\MassAction\MassActionHandlerArgs')
            ->disableOriginalConstructor()
            ->getMock();
        $args->expects($this->any())
            ->method('getMassAction')
            ->willReturn(new AddProductsMassAction());

        return $args;
    }

    /**
     * @return TranslatorInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    protected function getTranslator()
    {
        $translator = $this->getMockBuilder('Symfony\Component\Translation\TranslatorInterface')
            ->getMock();
        $translator->expects($this->any())
            ->method('transChoice')
            ->willReturnCallback(function ($string) {
                return $string;
            });

        return $translator;
    }

    /**
     * @return ShoppingListManager|\PHPUnit_Framework_MockObject_MockObject
     */
    protected function getShoppingListManager()
    {
        /** @var \PHPUnit_Framework_MockObject_MockObject|ShoppingList $shoppingList */
        $shoppingList = $this->getMock('OroB2B\Bundle\ShoppingListBundle\Entity\ShoppingList');
        $shoppingList->expects($this->any())
            ->method('getId')
            ->willReturn(1);

        $shoppingList->expects($this->any())
            ->method('getOwner')
            ->willReturn(new AccountUser());

        $shoppingListManager = $this->getMockBuilder('OroB2B\Bundle\ShoppingListBundle\Manager\ShoppingListManager')
            ->disableOriginalConstructor()
            ->getMock();

        $shoppingListManager->expects($this->any())
            ->method('createCurrent')
            ->willReturn($shoppingList);

        $shoppingListManager->expects($this->any())
            ->method('getForCurrentUser')
            ->willReturn($shoppingList);

        $shoppingListManager->expects($this->any())
            ->method('bulkAddLineItems')
            ->willReturnCallback(function (array $lineItems) {
                return count($lineItems);
            });

        return $shoppingListManager;
    }

    /**
     * @return SecurityFacade|\PHPUnit_Framework_MockObject_MockObject
     */
    protected function getSecurityFacade()
    {
        return $this->getMockBuilder('Oro\Bundle\SecurityBundle\SecurityFacade')
            ->disableOriginalConstructor()
            ->getMock();
    }

    /**
     * @return RouterInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    protected function getRouter()
    {
        return $this->getMock('Symfony\Component\Routing\RouterInterface');
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
            ->will($this->returnValueMap([
                ['OroB2BShoppingListBundle:ShoppingList', $shoppingListRepository],
                ['OroB2BProductBundle:Product', $productRepository]
            ]));

        /** @var \PHPUnit_Framework_MockObject_MockObject|Registry $managerRegistry */
        $managerRegistry = $this->getMockBuilder('Doctrine\Bundle\DoctrineBundle\Registry')
            ->disableOriginalConstructor()
            ->getMock();

        $managerRegistry->expects($this->any())
            ->method('getManagerForClass')
            ->willReturn($em);

        return $managerRegistry;
    }
}
