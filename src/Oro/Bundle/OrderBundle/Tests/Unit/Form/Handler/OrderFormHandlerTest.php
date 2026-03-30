<?php

declare(strict_types=1);

namespace Oro\Bundle\OrderBundle\Tests\Unit\Form\Handler;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\FormBundle\Event\FormHandler\AfterFormProcessEvent;
use Oro\Bundle\FormBundle\Event\FormHandler\Events;
use Oro\Bundle\FormBundle\Event\FormHandler\FormProcessEvent;
use Oro\Bundle\OrderBundle\Entity\Order;
use Oro\Bundle\OrderBundle\Form\Handler\OrderFormHandler;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\Request;

final class OrderFormHandlerTest extends TestCase
{
    private ManagerRegistry&MockObject $doctrine;
    private EventDispatcherInterface&MockObject $eventDispatcher;
    private OrderFormHandler $handler;
    private FormInterface&MockObject $form;
    private Order $order;
    private EntityManagerInterface&MockObject $entityManager;

    #[\Override]
    protected function setUp(): void
    {
        $this->doctrine = $this->createMock(ManagerRegistry::class);
        $this->eventDispatcher = $this->createMock(EventDispatcherInterface::class);
        $this->form = $this->createMock(FormInterface::class);
        $this->order = new Order();
        $this->entityManager = $this->createMock(EntityManagerInterface::class);

        $this->handler = new OrderFormHandler($this->doctrine, $this->eventDispatcher);
    }

    public function testProcessWhenNotPostOrPutRequest(): void
    {
        $request = Request::create('/order', 'GET');

        $this->eventDispatcher
            ->expects(self::once())
            ->method('dispatch')
            ->with(
                self::isInstanceOf(FormProcessEvent::class),
                Events::BEFORE_FORM_DATA_SET
            );

        $this->form
            ->expects(self::once())
            ->method('setData')
            ->with(self::identicalTo($this->order));

        $this->form
            ->expects(self::never())
            ->method('submit');

        $result = $this->handler->process($this->order, $this->form, $request);

        self::assertFalse($result);
    }

    public function testProcessWithInterruptedBeforeFormDataSetEvent(): void
    {
        $request = Request::create('/order', 'POST');

        $this->eventDispatcher
            ->expects(self::once())
            ->method('dispatch')
            ->with(
                self::isInstanceOf(FormProcessEvent::class),
                Events::BEFORE_FORM_DATA_SET
            )
            ->willReturnCallback(function (FormProcessEvent $event) {
                $event->interruptFormProcess();

                return $event;
            });

        $this->form
            ->expects(self::never())
            ->method('setData');

        $result = $this->handler->process($this->order, $this->form, $request);

        self::assertFalse($result);
    }

    public function testProcessWithPostRequestAndInterruptedBeforeSubmitEvent(): void
    {
        $request = Request::create('/order', 'POST');

        $eventDispatcher = $this->eventDispatcher;
        $eventDispatcher
            ->expects(self::exactly(2))
            ->method('dispatch')
            ->willReturnOnConsecutiveCalls(
                self::returnCallback(static function (FormProcessEvent $event) {
                    return $event;
                }),
                self::returnCallback(static function (FormProcessEvent $event) {
                    $event->interruptFormProcess();

                    return $event;
                })
            );

        $this->form
            ->expects(self::once())
            ->method('setData')
            ->with(self::identicalTo($this->order));

        $this->form
            ->expects(self::never())
            ->method('submit');

        $result = $this->handler->process($this->order, $this->form, $request);

        self::assertFalse($result);
    }

    public function testProcessWithPostRequestAndInvalidForm(): void
    {
        $request = Request::create('/order', 'POST', ['form_name' => ['field' => 'value']]);

        $this->form
            ->method('getName')
            ->willReturn('form_name');

        $this->eventDispatcher
            ->expects(self::exactly(2))
            ->method('dispatch')
            ->withConsecutive(
                [
                    self::isInstanceOf(FormProcessEvent::class),
                    Events::BEFORE_FORM_DATA_SET
                ],
                [
                    self::isInstanceOf(FormProcessEvent::class),
                    Events::BEFORE_FORM_SUBMIT
                ]
            );

        $this->form
            ->expects(self::once())
            ->method('setData')
            ->with(self::identicalTo($this->order));

        $this->form
            ->expects(self::once())
            ->method('submit')
            ->with(['field' => 'value'], true);

        $this->form
            ->expects(self::once())
            ->method('isValid')
            ->willReturn(false);

        $this->doctrine
            ->expects(self::never())
            ->method('getManagerForClass');

        $result = $this->handler->process($this->order, $this->form, $request);

        self::assertFalse($result);
    }

    public function testProcessWithPostRequestAndValidForm(): void
    {
        $request = Request::create('/order', 'POST', ['form_name' => ['field' => 'value']]);

        $this->form
            ->method('getName')
            ->willReturn('form_name');

        $this->eventDispatcher
            ->expects(self::exactly(4))
            ->method('dispatch')
            ->withConsecutive(
                [
                    self::isInstanceOf(FormProcessEvent::class),
                    Events::BEFORE_FORM_DATA_SET
                ],
                [
                    self::isInstanceOf(FormProcessEvent::class),
                    Events::BEFORE_FORM_SUBMIT
                ],
                [
                    self::isInstanceOf(AfterFormProcessEvent::class),
                    Events::BEFORE_FLUSH
                ],
                [
                    self::isInstanceOf(AfterFormProcessEvent::class),
                    Events::AFTER_FLUSH
                ]
            );

        $this->form
            ->expects(self::once())
            ->method('setData')
            ->with(self::identicalTo($this->order));

        $this->form
            ->expects(self::once())
            ->method('submit')
            ->with(['field' => 'value'], true);

        $this->form
            ->expects(self::once())
            ->method('isValid')
            ->willReturn(true);

        $this->doctrine
            ->expects(self::once())
            ->method('getManagerForClass')
            ->with(Order::class)
            ->willReturn($this->entityManager);

        $this->entityManager
            ->expects(self::once())
            ->method('beginTransaction');

        $this->entityManager
            ->expects(self::once())
            ->method('persist')
            ->with(self::identicalTo($this->order));

        $this->entityManager
            ->expects(self::once())
            ->method('flush');

        $this->entityManager
            ->expects(self::once())
            ->method('commit');

        $result = $this->handler->process($this->order, $this->form, $request);

        self::assertTrue($result);
    }

    public function testProcessWithPutRequestAndValidForm(): void
    {
        $request = Request::create('/order', 'PUT', ['field' => 'value']);

        $this->form
            ->method('getName')
            ->willReturn('');

        $this->eventDispatcher
            ->expects(self::exactly(4))
            ->method('dispatch')
            ->withConsecutive(
                [
                    self::isInstanceOf(FormProcessEvent::class),
                    Events::BEFORE_FORM_DATA_SET
                ],
                [
                    self::isInstanceOf(FormProcessEvent::class),
                    Events::BEFORE_FORM_SUBMIT
                ],
                [
                    self::isInstanceOf(AfterFormProcessEvent::class),
                    Events::BEFORE_FLUSH
                ],
                [
                    self::isInstanceOf(AfterFormProcessEvent::class),
                    Events::AFTER_FLUSH
                ]
            );

        $this->form
            ->expects(self::once())
            ->method('getData')
            ->willReturn(null);

        $this->form
            ->expects(self::once())
            ->method('setData')
            ->with(self::identicalTo($this->order));

        $this->form
            ->expects(self::once())
            ->method('submit')
            ->with(['field' => 'value'], true);

        $this->form
            ->expects(self::once())
            ->method('isValid')
            ->willReturn(true);

        $this->doctrine
            ->expects(self::once())
            ->method('getManagerForClass')
            ->with(Order::class)
            ->willReturn($this->entityManager);

        $this->entityManager
            ->expects(self::once())
            ->method('beginTransaction');

        $this->entityManager
            ->expects(self::once())
            ->method('persist')
            ->with(self::identicalTo($this->order));

        $this->entityManager
            ->expects(self::once())
            ->method('flush');

        $this->entityManager
            ->expects(self::once())
            ->method('commit');

        $result = $this->handler->process($this->order, $this->form, $request);

        self::assertTrue($result);
    }

    public function testProcessRollsBackTransactionOnException(): void
    {
        $request = Request::create('/order', 'POST');
        $exception = new \Exception('Test exception');

        $this->form
            ->method('getName')
            ->willReturn('');

        $this->eventDispatcher
            ->expects(self::exactly(3))
            ->method('dispatch')
            ->withConsecutive(
                [
                    self::isInstanceOf(FormProcessEvent::class),
                    Events::BEFORE_FORM_DATA_SET
                ],
                [
                    self::isInstanceOf(FormProcessEvent::class),
                    Events::BEFORE_FORM_SUBMIT
                ],
                [
                    self::isInstanceOf(AfterFormProcessEvent::class),
                    Events::BEFORE_FLUSH
                ]
            );

        $this->form
            ->expects(self::once())
            ->method('getData')
            ->willReturn(null);

        $this->form
            ->expects(self::once())
            ->method('setData')
            ->with(self::identicalTo($this->order));

        $this->form
            ->expects(self::once())
            ->method('submit')
            ->with([], true);

        $this->form
            ->expects(self::once())
            ->method('isValid')
            ->willReturn(true);

        $this->doctrine
            ->expects(self::once())
            ->method('getManagerForClass')
            ->with(Order::class)
            ->willReturn($this->entityManager);

        $this->entityManager
            ->expects(self::once())
            ->method('beginTransaction');

        $this->entityManager
            ->expects(self::once())
            ->method('persist')
            ->with(self::identicalTo($this->order));

        $this->entityManager
            ->expects(self::once())
            ->method('flush')
            ->willThrowException($exception);

        $this->entityManager
            ->expects(self::once())
            ->method('rollback');

        $this->entityManager
            ->expects(self::never())
            ->method('commit');

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Test exception');

        $this->handler->process($this->order, $this->form, $request);
    }

    public function testProcessWhenFormAlreadyHasData(): void
    {
        $request = Request::create('/order', 'GET');

        $this->eventDispatcher
            ->expects(self::once())
            ->method('dispatch')
            ->with(
                self::isInstanceOf(FormProcessEvent::class),
                Events::BEFORE_FORM_DATA_SET
            );

        $this->form
            ->expects(self::once())
            ->method('getData')
            ->willReturn($this->order);

        $this->form
            ->expects(self::never())
            ->method('setData');

        $this->form
            ->expects(self::never())
            ->method('submit');

        $result = $this->handler->process($this->order, $this->form, $request);

        self::assertFalse($result);
    }
}
