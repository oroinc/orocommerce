<?php

namespace Oro\Bundle\ShoppingListBundle\Tests\Unit\Form\Handler;

use Doctrine\Common\Persistence\ManagerRegistry;
use Doctrine\ORM\EntityManagerInterface;
use Oro\Bundle\ProductBundle\Form\Type\FrontendLineItemType;
use Oro\Bundle\ShoppingListBundle\Entity\LineItem;
use Oro\Bundle\ShoppingListBundle\Entity\ShoppingList;
use Oro\Bundle\ShoppingListBundle\Form\Handler\LineItemHandler;
use Oro\Bundle\ShoppingListBundle\Manager\CurrentShoppingListManager;
use Oro\Bundle\ShoppingListBundle\Manager\ShoppingListManager;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\Request;

class LineItemHandlerTest extends \PHPUnit\Framework\TestCase
{
    private const FORM_DATA = ['field' => 'value'];

    /** @var \PHPUnit\Framework\MockObject\MockObject|FormInterface */
    private $form;

    /** @var \PHPUnit\Framework\MockObject\MockObject|ManagerRegistry */
    private $doctrine;

    /** @var \PHPUnit\Framework\MockObject\MockObject|ShoppingListManager */
    private $shoppingListManager;

    /** @var \PHPUnit\Framework\MockObject\MockObject|CurrentShoppingListManager */
    private $currentShoppingListManager;

    /**
     * {@inheritdoc}
     */
    protected function setUp()
    {
        $this->form = $this->createMock(FormInterface::class);
        $this->doctrine = $this->createMock(ManagerRegistry::class);
        $this->shoppingListManager = $this->createMock(ShoppingListManager::class);
        $this->currentShoppingListManager = $this->createMock(CurrentShoppingListManager::class);

        $this->form->expects($this->any())
            ->method('getName')
            ->willReturn(FrontendLineItemType::NAME);
    }

    /**
     * @param Request $request
     *
     * @return LineItemHandler
     */
    private function getLineItemHandler(Request $request)
    {
        return new LineItemHandler(
            $this->form,
            $request,
            $this->doctrine,
            $this->shoppingListManager,
            $this->currentShoppingListManager
        );
    }

    public function testProcessWrongMethod()
    {
        $lineItem = $this->createMock(LineItem::class);

        $request = Request::create('/');

        $this->doctrine->expects($this->never())
            ->method('getManagerForClass');

        $handler = $this->getLineItemHandler($request);
        $this->assertFalse($handler->process($lineItem));
    }

    public function testProcessFormNotValid()
    {
        $lineItem = $this->createMock(LineItem::class);

        $em = $this->createMock(EntityManagerInterface::class);
        $em->expects($this->once())
            ->method('beginTransaction');
        $em->expects($this->never())
            ->method('commit');
        $em->expects($this->once())
            ->method('rollback');

        $em->expects($this->never())
            ->method('commit');

        $this->doctrine->expects($this->once())
            ->method('getManagerForClass')
            ->with(LineItem::class)
            ->willReturn($em);

        $request = Request::create('/', 'POST', [FrontendLineItemType::NAME => self::FORM_DATA]);

        $this->form->expects($this->once())
            ->method('submit')
            ->with(self::FORM_DATA);
        $this->form->expects($this->once())
            ->method('isValid')
            ->willReturn(false);

        $handler = $this->getLineItemHandler($request);
        $this->assertFalse($handler->process($lineItem));
    }

    public function testProcessSuccess()
    {
        $shoppingList = $this->createMock(ShoppingList::class);
        $shoppingList->expects($this->once())
            ->method('getId')
            ->willReturn(777);
        $lineItem = $this->createMock(LineItem::class);
        $lineItem->expects($this->once())
            ->method('getShoppingList')
            ->willReturn($shoppingList);

        $request = Request::create('/', 'PUT', [FrontendLineItemType::NAME => ['shoppingListLabel' => 'label']]);

        $em = $this->createMock(EntityManagerInterface::class);
        $em->expects($this->once())
            ->method('beginTransaction');
        $em->expects($this->once())
            ->method('commit');
        $em->expects($this->never())
            ->method('rollback');
        $em->expects($this->once())
            ->method('commit');

        $this->doctrine->expects($this->once())
            ->method('getManagerForClass')
            ->with(LineItem::class)
            ->willReturn($em);

        $this->form->expects($this->once())
            ->method('submit')
            ->with(['shoppingListLabel' => 'label', 'shoppingList' => 777]);
        $this->form->expects($this->once())
            ->method('isValid')
            ->willReturn(true);

        $this->shoppingListManager->expects($this->once())
            ->method('addLineItem')
            ->willReturn($shoppingList);

        $this->currentShoppingListManager->expects($this->once())
            ->method('createCurrent')
            ->willReturn($shoppingList);

        $handler = $this->getLineItemHandler($request);
        $this->assertTrue($handler->process($lineItem));
    }
}
