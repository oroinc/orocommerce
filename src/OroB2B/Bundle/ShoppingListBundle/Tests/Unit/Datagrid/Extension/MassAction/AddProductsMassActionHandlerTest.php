<?php

namespace OroB2B\Bundle\ShoppingListBundle\Tests\Unit\Datagrid\Extension\MassAction;

use Doctrine\Bundle\DoctrineBundle\Registry;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\QueryBuilder;
use Doctrine\ORM\AbstractQuery;

use Oro\Bundle\DataGridBundle\Extension\MassAction\MassActionHandlerArgs;

use OroB2B\Bundle\CustomerBundle\Entity\AccountUser;
use OroB2B\Bundle\ProductBundle\Entity\Product;
use OroB2B\Bundle\ProductBundle\Entity\ProductUnit;
use OroB2B\Bundle\ProductBundle\Entity\ProductUnitPrecision;
use OroB2B\Bundle\ShoppingListBundle\DataGrid\Extension\MassAction\AddProductsMassAction;
use OroB2B\Bundle\ShoppingListBundle\Entity\ShoppingList;
use OroB2B\Bundle\ShoppingListBundle\DataGrid\Extension\MassAction\AddProductsMassActionHandler;
use OroB2B\Bundle\ShoppingListBundle\DataGrid\Extension\MassAction\AddProductsMassActionArgsParser as ArgsParser;

class AddProductsMassActionHandlerTest extends \PHPUnit_Framework_TestCase
{
    /** @var AddProductsMassActionHandler */
    protected $handler;

    /** @var  MassActionHandlerArgs */
    protected $args;

    protected function setUp()
    {
        $this->handler = new AddProductsMassActionHandler(
            $this->getManagerRegistry(),
            $this->getShoppingListManager(),
            $this->getTranslator(),
            $this->getSecurityContext()
        );
    }

    public function testHandle()
    {
        $args = $this->getMassActionArgs();
        $args->expects($this->any())
            ->method('getData')
            ->willReturn(['shoppingList' => ArgsParser::CURRENT_SHOPPING_LIST_KEY]);

        $response = $this->handler->handle($args);
        $this->assertTrue($response->isSuccessful());
        $this->assertEquals(2, $response->getOptions()['count']);
    }

    /**
     * @return \PHPUnit_Framework_MockObject_MockObject
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
     * @return \PHPUnit_Framework_MockObject_MockObject
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
     * @return \PHPUnit_Framework_MockObject_MockObject
     */
    protected function getShoppingListManager()
    {
        $shoppingListManager = $this->getMockBuilder('OroB2B\Bundle\ShoppingListBundle\Manager\ShoppingListManager')
            ->disableOriginalConstructor()
            ->getMock();

        $shoppingListManager->expects($this->any())
            ->method('createCurrent')
            ->willReturn(new ShoppingList());

        $shoppingListManager->expects($this->any())
            ->method('getForCurrentUser')
            ->willReturn(new ShoppingList());

        $shoppingListManager->expects($this->once())
            ->method('bulkAddLineItems')
            ->willReturnCallback(function (array $lineItems) {
                return count($lineItems);
            });

        return $shoppingListManager;
    }

    /**
     * @return \PHPUnit_Framework_MockObject_MockObject
     */
    protected function getSecurityContext()
    {
        $context = $this->getMockBuilder('Symfony\Component\Security\Core\SecurityContextInterface')
            ->disableOriginalConstructor()
            ->getMock();

        $token = $this->getMockBuilder('Symfony\Component\Security\Core\Authentication\Token\TokenInterface')
            ->disableOriginalConstructor()
            ->getMock();

        $token->expects($this->any())
            ->method('getUser')
            ->willReturn(
                new AccountUser()
            );

        $context->expects($this->any())
            ->method('getToken')
            ->willReturn($token);

        $context->expects($this->any())
            ->method('isGranted')
            ->willReturn(true);

        return $context;
    }

    /**
     * @return \PHPUnit_Framework_MockObject_MockObject
     */
    protected function getManagerRegistry()
    {
        $em = $this->getMockBuilder('Doctrine\ORM\EntityManager')->disableOriginalConstructor()->getMock();

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

        $queryBuilder = $this->getMockBuilder('Doctrine\ORM\QueryBuilder')
            ->disableOriginalConstructor()
            ->getMock();

        $queryBuilder->expects($this->any())
            ->method('getQuery')
            ->willReturn($query);

        $productRepository = $this->getMockBuilder('Doctrine\ORM\EntityRepository')
            ->disableOriginalConstructor()
            ->setMethods(['getProductsQueryBuilder'])
            ->getMock();

        $productRepository->expects($this->any())
            ->method('getProductsQueryBuilder')
            ->willReturn($queryBuilder);

        $shoppingListRepository = $this->getMockBuilder('Doctrine\ORM\EntityRepository')
            ->disableOriginalConstructor()
            ->getMock();

        $em->expects($this->any())
            ->method('getRepository')
            ->will($this->returnValueMap([
                ['OroB2BShoppingListBundle:ShoppingList', $shoppingListRepository],
                ['OroB2BProductBundle:Product', $productRepository]
            ]));

        $managerRegistry = $this->getMockBuilder('Doctrine\Bundle\DoctrineBundle\Registry')
            ->disableOriginalConstructor()->getMock();

        $managerRegistry->expects($this->any())
            ->method('getManagerForClass')
            ->willReturn($em);

        return $managerRegistry;
    }
}
