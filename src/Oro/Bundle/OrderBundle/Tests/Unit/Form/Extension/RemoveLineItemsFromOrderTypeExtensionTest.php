<?php

declare(strict_types=1);

namespace Oro\Bundle\OrderBundle\Tests\Unit\Form\Extension;

use Oro\Bundle\OrderBundle\DraftSession\Manager\OrderDraftManager;
use Oro\Bundle\OrderBundle\Form\Extension\RemoveLineItemsFromOrderTypeExtension;
use Oro\Bundle\OrderBundle\Form\Type\OrderType;
use Oro\Bundle\OrderBundle\Form\Type\SubOrderType;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Form\Form;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormError;
use Symfony\Component\Form\FormErrorIterator;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Validator\ConstraintViolationInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

final class RemoveLineItemsFromOrderTypeExtensionTest extends TestCase
{
    private OrderDraftManager&MockObject $orderDraftManager;

    private TranslatorInterface&MockObject $translator;

    private RemoveLineItemsFromOrderTypeExtension $extension;

    #[\Override]
    protected function setUp(): void
    {
        $this->orderDraftManager = $this->createMock(OrderDraftManager::class);
        $this->translator = $this->createMock(TranslatorInterface::class);

        $this->extension = new RemoveLineItemsFromOrderTypeExtension($this->orderDraftManager, $this->translator);
    }

    public function testGetExtendedTypes(): void
    {
        self::assertSame(
            [OrderType::class, SubOrderType::class],
            [...RemoveLineItemsFromOrderTypeExtension::getExtendedTypes()]
        );
    }

    public function testBuildFormDoesNothingWhenDraftSessionSyncIsDisabled(): void
    {
        $builder = $this->createMock(FormBuilderInterface::class);

        $this->orderDraftManager
            ->expects(self::never())
            ->method('getDraftSessionUuid');

        $builder
            ->expects(self::never())
            ->method('remove');

        $builder
            ->expects(self::never())
            ->method('addEventListener');

        $this->extension->buildForm($builder, ['draft_session_sync' => false]);
    }

    public function testBuildFormDoesNothingWhenDraftSessionSyncEnabledButSessionUuidIsMissing(): void
    {
        $builder = $this->createMock(FormBuilderInterface::class);

        $this->orderDraftManager
            ->expects(self::once())
            ->method('getDraftSessionUuid')
            ->willReturn(null);

        $builder
            ->expects(self::never())
            ->method('remove');

        $builder
            ->expects(self::never())
            ->method('addEventListener');

        $this->extension->buildForm($builder, ['draft_session_sync' => true]);
    }

    public function testBuildFormRemovesLineItemsAndAddsPostSubmitListenerWhenDraftSessionSyncEnabled(): void
    {
        $builder = $this->createMock(FormBuilderInterface::class);

        $this->orderDraftManager
            ->expects(self::once())
            ->method('getDraftSessionUuid')
            ->willReturn('draft-session-uuid');

        $builder
            ->expects(self::once())
            ->method('remove')
            ->with('lineItems')
            ->willReturnSelf();

        $builder
            ->expects(self::once())
            ->method('addEventListener')
            ->with(FormEvents::POST_SUBMIT, self::isInstanceOf(\Closure::class), -100)
            ->willReturnSelf();

        $this->extension->buildForm($builder, ['draft_session_sync' => true]);
    }

    public function testPostSubmitListenerDoesNothingWhenFormIsNotClearableErrorsInterface(): void
    {
        $builder = $this->createMock(FormBuilderInterface::class);
        $form = $this->createMock(FormInterface::class);
        $event = new FormEvent($form, null);
        $postSubmitListener = null;

        $this->orderDraftManager
            ->expects(self::once())
            ->method('getDraftSessionUuid')
            ->willReturn('draft-session-uuid');

        $builder
            ->expects(self::once())
            ->method('remove')
            ->with('lineItems')
            ->willReturnSelf();

        $builder
            ->expects(self::once())
            ->method('addEventListener')
            ->willReturnCallback(
                static function (string $eventName, callable $listener) use (&$postSubmitListener, $builder) {
                    if ($eventName === FormEvents::POST_SUBMIT) {
                        $postSubmitListener = $listener;
                    }

                    return $builder;
                }
            );

        $this->translator
            ->expects(self::never())
            ->method('trans');

        $this->extension->buildForm($builder, ['draft_session_sync' => true]);

        self::assertIsCallable($postSubmitListener);
        $postSubmitListener($event);
    }

    public function testPostSubmitListenerDoesNothingWhenFormIsValid(): void
    {
        $builder = $this->createMock(FormBuilderInterface::class);
        $form = $this->createMock(Form::class);
        $event = new FormEvent($form, null);
        $postSubmitListener = null;

        $this->orderDraftManager
            ->expects(self::once())
            ->method('getDraftSessionUuid')
            ->willReturn('draft-session-uuid');

        $builder
            ->expects(self::once())
            ->method('remove')
            ->with('lineItems')
            ->willReturnSelf();

        $builder
            ->expects(self::once())
            ->method('addEventListener')
            ->willReturnCallback(
                static function (string $eventName, callable $listener) use (&$postSubmitListener, $builder) {
                    if ($eventName === FormEvents::POST_SUBMIT) {
                        $postSubmitListener = $listener;
                    }

                    return $builder;
                }
            );

        $form
            ->expects(self::once())
            ->method('isValid')
            ->willReturn(true);

        $form
            ->expects(self::never())
            ->method('clearErrors');

        $form
            ->expects(self::never())
            ->method('addError');

        $this->translator
            ->expects(self::never())
            ->method('trans');

        $this->extension->buildForm($builder, ['draft_session_sync' => true]);

        self::assertIsCallable($postSubmitListener);
        $postSubmitListener($event);
    }

    public function testPostSubmitListenerReplacesLineItemsErrorsWithGeneralError(): void
    {
        $builder = $this->createMock(FormBuilderInterface::class);
        $form = $this->createMock(Form::class);
        $event = new FormEvent($form, null);
        $postSubmitListener = null;

        $lineItemViolation = $this->createMock(ConstraintViolationInterface::class);
        $lineItemViolation
            ->expects(self::once())
            ->method('getPropertyPath')
            ->willReturn('data.lineItems[0].quantity');

        $otherViolation = $this->createMock(ConstraintViolationInterface::class);
        $otherViolation
            ->expects(self::once())
            ->method('getPropertyPath')
            ->willReturn('data.customer');

        $lineItemError = new FormError('lineItemError', null, [], null, $lineItemViolation);
        $otherError = new FormError('otherError', null, [], null, $otherViolation);

        $this->orderDraftManager
            ->expects(self::once())
            ->method('getDraftSessionUuid')
            ->willReturn('draft-session-uuid');

        $builder
            ->expects(self::once())
            ->method('remove')
            ->with('lineItems')
            ->willReturnSelf();

        $builder
            ->expects(self::once())
            ->method('addEventListener')
            ->willReturnCallback(
                static function (string $eventName, callable $listener) use (&$postSubmitListener, $builder) {
                    if ($eventName === FormEvents::POST_SUBMIT) {
                        $postSubmitListener = $listener;
                    }

                    return $builder;
                }
            );

        $form
            ->expects(self::once())
            ->method('isValid')
            ->willReturn(false);

        $form
            ->expects(self::once())
            ->method('getErrors')
            ->willReturn(new FormErrorIterator($form, [$lineItemError, $otherError]));

        $form
            ->expects(self::once())
            ->method('clearErrors');

        $this->translator
            ->expects(self::once())
            ->method('trans')
            ->with('oro.order.line_items_general_error.message', [], 'validators')
            ->willReturn('General line items error');

        $addedErrors = [];
        $form
            ->expects(self::exactly(2))
            ->method('addError')
            ->willReturnCallback(static function (FormError $formError) use (&$addedErrors, $form) {
                $addedErrors[] = $formError;

                return $form;
            });

        $this->extension->buildForm($builder, ['draft_session_sync' => true]);

        self::assertIsCallable($postSubmitListener);
        $postSubmitListener($event);

        self::assertCount(2, $addedErrors);
        self::assertSame('General line items error', $addedErrors[0]->getMessage());
        self::assertSame($otherError, $addedErrors[1]);
    }
}
