<?php

namespace OroB2B\Bundle\ShoppingListBundle\Tests\Unit\Form\Handler;

use Doctrine\Bundle\DoctrineBundle\Registry;
use Doctrine\Common\Persistence\ObjectManager;

use Symfony\Component\Form\Test\FormInterface;
use Symfony\Component\HttpFoundation\Request;

use OroB2B\Bundle\AccountBundle\Entity\AccountUser;
use OroB2B\Bundle\ShoppingListBundle\Entity\ShoppingList;
use OroB2B\Bundle\ShoppingListBundle\Manager\ShoppingListManager;
use OroB2B\Bundle\ShoppingListBundle\Form\Handler\ShoppingListHandler;

class ShoppingListHandlerTest extends \PHPUnit_Framework_TestCase
{
    const SHOPPING_LIST_SHORTCUT = 'OroB2BShoppingListBundle:ShoppingList';

    /** @var \PHPUnit_Framework_MockObject_MockObject|FormInterface */
    protected $form;
    /** @var \PHPUnit_Framework_MockObject_MockObject|Request */
    protected $request;
    /** @var \PHPUnit_Framework_MockObject_MockObject|Registry */
    protected $registry;
    /** @var \PHPUnit_Framework_MockObject_MockObject|ShoppingList */
    protected $shoppingList;
    /** @var \PHPUnit_Framework_MockObject_MockObject|ShoppingListManager */
    protected $manager;

    protected function setUp()
    {
        $this->form = $this->getMockBuilder('Symfony\Component\Form\FormInterface')
            ->disableOriginalConstructor()
            ->getMock();
        $this->request = $this->getMockBuilder('Symfony\Component\HttpFoundation\Request')
            ->disableOriginalConstructor()
            ->getMock();
        $this->registry = $this->getMockBuilder('Doctrine\Bundle\DoctrineBundle\Registry')
            ->disableOriginalConstructor()
            ->getMock();
        $this->shoppingList = $this->getMock('OroB2B\Bundle\ShoppingListBundle\Entity\ShoppingList');
        $this->shoppingList->expects($this->any())
            ->method('getAccountUser')
            ->willReturn(new AccountUser());
        $this->manager = $this->getMockBuilder('OroB2B\Bundle\ShoppingListBundle\Manager\ShoppingListManager')
            ->disableOriginalConstructor()
            ->getMock();
    }

    public function testProcessWrongMethod()
    {
        $this->request->expects($this->once())
            ->method('getMethod')
            ->will($this->returnValue('GET'));

        $handler = new ShoppingListHandler($this->form, $this->request, $this->manager, $this->registry);
        $this->assertFalse($handler->process($this->shoppingList));
    }

    public function testProcessFormNotValid()
    {
        $this->request->expects($this->once())
            ->method('getMethod')
            ->will($this->returnValue('POST'));

        $this->form->expects($this->once())
            ->method('submit')
            ->with($this->request);
        $this->form->expects($this->once())
            ->method('isValid')
            ->will($this->returnValue(false));

        $handler = new ShoppingListHandler($this->form, $this->request, $this->manager, $this->registry);
        $this->assertFalse($handler->process($this->shoppingList));
    }

    public function testProcessNotExistingShoppingList()
    {
        $this->request->expects($this->once())
            ->method('getMethod')
            ->will($this->returnValue('PUT'));

        $this->form->expects($this->once())
            ->method('submit')
            ->with($this->request);
        $this->form->expects($this->once())
            ->method('isValid')
            ->will($this->returnValue(true));

        /** @var \PHPUnit_Framework_MockObject_MockObject|ObjectManager $manager */
        $manager = $this->getMock('Doctrine\Common\Persistence\ObjectManager');

        $this->registry->expects($this->once())
            ->method('getManagerForClass')
            ->with(self::SHOPPING_LIST_SHORTCUT)
            ->will($this->returnValue($manager));

        $handler = new ShoppingListHandler($this->form, $this->request, $this->manager, $this->registry);
        $this->assertTrue($handler->process($this->shoppingList));
    }

    public function testProcessExistingShoppingList()
    {
        $this->shoppingList->expects($this->once())
            ->method('getId')
            ->willReturn(1);

        $this->request->expects($this->once())
            ->method('getMethod')
            ->will($this->returnValue('PUT'));

        $this->form->expects($this->once())
            ->method('submit')
            ->with($this->request);
        $this->form->expects($this->once())
            ->method('isValid')
            ->will($this->returnValue(true));

        /** @var \PHPUnit_Framework_MockObject_MockObject|ObjectManager $manager */
        $manager = $this->getMock('Doctrine\Common\Persistence\ObjectManager');

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
