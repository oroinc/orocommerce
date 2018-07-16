<?php

namespace Oro\Bundle\ShoppingListBundle\Tests\Unit\Form\Handler;

use Doctrine\Bundle\DoctrineBundle\Registry;
use Doctrine\Common\Persistence\ObjectManager;
use Doctrine\ORM\EntityManagerInterface;
use Oro\Bundle\CustomerBundle\Entity\CustomerUser;
use Oro\Bundle\ShoppingListBundle\Entity\ShoppingList;
use Oro\Bundle\ShoppingListBundle\Form\Handler\ShoppingListHandler;
use Oro\Bundle\ShoppingListBundle\Manager\ShoppingListManager;
use Symfony\Component\Form\Test\FormInterface;
use Symfony\Component\HttpFoundation\Request;

class ShoppingListHandlerTest extends \PHPUnit\Framework\TestCase
{
    const FORM_DATA = ['field' => 'value'];

    const SHOPPING_LIST_SHORTCUT = 'OroShoppingListBundle:ShoppingList';

    /** @var \PHPUnit\Framework\MockObject\MockObject|FormInterface */
    protected $form;
    /** @var \PHPUnit\Framework\MockObject\MockObject|Request */
    protected $request;
    /** @var \PHPUnit\Framework\MockObject\MockObject|Registry */
    protected $registry;
    /** @var \PHPUnit\Framework\MockObject\MockObject|ShoppingList */
    protected $shoppingList;
    /** @var \PHPUnit\Framework\MockObject\MockObject|ShoppingListManager */
    protected $manager;

    protected function setUp()
    {
        $this->form = $this->getMockBuilder('Symfony\Component\Form\FormInterface')
            ->disableOriginalConstructor()
            ->getMock();
        $this->request = new Request();
        $this->registry = $this->getMockBuilder('Doctrine\Bundle\DoctrineBundle\Registry')
            ->disableOriginalConstructor()
            ->getMock();
        $this->shoppingList = $this->createMock('Oro\Bundle\ShoppingListBundle\Entity\ShoppingList');
        $this->shoppingList->expects($this->any())
            ->method('getCustomerUser')
            ->willReturn(new CustomerUser());
        $this->manager = $this->getMockBuilder('Oro\Bundle\ShoppingListBundle\Manager\ShoppingListManager')
            ->disableOriginalConstructor()
            ->getMock();
    }

    public function testProcessWrongMethod()
    {
        $this->request->setMethod('GET');

        $handler = new ShoppingListHandler($this->form, $this->request, $this->manager, $this->registry);
        $this->assertFalse($handler->process($this->shoppingList));
    }

    public function testProcessFormNotValid()
    {
        $this->request = Request::create('/', 'POST', self::FORM_DATA);

        $this->form->expects($this->once())
            ->method('submit')
            ->with(self::FORM_DATA);
        $this->form->expects($this->once())
            ->method('isValid')
            ->will($this->returnValue(false));

        $handler = new ShoppingListHandler($this->form, $this->request, $this->manager, $this->registry);
        $this->assertFalse($handler->process($this->shoppingList));
    }

    public function testProcessNotExistingShoppingList()
    {
        $this->request = Request::create('/', 'PUT', self::FORM_DATA);

        $this->form->expects($this->once())
            ->method('submit')
            ->with(self::FORM_DATA);
        $this->form->expects($this->once())
            ->method('isValid')
            ->will($this->returnValue(true));

        $em = $this->createMock(EntityManagerInterface::class);
        $this->registry->method('getManagerForClass')->willReturn($em);

        $handler = new ShoppingListHandler($this->form, $this->request, $this->manager, $this->registry);
        $this->assertTrue($handler->process($this->shoppingList));
    }

    public function testProcessExistingShoppingList()
    {
        $this->shoppingList->expects($this->once())
            ->method('getId')
            ->willReturn(1);

        $this->request->initialize([], self::FORM_DATA);
        $this->request->setMethod('PUT');

        $this->form->expects($this->once())
            ->method('submit')
            ->with(self::FORM_DATA);
        $this->form->expects($this->once())
            ->method('isValid')
            ->will($this->returnValue(true));

        /** @var \PHPUnit\Framework\MockObject\MockObject|ObjectManager $manager */
        $manager = $this->createMock('Doctrine\Common\Persistence\ObjectManager');

        $manager->expects($this->once())
            ->method('persist');
        $manager->expects($this->once())
            ->method('flush');

        $this->registry->expects($this->once())
            ->method('getManagerForClass')
            ->with(self::SHOPPING_LIST_SHORTCUT)
            ->will($this->returnValue($manager));

        $handler = new ShoppingListHandler($this->form, $this->request, $this->manager, $this->registry);
        $this->assertTrue($handler->process($this->shoppingList));
    }
}
