<?php

namespace OroB2B\Bundle\ShoppingListBundle\Tests\Unit\Datagrid\Extension\MassAction;

use Symfony\Component\Security\Core\Exception\AccessDeniedException;

use Oro\Bundle\DataGridBundle\Extension\MassAction\MassActionHandlerArgs;

use OroB2B\Bundle\AccountBundle\Entity\AccountUser;
use OroB2B\Bundle\ShoppingListBundle\DataGrid\Extension\MassAction\AddProductsMassAction;
use OroB2B\Bundle\ShoppingListBundle\DataGrid\Extension\MassAction\AddProductsMassActionHandler;
use OroB2B\Bundle\ShoppingListBundle\Entity\ShoppingList;
use OroB2B\Bundle\ShoppingListBundle\Generator\MessageGenerator;
use OroB2B\Bundle\ShoppingListBundle\Handler\ShoppingListLineItemHandler;

class AddProductsMassActionHandlerTest extends \PHPUnit_Framework_TestCase
{
    const MESSAGE = 'test message';

    /** @var AddProductsMassActionHandler */
    protected $handler;

    /** @var  MassActionHandlerArgs */
    protected $args;

    /** @var \PHPUnit_Framework_MockObject_MockObject|ShoppingListLineItemHandler */
    protected $shoppingListItemHandler;

    protected function setUp()
    {
        $this->shoppingListItemHandler = $this->getShoppingListItemHandler();

        $this->handler = new AddProductsMassActionHandler($this->shoppingListItemHandler, $this->getMessageGenerator());
    }

    public function testHandleMissingShoppingList()
    {
        $args = $this->getMassActionArgs();
        $args->expects($this->any())
            ->method('getData')
            ->willReturn(['shoppingList' => null]);
        $this->shoppingListItemHandler->expects($this->once())->method('getShoppingList')->willReturn(null);
        $response = $this->handler->handle($args);
        $this->assertFalse($response->isSuccessful());
        $this->assertEquals(0, $response->getOptions()['count']);
    }

    public function testHandleAccessDenied()
    {
        $args = $this->getMassActionArgs();
        $args->expects($this->any())
            ->method('getData')
            ->willReturn(['shoppingList' => 1]);
        $this->shoppingListItemHandler->expects($this->once())->method('getShoppingList')
            ->willReturn(new ShoppingList());
        $this->shoppingListItemHandler->expects($this->once())->method('createForShoppingList')
            ->willThrowException(new AccessDeniedException());

        $response = $this->handler->handle($args);
        $this->assertFalse($response->isSuccessful());
        $this->assertEquals(0, $response->getOptions()['count']);
    }

    public function testHandle()
    {
        $args = $this->getMassActionArgs();
        $args->expects($this->any())
            ->method('getData')
            ->willReturn(['shoppingList' => 1]);
        $this->shoppingListItemHandler->expects($this->once())->method('getShoppingList')
            ->willReturn($this->getEntity('OroB2B\Bundle\ShoppingListBundle\Entity\ShoppingList', 1));
        $this->shoppingListItemHandler->expects($this->once())->method('createForShoppingList')->willReturn(2);

        $response = $this->handler->handle($args);
        $this->assertTrue($response->isSuccessful());
        $this->assertEquals(2, $response->getOptions()['count']);
        $this->assertEquals(self::MESSAGE, $response->getMessage());
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
     * @return MessageGenerator|\PHPUnit_Framework_MockObject_MockObject
     */
    protected function getMessageGenerator()
    {
        $translator = $this->getMockBuilder('OroB2B\Bundle\ShoppingListBundle\Generator\MessageGenerator')
            ->disableOriginalConstructor()
            ->getMock();
        $translator->expects($this->any())
            ->method('getSuccessMessage')
            ->willReturn(self::MESSAGE);

        return $translator;
    }

    /**
     * @return ShoppingListLineItemHandler|\PHPUnit_Framework_MockObject_MockObject
     */
    protected function getShoppingListItemHandler()
    {
        /** @var \PHPUnit_Framework_MockObject_MockObject|ShoppingList $shoppingList */
        $shoppingList = $this->getMock('OroB2B\Bundle\ShoppingListBundle\Entity\ShoppingList');
        $shoppingList->expects($this->any())
            ->method('getId')
            ->willReturn(1);

        $shoppingList->expects($this->any())
            ->method('getAccountUser')
            ->willReturn(new AccountUser());

        $shoppingListItemHandler = $this
            ->getMockBuilder('OroB2B\Bundle\ShoppingListBundle\Handler\ShoppingListLineItemHandler')
            ->disableOriginalConstructor()
            ->getMock();

        $shoppingListItemHandler->expects($this->any())
            ->method('createCurrent')
            ->willReturn($shoppingList);

        $shoppingListItemHandler->expects($this->any())
            ->method('getForCurrentUser')
            ->willReturn($shoppingList);

        $shoppingListItemHandler->expects($this->any())
            ->method('bulkAddLineItems')
            ->willReturnCallback(
                function (array $lineItems) {
                    return count($lineItems);
                }
            );

        return $shoppingListItemHandler;
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
