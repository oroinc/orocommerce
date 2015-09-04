<?php

namespace OroB2B\Bundle\ShoppingListBundle\Tests\Unit\Form\Handler;

use Doctrine\Bundle\DoctrineBundle\Registry;
use Doctrine\ORM\EntityManagerInterface;

use OroB2B\Bundle\ShoppingListBundle\Entity\ShoppingList;
use OroB2B\Bundle\ShoppingListBundle\Form\Type\FrontendLineItemType;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\Request;

use OroB2B\Bundle\ShoppingListBundle\Entity\LineItem;
use OroB2B\Bundle\ShoppingListBundle\Entity\Repository\LineItemRepository;
use OroB2B\Bundle\ShoppingListBundle\Form\Handler\LineItemHandler;
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
    }

    public function testProcessWrongMethod()
    {
        $this->registry
            ->expects($this->never())
            ->method('getManagerForClass');

        $handler = new LineItemHandler($this->form, $this->request, $this->registry, $this->shoppingListManager);
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

        $handler = new LineItemHandler($this->form, $this->request, $this->registry, $this->shoppingListManager);
        $this->assertFalse($handler->process($this->lineItem));
    }

    /**
     * @param \PHPUnit_Framework_MockObject_MockObject|LineItem $existingLineItem
     * @param mixed $newNotes
     *
     * @dataProvider lineItemDataProvider
     */
    public function testProcessExistingLineItem($existingLineItem, $newNotes)
    {
        $this->request = Request::create('/', 'PUT');

        $this->addRegistryExpectations($existingLineItem);

        $this->form->expects($this->once())
            ->method('submit')
            ->with($this->request);
        $this->form->expects($this->once())
            ->method('isValid')
            ->will($this->returnValue(true));

        $this->lineItem->expects($this->once())
            ->method('getQuantity')
            ->will($this->returnValue(40));
        $this->lineItem->expects($this->once())
            ->method('getNotes')
            ->will($this->returnValue($newNotes));

        $handler = new LineItemHandler($this->form, $this->request, $this->registry, $this->shoppingListManager);
        $this->assertTrue($handler->process($this->lineItem));
        $this->assertEquals(['savedId' => 123], $handler->updateSavedId([]));
    }

    /**
     * @return array
     */
    public function lineItemDataProvider()
    {
        return [
            [$this->getLineItem(10, null, 123, null), null],
            [$this->getLineItem(10, null, 123, null), ''],
            [$this->getLineItem(10, null, 123, 'note1'), 'note1'],
            [$this->getLineItem(10, '', 123, null), null],
            [$this->getLineItem(10, '', 123, null), ''],
            [$this->getLineItem(10, '', 123, 'note1'), 'note1'],
            [$this->getLineItem(10, 'note1', 123, 'note1'), null],
            [$this->getLineItem(10, 'note1', 123, 'note1'), ''],
            [$this->getLineItem(10, 'note1', 123, 'note1 note1'), 'note1'],
        ];
    }

    public function testProcessExistingLineItemWithNewShoppingList()
    {
        $newShoppingListId = 12;
        $lineItem = $this->getLineItem(10, null, 123, null);
        $newShoppingList = $this->getShoppingList($newShoppingListId);

        $formData = [FrontendLineItemType::NAME => ['shoppingList' => '', 'shoppingListLabel' => 'New List']];
        $this->request = Request::create('/', 'POST', $formData);

        $this->addRegistryExpectations($lineItem);

        $this->form->expects($this->once())
            ->method('submit')
            ->with($this->request);
        $this->form->expects($this->once())
            ->method('isValid')
            ->will($this->returnValue(true));

        $this->shoppingListManager->expects($this->once())
            ->method('createCurrent')
            ->with($formData[FrontendLineItemType::NAME]['shoppingListLabel'])
            ->willReturn($newShoppingList);

        $handler = new LineItemHandler($this->form, $this->request, $this->registry, $this->shoppingListManager);
        $this->assertShoppingListId('', $this->request);
        $this->assertTrue($handler->process($this->lineItem));
        $this->assertShoppingListId($newShoppingListId, $this->request);
    }

    public function testProcessNotExistingLineItem()
    {
        $this->request = Request::create('/', 'PUT');

        $this->form->expects($this->once())
            ->method('submit')
            ->with($this->request);
        $this->form->expects($this->once())
            ->method('isValid')
            ->will($this->returnValue(true));

        /** @var \PHPUnit_Framework_MockObject_MockObject|LineItemRepository $repository */
        $repository = $this->getMockBuilder('OroB2B\Bundle\ShoppingListBundle\Entity\Repository\LineItemRepository')
            ->disableOriginalConstructor()
            ->getMock();
        $repository->expects($this->once())
            ->method('findDuplicate')
            ->with($this->lineItem)
            ->will($this->returnValue(null));

        /** @var \PHPUnit_Framework_MockObject_MockObject|EntityManagerInterface $manager */
        $manager = $this->getMock('Doctrine\ORM\EntityManagerInterface');
        $manager->expects($this->once())
            ->method('beginTransaction');
        $manager->expects($this->once())
            ->method('commit');
        $manager->expects($this->never())
            ->method('rollback');
        $manager->expects($this->once())
            ->method('persist')
            ->with($this->lineItem);
        $manager->expects($this->once())
            ->method('flush');
        $manager->expects($this->once())
            ->method('getRepository')
            ->with(self::LINE_ITEM_SHORTCUT)
            ->will($this->returnValue($repository));

        $this->registry->expects($this->once())
            ->method('getManagerForClass')
            ->with(self::LINE_ITEM_SHORTCUT)
            ->will($this->returnValue($manager));

        $handler = new LineItemHandler($this->form, $this->request, $this->registry, $this->shoppingListManager);
        $this->assertTrue($handler->process($this->lineItem));
        $this->assertEquals([], $handler->updateSavedId([]));
    }

    /**
     * @param object|null $lineItem
     */
    protected function addRegistryExpectations(LineItem $lineItem)
    {
        /** @var \PHPUnit_Framework_MockObject_MockObject|LineItemRepository $repository */
        $repository = $this->getMockBuilder('OroB2B\Bundle\ShoppingListBundle\Entity\Repository\LineItemRepository')
            ->disableOriginalConstructor()
            ->getMock();
        $repository->expects($this->once())
            ->method('findDuplicate')
            ->with($this->lineItem)
            ->will($this->returnValue($lineItem));

        /** @var \PHPUnit_Framework_MockObject_MockObject|EntityManagerInterface $manager */
        $manager = $this->getMock('Doctrine\ORM\EntityManagerInterface');
        $manager->expects($this->once())
            ->method('beginTransaction');
        $manager->expects($this->once())
            ->method('commit');
        $manager->expects($this->never())
            ->method('rollback');
        $manager->expects($this->never())
            ->method('persist');
        $manager->expects($this->once())
            ->method('flush');
        $manager->expects($this->once())
            ->method('getRepository')
            ->with(self::LINE_ITEM_SHORTCUT)
            ->will($this->returnValue($repository));

        $this->registry->expects($this->once())
            ->method('getManagerForClass')
            ->with(self::LINE_ITEM_SHORTCUT)
            ->will($this->returnValue($manager));
    }

    /**
     * @param mixed $quantity
     * @param mixed $notes
     * @param int $id
     * @param int $expectedNotes
     *
     * @return \PHPUnit_Framework_MockObject_MockObject|LineItem
     */
    protected function getLineItem($quantity, $notes, $id, $expectedNotes)
    {
        $existingLineItem = $this->getMock('OroB2B\Bundle\ShoppingListBundle\Entity\LineItem');
        $existingLineItem->expects($this->any())->method('getQuantity')->will($this->returnValue($quantity));
        $existingLineItem->expects($this->any())->method('setQuantity');
        $existingLineItem->expects($this->any())->method('getNotes')->will($this->returnValue($notes));
        $existingLineItem->expects($this->any())->method('setNotes')->with(
            $this->callback(
                function ($actualNotes) use ($expectedNotes) {
                    $this->assertNotEmpty($actualNotes);
                    $this->assertEquals($expectedNotes, $actualNotes);

                    return true;
                }
            )
        );
        $existingLineItem->expects($this->any())->method('getId')->will($this->returnValue($id));

        return $existingLineItem;
    }

    /**
     * @param $id
     * @return \PHPUnit_Framework_MockObject_MockObject|ShoppingList
     */
    protected function getShoppingList($id)
    {
        $shoppingList = $this->getMock('OroB2B\Bundle\ShoppingListBundle\Entity\ShoppingList');
        $shoppingList->expects($this->any())
            ->method('getId')
            ->will($this->returnValue($id));

        return $shoppingList;
    }

    /**
     * @param int $expectedId
     * @param Request $request
     */
    protected function assertShoppingListId($expectedId, Request $request)
    {
        $formData = $request->request->get(FrontendLineItemType::NAME);
        $this->assertEquals($expectedId, $formData['shoppingList']);
    }
}
