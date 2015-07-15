<?php

namespace OroB2B\Bundle\ShoppingListBundle\Tests\Unit\Form\Handler;

use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\Request;

use Doctrine\Bundle\DoctrineBundle\Registry;

use OroB2B\Bundle\ShoppingListBundle\Entity\LineItem;
use OroB2B\Bundle\ShoppingListBundle\Form\Handler\LineItemHandler;

class LineItemHandlerTest extends \PHPUnit_Framework_TestCase
{
    const LINE_ITEM_SHORTCUT = 'OroB2BShoppingListBundle:LineItem';

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject|FormInterface
     */
    protected $form;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject|Request
     */
    protected $request;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject|Registry
     */
    protected $registry;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject|LineItem
     */
    protected $lineItem;

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
        $this->lineItem = $this->getMock('OroB2B\Bundle\ShoppingListBundle\Entity\LineItem');
    }

    public function testProcessWrongMethod()
    {
        $this->request->expects($this->once())
            ->method('getMethod')
            ->will($this->returnValue('GET'));

        $handler = new LineItemHandler($this->form, $this->request, $this->registry);
        $this->assertFalse($handler->process($this->lineItem));
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

        $handler = new LineItemHandler($this->form, $this->request, $this->registry);
        $this->assertFalse($handler->process($this->lineItem));
    }

    public function testProcessExistingLineItem()
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

        $this->lineItem->expects($this->once())
            ->method('getQuantity')
            ->will($this->returnValue(40));

        $existingLineItem = $this->getMock('OroB2B\Bundle\ShoppingListBundle\Entity\LineItem');
        $existingLineItem->expects($this->once())
            ->method('getQuantity')
            ->will($this->returnValue(10));
        $existingLineItem->expects($this->once())
            ->method('setQuantity');
        $existingLineItem->expects($this->once())
            ->method('getId')
            ->will($this->returnValue(123));

        $repository = $this->getMockBuilder('OroB2B\Bundle\ShoppingListBundle\Entity\Repository\LineItemRepository')
            ->disableOriginalConstructor()
            ->getMock();
        $repository->expects($this->once())
            ->method('findDuplicate')
            ->with($this->lineItem)
            ->will($this->returnValue($existingLineItem));

        $manager = $this->getMock('Doctrine\Common\Persistence\ObjectManager');
        $manager->expects($this->once())
            ->method('getRepository')
            ->with(self::LINE_ITEM_SHORTCUT)
            ->will($this->returnValue($repository));
        $manager->expects($this->never())
            ->method('persist');
        $manager->expects($this->once())
            ->method('flush');

        $this->registry->expects($this->once())
            ->method('getManagerForClass')
            ->with(self::LINE_ITEM_SHORTCUT)
            ->will($this->returnValue($manager));

        $handler = new LineItemHandler($this->form, $this->request, $this->registry);
        $this->assertTrue($handler->process($this->lineItem));
        $this->assertEquals(['savedId' => 123], $handler->updateSavedId([]));
    }

    public function testProcessNotExistingLineItem()
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

        $repository = $this->getMockBuilder('OroB2B\Bundle\ShoppingListBundle\Entity\Repository\LineItemRepository')
            ->disableOriginalConstructor()
            ->getMock();
        $repository->expects($this->once())
            ->method('findDuplicate')
            ->with($this->lineItem)
            ->will($this->returnValue(null));

        $manager = $this->getMock('Doctrine\Common\Persistence\ObjectManager');
        $manager->expects($this->once())
            ->method('getRepository')
            ->with(self::LINE_ITEM_SHORTCUT)
            ->will($this->returnValue($repository));
        $manager->expects($this->once())
            ->method('persist')
            ->with($this->lineItem);
        $manager->expects($this->once())
            ->method('flush');

        $this->registry->expects($this->once())
            ->method('getManagerForClass')
            ->with(self::LINE_ITEM_SHORTCUT)
            ->will($this->returnValue($manager));

        $handler = new LineItemHandler($this->form, $this->request, $this->registry);
        $this->assertTrue($handler->process($this->lineItem));
        $this->assertEquals([], $handler->updateSavedId([]));
    }
}
