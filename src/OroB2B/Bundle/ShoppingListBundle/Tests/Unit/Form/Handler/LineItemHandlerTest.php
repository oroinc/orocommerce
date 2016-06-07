<?php

namespace OroB2B\Bundle\ShoppingListBundle\Tests\Unit\Form\Handler;

use Doctrine\Bundle\DoctrineBundle\Registry;
use Doctrine\ORM\EntityManagerInterface;

use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\Request;

use OroB2B\Bundle\ShoppingListBundle\Entity\LineItem;
use OroB2B\Bundle\ShoppingListBundle\Entity\ShoppingList;
use OroB2B\Bundle\ShoppingListBundle\Form\Handler\LineItemHandler;
use OroB2B\Bundle\ProductBundle\Form\Type\FrontendLineItemType;
use OroB2B\Bundle\ShoppingListBundle\Manager\ShoppingListManager;

class LineItemHandlerTest extends \PHPUnit_Framework_TestCase
{
    const LINE_ITEM_SHORTCUT = 'OroB2BShoppingListBundle:LineItem';

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject|FormInterface
     */
    protected $form;

    /**
     * @var Request
     */
    protected $request;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject|Registry
     */
    protected $registry;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject|ShoppingListManager
     */
    protected $shoppingListManager;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject|LineItem
     */
    protected $lineItem;

    /**
     * {@inheritdoc}
     */
    protected function setUp()
    {
        $this->form = $this->getMockBuilder('Symfony\Component\Form\FormInterface')
            ->disableOriginalConstructor()
            ->getMock();
        $this->form->expects($this->any())
            ->method('getName')
            ->willReturn(FrontendLineItemType::NAME);
        $this->request = new Request();
        $this->registry = $this->getMockBuilder('Doctrine\Bundle\DoctrineBundle\Registry')
            ->disableOriginalConstructor()
            ->getMock();
        $this->shoppingListManager =
            $this->getMockBuilder('OroB2B\Bundle\ShoppingListBundle\Manager\ShoppingListManager')
                ->disableOriginalConstructor()
                ->getMock();

        $this->lineItem = $this->getMock('OroB2B\Bundle\ShoppingListBundle\Entity\LineItem');
        $shoppingList = $this->getMock('OroB2B\Bundle\ShoppingListBundle\Entity\ShoppingList');

        $this->lineItem->expects($this->any())
            ->method('getShoppingList')
            ->willReturn($shoppingList);
    }

    public function testProcessWrongMethod()
    {
        $this->registry
            ->expects($this->never())
            ->method('getManagerForClass');

        $handler = new LineItemHandler(
            $this->form,
            $this->request,
            $this->registry,
            $this->shoppingListManager
        );
        $this->assertFalse($handler->process($this->lineItem));
    }

    public function testProcessFormNotValid()
    {
        /** @var \PHPUnit_Framework_MockObject_MockObject|EntityManagerInterface $manager */
        $manager = $this->getMock('Doctrine\ORM\EntityManagerInterface');
        $manager->expects($this->once())
            ->method('beginTransaction');
        $manager->expects($this->never())
            ->method('commit');
        $manager->expects($this->once())
            ->method('rollback');

        $manager->expects($this->never())
            ->method('commit');

        $this->registry->expects($this->once())
            ->method('getManagerForClass')
            ->with(self::LINE_ITEM_SHORTCUT)
            ->will($this->returnValue($manager));

        $this->request = Request::create('/', 'POST');

        $this->form->expects($this->once())
            ->method('submit')
            ->with($this->request);
        $this->form->expects($this->once())
            ->method('isValid')
            ->will($this->returnValue(false));

        $handler = new LineItemHandler(
            $this->form,
            $this->request,
            $this->registry,
            $this->shoppingListManager
        );
        $this->assertFalse($handler->process($this->lineItem));
    }

    public function testProcessSuccess()
    {
        $this->request = Request::create('/', 'PUT');

        /** @var \PHPUnit_Framework_MockObject_MockObject|EntityManagerInterface $manager */
        $manager = $this->getMock('Doctrine\ORM\EntityManagerInterface');
        $manager->expects($this->once())
            ->method('beginTransaction');
        $manager->expects($this->once())
            ->method('commit');
        $manager->expects($this->never())
            ->method('rollback');
        $manager->expects($this->once())
            ->method('commit');

        $this->registry->expects($this->once())
            ->method('getManagerForClass')
            ->with(self::LINE_ITEM_SHORTCUT)
            ->will($this->returnValue($manager));

        $this->form->expects($this->once())
            ->method('submit')
            ->with($this->request);
        $this->form->expects($this->once())
            ->method('isValid')
            ->will($this->returnValue(true));

        $this->request->request->add(['orob2b_product_frontend_line_item' => ['shoppingListLabel' => 'label']]);

        $shoppingList = new ShoppingList();
        $this->lineItem->expects($this->once())
            ->method('getShoppingList')
            ->willReturn($shoppingList);

        $this->shoppingListManager->expects($this->once())
            ->method('addLineItem')
            ->willReturn($shoppingList);

        $this->shoppingListManager->expects($this->once())
            ->method('createCurrent')
            ->willReturn($shoppingList);

        $handler = new LineItemHandler(
            $this->form,
            $this->request,
            $this->registry,
            $this->shoppingListManager
        );
        $this->assertTrue($handler->process($this->lineItem));
    }
}
