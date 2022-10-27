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
use Symfony\Component\Form\FormError;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Validator\ConstraintViolation;
use Symfony\Component\Validator\ConstraintViolationList;
use Symfony\Component\Validator\Validator\ValidatorInterface;

class LineItemHandlerTest extends \PHPUnit\Framework\TestCase
{
    private const FORM_DATA = ['field' => 'value'];
    private const CONSTRAINT_ERROR_1 = 'Error 1';
    private const CONSTRAINT_TEMPLATE_1 = 'error_template_1';
    private const CONSTRAINT_PARAMS_1 = ['parameter1' => '1'];
    private const CONSTRAINT_ERROR_2 = 'Error 2';
    private const CONSTRAINT_TEMPLATE_2 = 'error_template_2';
    private const CONSTRAINT_PARAMS_2 = ['parameter2' => '2'];

    /** @var \PHPUnit\Framework\MockObject\MockObject|FormInterface */
    private $form;

    /** @var \PHPUnit\Framework\MockObject\MockObject|ManagerRegistry */
    private $doctrine;

    /** @var \PHPUnit\Framework\MockObject\MockObject|ShoppingListManager */
    private $shoppingListManager;

    /** @var \PHPUnit\Framework\MockObject\MockObject|CurrentShoppingListManager */
    private $currentShoppingListManager;

    /** @var \PHPUnit\Framework\MockObject\MockObject|ValidatorInterface */
    private $validator;

    /**
     * {@inheritdoc}
     */
    protected function setUp(): void
    {
        $this->form = $this->createMock(FormInterface::class);
        $this->doctrine = $this->createMock(ManagerRegistry::class);
        $this->shoppingListManager = $this->createMock(ShoppingListManager::class);
        $this->currentShoppingListManager = $this->createMock(CurrentShoppingListManager::class);
        $this->validator = $this->createMock(ValidatorInterface::class);

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
            $this->currentShoppingListManager,
            $this->validator
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
        $shoppingList = $this->getShoppingList();
        $lineItem = $this->getLineItem($shoppingList);

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
            ->with($lineItem, $lineItem->getShoppingList(), false, true)
            ->willReturn($shoppingList);

        $this->currentShoppingListManager->expects($this->once())
            ->method('createCurrent')
            ->willReturn($shoppingList);

        $this->validator->expects($this->once())
            ->method('validate')
            ->with($shoppingList)
            ->willReturn(new ConstraintViolationList());

        $handler = $this->getLineItemHandler($request);
        $this->assertTrue($handler->process($lineItem));
    }

    public function testProcessShoppingListNotValid()
    {
        $shoppingList = $this->getShoppingList();
        $lineItem = $this->getLineItem($shoppingList);

        $em = $this->createMock(EntityManagerInterface::class);
        $em->expects($this->once())
            ->method('beginTransaction');
        $em->expects($this->never())
            ->method('commit');
        $em->expects($this->once())
            ->method('rollback');

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
            ->with($lineItem, $shoppingList, false, true);

        $this->currentShoppingListManager->expects($this->once())
            ->method('createCurrent')
            ->willReturn($shoppingList);

        $this->validator->expects($this->once())
            ->method('validate')
            ->with($shoppingList)
            ->willReturn($this->createConstraintViolationList());

        $this->form->expects($this->exactly(2))
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

        $this->assertFalse($this->getLineItemHandler($request)->process($lineItem));
    }

    private function createConstraintViolationList(): ConstraintViolationList
    {
        $constraintViolations = [
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
            ),
        ];

        return new ConstraintViolationList($constraintViolations);
    }

    /**
     * @return ShoppingList|\PHPUnit\Framework\MockObject\MockObject
     */
    private function getShoppingList()
    {
        $shoppingList = $this->createMock(ShoppingList::class);
        $shoppingList->expects($this->once())
            ->method('getId')
            ->willReturn(777);

        return $shoppingList;
    }

    /**
     * @param $shoppingList
     *
     * @return LineItem|\PHPUnit\Framework\MockObject\MockObject
     */
    private function getLineItem($shoppingList)
    {
        $lineItem = $this->createMock(LineItem::class);
        $lineItem->expects($this->atLeastOnce())
            ->method('getShoppingList')
            ->willReturn($shoppingList);

        return $lineItem;
    }
}
