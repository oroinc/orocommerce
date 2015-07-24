<?php

namespace OroB2B\Bundle\ShoppingListBundle\Tests\Unit\Datagrid\Extension\MassAction;

use Doctrine\ORM\EntityManager;
use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\QueryBuilder;
use Doctrine\ORM\AbstractQuery;

use Oro\Bundle\DataGridBundle\Extension\MassAction\MassActionHandlerArgs;
use Oro\Bundle\DataGridBundle\Extension\MassAction\MassActionResponse;

use OroB2B\Bundle\CustomerBundle\Entity\AccountUser;
use OroB2B\Bundle\ProductBundle\Entity\Product;
use OroB2B\Bundle\ProductBundle\Entity\ProductUnit;
use OroB2B\Bundle\ProductBundle\Entity\ProductUnitPrecision;
use OroB2B\Bundle\ShoppingListBundle\DataGrid\Extension\MassAction\AddProductsMassAction;
use OroB2B\Bundle\ShoppingListBundle\Entity\ShoppingList;
use OroB2B\Bundle\ShoppingListBundle\DataGrid\Extension\MassAction\AddProductsMassActionHandler;

class AddProductsMassActionHandlerTest extends \PHPUnit_Framework_TestCase
{
    /** @var AddProductsMassActionHandler */
    protected $handler;

    /** @var  MassActionHandlerArgs */
    protected $args;

    protected function setUp()
    {
        $this->args = $this->getMockBuilder('Oro\Bundle\DataGridBundle\Extension\MassAction\MassActionHandlerArgs')
            ->disableOriginalConstructor()
            ->getMock();

        $entityManager = $this->getEntityManager();
        $shoppingListManager = $this->getShoppingListManager();

        $translator = $this->getTranslator();
        $securityContext = $this->getSecurityContext();
        $serviceLink = $this->getMockBuilder('Oro\Bundle\EntityConfigBundle\DependencyInjection\Utils\ServiceLink')
            ->disableOriginalConstructor()
            ->getMock();

        $this->handler = new AddProductsMassActionHandler(
            $entityManager,
            $shoppingListManager,
            $translator,
            $securityContext,
            $serviceLink
        );
    }

    public function testHandle()
    {
        $args = $this->getMassActionArgs();
        $args->expects($this->any())
            ->method('getData')
            ->willReturn(['shoppingList' => AddProductsMassActionHandler::CURRENT_SHOPPING_LIST_KEY]);

        $method = $this->getHandlerMethod('handle');
        /** @var MassActionResponse $response */
        $response = $method->invokeArgs($this->handler, [$args]);
        $this->assertTrue($response->isSuccessful());
        $this->assertEquals(2, $response->getOptions()['count']);
    }

    public function testGetProductsQueryBuilder()
    {
        $method = $this->getHandlerMethod('getProductsQueryBuilder');
        /** @var QueryBuilder $builder */
        $builder = $method->invokeArgs($this->handler, []);
        $this->assertInstanceOf('Doctrine\ORM\QueryBuilder', $builder);
    }

    public function testIsAllSelected()
    {
        $method = $this->getHandlerMethod('isAllSelected');
        $result = $method->invokeArgs(
            $this->handler,
            [['inset' => '0']]
        );
        $this->assertTrue($result);
    }

    public function testGetShoppingList()
    {
        $method = $this->getHandlerMethod('getShoppingList');
        $shoppingList = $method->invokeArgs($this->handler, [AddProductsMassActionHandler::CURRENT_SHOPPING_LIST_KEY]);
        $this->assertInstanceOf('OroB2B\Bundle\ShoppingListBundle\Entity\ShoppingList', $shoppingList);
    }

    public function testGetResponse()
    {
        $args = $this->getMassActionArgs();
        $method = $this->getHandlerMethod('getResponse');
        /** @var MassActionResponse $result */
        $result = $method->invokeArgs($this->handler, [$args, 1]);
        $this->assertTrue($result->isSuccessful());
        $this->assertEquals($result->getMessage(), 'orob2b.shoppinglist.actions.add_success_message');
    }

    /**
     * @param string $methodName
     *
     * @return \ReflectionMethod
     */
    protected function getHandlerMethod($methodName)
    {
        $class = new \ReflectionClass(get_class($this->handler));
        $method = $class->getMethod($methodName);
        $method->setAccessible(true);

        return $method;
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

        return $shoppingListManager;
    }

    /**
     * @return \PHPUnit_Framework_MockObject_MockObject
     */
    protected function getSecurityContext()
    {
        $context = $this->getMockBuilder('Symfony\Component\Security\Core\SecurityContext')
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

        return $context;
    }

    /**
     * @return \PHPUnit_Framework_MockObject_MockObject
     */
    protected function getEntityManager()
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
            ->getMock();

        $productRepository->expects($this->any())
            ->method('createQueryBuilder')
            ->willReturn($queryBuilder);

        $shoppingListRepository = $this->getMockBuilder('Doctrine\ORM\EntityRepository')
            ->disableOriginalConstructor()
            ->getMock();

        $em->expects($this->any())
            ->method('getRepository')
            ->willReturnCallback(function ($entityName) use ($productRepository, $shoppingListRepository) {
                $repo = null;
                if ($entityName == 'OroB2BProductBundle:Product') {
                    $repo = $productRepository;
                }

                if ($entityName == 'OroB2BShoppingListBundle:ShoppingList') {
                    $repo = $shoppingListRepository;
                }

                return $repo;
            });

        return $em;
    }
}
