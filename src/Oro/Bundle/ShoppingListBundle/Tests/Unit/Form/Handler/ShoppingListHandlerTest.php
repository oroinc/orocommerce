<?php

namespace Oro\Bundle\ShoppingListBundle\Tests\Unit\Form\Handler;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\CustomerBundle\Entity\CustomerUser;
use Oro\Bundle\ShoppingListBundle\Entity\ShoppingList;
use Oro\Bundle\ShoppingListBundle\Form\Handler\ShoppingListHandler;
use Oro\Bundle\ShoppingListBundle\Manager\CurrentShoppingListManager;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\Request;

class ShoppingListHandlerTest extends \PHPUnit\Framework\TestCase
{
    private const FORM_DATA = ['field' => 'value'];

    /** @var \PHPUnit\Framework\MockObject\MockObject|FormInterface */
    private $form;

    /** @var \PHPUnit\Framework\MockObject\MockObject|ManagerRegistry */
    private $doctrine;

    /** @var \PHPUnit\Framework\MockObject\MockObject|CurrentShoppingListManager */
    private $currentShoppingListManager;

    protected function setUp(): void
    {
        $this->form = $this->createMock(FormInterface::class);
        $this->currentShoppingListManager = $this->createMock(CurrentShoppingListManager::class);
        $this->doctrine = $this->createMock(ManagerRegistry::class);
    }

    public function testProcessWrongMethod()
    {
        $shoppingList = $this->createMock(ShoppingList::class);

        $handler = new ShoppingListHandler($this->currentShoppingListManager, $this->doctrine);
        $request = Request::create('/');
        $this->assertFalse($handler->process($shoppingList, $this->form, $request));
    }

    public function testProcessFormNotValid()
    {
        $shoppingList = $this->createMock(ShoppingList::class);

        $request = Request::create('/', 'POST', self::FORM_DATA);

        $this->form->expects($this->once())
            ->method('submit')
            ->with(self::FORM_DATA);
        $this->form->expects($this->once())
            ->method('isValid')
            ->willReturn(false);

        $handler = new ShoppingListHandler($this->currentShoppingListManager, $this->doctrine);
        $this->assertFalse($handler->process($shoppingList, $this->form, $request));
    }

    public function testProcessNotExistingShoppingList()
    {
        $customerUser = new CustomerUser();
        $shoppingList = $this->createMock(ShoppingList::class);
        $shoppingList->expects($this->once())
            ->method('getCustomerUser')
            ->willReturn($customerUser);

        $request = Request::create('/', 'PUT', self::FORM_DATA);

        $this->form->expects($this->once())
            ->method('submit')
            ->with(self::FORM_DATA);
        $this->form->expects($this->once())
            ->method('isValid')
            ->willReturn(true);

        $em = $this->createMock(EntityManagerInterface::class);
        $this->doctrine->expects($this->once())
            ->method('getManagerForClass')
            ->with(ShoppingList::class)
            ->willReturn($em);

        $em->expects($this->once())
            ->method('persist');
        $em->expects($this->once())
            ->method('flush');

        $this->currentShoppingListManager->expects($this->once())
            ->method('setCurrent')
            ->with($this->identicalTo($customerUser), $this->identicalTo($shoppingList));

        $handler = new ShoppingListHandler($this->currentShoppingListManager, $this->doctrine);
        $this->assertTrue($handler->process($shoppingList, $this->form, $request));
    }

    public function testProcessExistingShoppingList()
    {
        $shoppingList = $this->createMock(ShoppingList::class);
        $shoppingList->expects($this->once())
            ->method('getId')
            ->willReturn(1);

        $request = Request::create('/', 'PUT', self::FORM_DATA);

        $this->form->expects($this->once())
            ->method('submit')
            ->with(self::FORM_DATA);
        $this->form->expects($this->once())
            ->method('isValid')
            ->willReturn(true);

        $em = $this->createMock(EntityManagerInterface::class);
        $this->doctrine->expects($this->once())
            ->method('getManagerForClass')
            ->with(ShoppingList::class)
            ->willReturn($em);

        $em->expects($this->once())
            ->method('persist');
        $em->expects($this->once())
            ->method('flush');

        $this->currentShoppingListManager->expects($this->never())
            ->method('setCurrent');

        $handler = new ShoppingListHandler($this->currentShoppingListManager, $this->doctrine);
        $this->assertTrue($handler->process($shoppingList, $this->form, $request));
    }
}
