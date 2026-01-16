<?php

namespace Oro\Bundle\ShoppingListBundle\Tests\Unit\Form\Handler;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\ProductBundle\Form\Type\FrontendLineItemType;
use Oro\Bundle\ShoppingListBundle\Entity\LineItem;
use Oro\Bundle\ShoppingListBundle\Entity\ShoppingList;
use Oro\Bundle\ShoppingListBundle\Form\Handler\LineItemHandler;
use Oro\Bundle\ShoppingListBundle\Manager\CurrentShoppingListManager;
use Oro\Bundle\ShoppingListBundle\Manager\ShoppingListManager;
use Oro\Bundle\ShoppingListBundle\Tests\Unit\Stub\LineItemStub;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Form\FormError;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Validator\ConstraintViolation;
use Symfony\Component\Validator\ConstraintViolationList;
use Symfony\Component\Validator\Validator\ValidatorInterface;

final class LineItemHandlerTest extends TestCase
{
    private const FORM_DATA = ['field' => 'value'];
    private const CONSTRAINT_ERROR_1 = 'Error 1';
    private const CONSTRAINT_TEMPLATE_1 = 'error_template_1';
    private const CONSTRAINT_PARAMS_1 = ['parameter1' => '1'];
    private const CONSTRAINT_ERROR_2 = 'Error 2';
    private const CONSTRAINT_TEMPLATE_2 = 'error_template_2';
    private const CONSTRAINT_PARAMS_2 = ['parameter2' => '2'];

    private FormInterface&MockObject $form;
    private ManagerRegistry&MockObject $doctrine;
    private ShoppingListManager&MockObject $shoppingListManager;
    private CurrentShoppingListManager&MockObject $currentShoppingListManager;
    private ValidatorInterface&MockObject $validator;

    #[\Override]
    protected function setUp(): void
    {
        $this->form = $this->createMock(FormInterface::class);
        $this->doctrine = $this->createMock(ManagerRegistry::class);
        $this->shoppingListManager = $this->createMock(ShoppingListManager::class);
        $this->currentShoppingListManager = $this->createMock(CurrentShoppingListManager::class);
        $this->validator = $this->createMock(ValidatorInterface::class);

        $this->form->expects(self::any())
            ->method('getName')
            ->willReturn(FrontendLineItemType::NAME);
    }

    private function getLineItemHandler(Request $request): LineItemHandler
    {
        return new LineItemHandler(
            $this->form,
            $request,
            $this->doctrine,
            $this->shoppingListManager,
            $this->currentShoppingListManager,
            $this->validator
        );
    }

    private function getConstraintViolationList(): ConstraintViolationList
    {
        return new ConstraintViolationList([
            new ConstraintViolation(
                self::CONSTRAINT_ERROR_1,
                self::CONSTRAINT_TEMPLATE_1,
                self::CONSTRAINT_PARAMS_1,
                null,
                '',
                null
            ),
            new ConstraintViolation(
                self::CONSTRAINT_ERROR_2,
                self::CONSTRAINT_TEMPLATE_2,
                self::CONSTRAINT_PARAMS_2,
                null,
                '',
                null
            )
        ]);
    }

    private function getShoppingList(): ShoppingList
    {
        $shoppingList = $this->createMock(ShoppingList::class);
        $shoppingList->expects(self::once())
            ->method('getId')
            ->willReturn(777);

        return $shoppingList;
    }

    private function getLineItem(ShoppingList $shoppingList, ?int $id = null): LineItem
    {
        return (new LineItemStub())
            ->setId($id)
            ->setShoppingList($shoppingList);
    }

    public function testProcessWrongMethod(): void
    {
        $lineItem = $this->createMock(LineItem::class);

        $request = Request::create('/');

        $this->doctrine->expects(self::never())
            ->method('getManagerForClass');

        $handler = $this->getLineItemHandler($request);
        self::assertFalse($handler->process($lineItem));
    }

    public function testProcessFormNotValid(): void
    {
        $lineItem = $this->createMock(LineItem::class);

        $em = $this->createMock(EntityManagerInterface::class);
        $em->expects(self::once())
            ->method('beginTransaction');
        $em->expects(self::never())
            ->method('commit');
        $em->expects(self::once())
            ->method('rollback');

        $this->doctrine->expects(self::once())
            ->method('getManagerForClass')
            ->with(LineItem::class)
            ->willReturn($em);

        $request = Request::create('/', 'POST', [FrontendLineItemType::NAME => self::FORM_DATA]);

        $this->form->expects(self::once())
            ->method('submit')
            ->with(self::FORM_DATA);
        $this->form->expects(self::once())
            ->method('isValid')
            ->willReturn(false);

        $handler = $this->getLineItemHandler($request);
        self::assertFalse($handler->process($lineItem));
    }

    public function testProcessAddSuccess(): void
    {
        $shoppingList = $this->getShoppingList();
        $lineItem = $this->getLineItem($shoppingList);

        $request = Request::create('/', 'PUT', [FrontendLineItemType::NAME => ['shoppingListLabel' => 'label']]);

        $em = $this->createMock(EntityManagerInterface::class);
        $em->expects(self::once())
            ->method('beginTransaction');
        $em->expects(self::once())
            ->method('commit');
        $em->expects(self::never())
            ->method('rollback');
        $em->expects(self::once())
            ->method('commit');

        $this->doctrine->expects(self::once())
            ->method('getManagerForClass')
            ->with(LineItem::class)
            ->willReturn($em);

        $this->form->expects(self::once())
            ->method('submit')
            ->with(['shoppingListLabel' => 'label', 'shoppingList' => 777]);
        $this->form->expects(self::once())
            ->method('isValid')
            ->willReturn(true);

        $this->shoppingListManager->expects(self::once())
            ->method('addLineItem')
            ->with($lineItem, $lineItem->getShoppingList(), false, true);

        $this->currentShoppingListManager->expects(self::once())
            ->method('createCurrent')
            ->willReturn($shoppingList);

        $this->validator->expects(self::once())
            ->method('validate')
            ->with($shoppingList)
            ->willReturn(new ConstraintViolationList());

        $handler = $this->getLineItemHandler($request);
        self::assertTrue($handler->process($lineItem));
    }

    public function testProcessSavedForLaterAddSuccess(): void
    {
        $shoppingList = $this->getShoppingList();
        $lineItem = $this->getLineItem($shoppingList);
        $lineItem->setSavedForLaterList($shoppingList);
        $lineItem->removeShoppingList();

        $request = Request::create('/', 'PUT', [FrontendLineItemType::NAME => ['shoppingListLabel' => 'label']]);

        $em = $this->createMock(EntityManagerInterface::class);
        $em->expects(self::once())
            ->method('beginTransaction');
        $em->expects(self::once())
            ->method('commit');
        $em->expects(self::never())
            ->method('rollback');
        $em->expects(self::once())
            ->method('commit');

        $this->doctrine->expects(self::once())
            ->method('getManagerForClass')
            ->with(LineItem::class)
            ->willReturn($em);

        $this->form->expects(self::once())
            ->method('submit')
            ->with(['shoppingListLabel' => 'label', 'shoppingList' => 777]);
        $this->form->expects(self::once())
            ->method('isValid')
            ->willReturn(true);

        $this->shoppingListManager->expects(self::once())
            ->method('addLineItem')
            ->with($lineItem, $lineItem->getAssociatedList(), false, true);

        $this->currentShoppingListManager->expects(self::once())
            ->method('createCurrent')
            ->willReturn($shoppingList);

        $this->validator->expects(self::once())
            ->method('validate')
            ->with($shoppingList)
            ->willReturn(new ConstraintViolationList());

        $handler = $this->getLineItemHandler($request);
        self::assertTrue($handler->process($lineItem));
    }

    public function testProcessShoppingListNotValid(): void
    {
        $shoppingList = $this->getShoppingList();
        $lineItem = $this->getLineItem($shoppingList);

        $em = $this->createMock(EntityManagerInterface::class);
        $em->expects(self::once())
            ->method('beginTransaction');
        $em->expects(self::never())
            ->method('commit');
        $em->expects(self::once())
            ->method('rollback');

        $this->doctrine->expects(self::once())
            ->method('getManagerForClass')
            ->with(LineItem::class)
            ->willReturn($em);

        $this->form->expects(self::once())
            ->method('submit')
            ->with(['shoppingListLabel' => 'label', 'shoppingList' => 777]);
        $this->form->expects(self::once())
            ->method('isValid')
            ->willReturn(true);

        $this->shoppingListManager->expects(self::once())
            ->method('addLineItem')
            ->with($lineItem, $shoppingList, false, true);

        $this->currentShoppingListManager->expects(self::once())
            ->method('createCurrent')
            ->willReturn($shoppingList);

        $this->validator->expects(self::once())
            ->method('validate')
            ->with($shoppingList)
            ->willReturn($this->getConstraintViolationList());

        $this->form->expects(self::exactly(2))
            ->method('addError')
            ->withConsecutive(
                [new FormError(
                    self::CONSTRAINT_ERROR_1,
                    self::CONSTRAINT_TEMPLATE_1,
                    self::CONSTRAINT_PARAMS_1
                )],
                [new FormError(
                    self::CONSTRAINT_ERROR_2,
                    self::CONSTRAINT_TEMPLATE_2,
                    self::CONSTRAINT_PARAMS_2
                )]
            );

        $request = Request::create('/', 'PUT', [FrontendLineItemType::NAME => ['shoppingListLabel' => 'label']]);

        self::assertFalse($this->getLineItemHandler($request)->process($lineItem));
    }
}
