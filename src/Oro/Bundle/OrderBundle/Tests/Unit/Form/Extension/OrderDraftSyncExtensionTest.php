<?php

declare(strict_types=1);

namespace Oro\Bundle\OrderBundle\Tests\Unit\Form\Extension;

use Oro\Bundle\OrderBundle\DraftSession\Manager\OrderDraftManager;
use Oro\Bundle\OrderBundle\Entity\Order;
use Oro\Bundle\OrderBundle\Form\Extension\OrderDraftSyncExtension;
use Oro\Bundle\OrderBundle\Form\Type\OrderType;
use Oro\Bundle\OrderBundle\Form\Type\SubOrderType;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\OptionsResolver\Exception\InvalidOptionsException;
use Symfony\Component\OptionsResolver\OptionsResolver;

final class OrderDraftSyncExtensionTest extends TestCase
{
    private OrderDraftManager&MockObject $orderDraftManager;
    private OrderDraftSyncExtension $extension;

    #[\Override]
    protected function setUp(): void
    {
        $this->orderDraftManager = $this->createMock(OrderDraftManager::class);

        $this->extension = new OrderDraftSyncExtension(
            $this->orderDraftManager
        );
    }

    public function testGetExtendedTypesReturnsOrderTypeAndSubOrderType(): void
    {
        $extendedTypes = OrderDraftSyncExtension::getExtendedTypes();

        self::assertContains(OrderType::class, $extendedTypes);
        self::assertContains(SubOrderType::class, $extendedTypes);
    }

    public function testConfigureOptionsSetsDraftSessionSyncDefaultToFalse(): void
    {
        $resolver = new OptionsResolver();

        $this->extension->configureOptions($resolver);

        $resolvedOptions = $resolver->resolve([]);

        self::assertFalse($resolvedOptions['draft_session_sync']);
    }

    public function testConfigureOptionsAllowsDraftSessionSyncToBeSetToTrue(): void
    {
        $resolver = new OptionsResolver();

        $this->extension->configureOptions($resolver);

        $resolvedOptions = $resolver->resolve(['draft_session_sync' => true]);

        self::assertTrue($resolvedOptions['draft_session_sync']);
    }

    public function testConfigureOptionsRejectsNonBoolValuesForDraftSessionSync(): void
    {
        $resolver = new OptionsResolver();

        $this->extension->configureOptions($resolver);

        $this->expectException(InvalidOptionsException::class);

        $resolver->resolve(['draft_session_sync' => 'invalid']);
    }

    public function testBuildFormDoesNothingWhenDraftSessionSyncIsDisabled(): void
    {
        $builder = $this->createMock(FormBuilderInterface::class);

        $this->orderDraftManager
            ->expects(self::never())
            ->method('getDraftSessionUuid');

        $builder->expects(self::never())
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

        $builder->expects(self::never())
            ->method('addEventListener');

        $this->extension->buildForm($builder, ['draft_session_sync' => true]);
    }

    public function testBuildFormAddsListenersWhenDraftSessionSyncIsEnabled(): void
    {
        $builder = $this->createMock(FormBuilderInterface::class);

        $this->orderDraftManager
            ->expects(self::once())
            ->method('getDraftSessionUuid')
            ->willReturn('draft-session-uuid');

        $builder
            ->expects(self::exactly(2))
            ->method('addEventListener')
            ->withConsecutive(
                [FormEvents::PRE_SET_DATA, self::isInstanceOf(\Closure::class), 100],
                [FormEvents::POST_SET_DATA, self::isInstanceOf(\Closure::class), -100]
            )
            ->willReturnSelf();

        $this->extension->buildForm($builder, ['draft_session_sync' => true]);
    }

    public function testBuildFormPreSetDataListenerLoadsOrderFromDraft(): void
    {
        $builder = $this->createMock(FormBuilderInterface::class);
        $order = new Order();
        $form = $this->createMock(FormInterface::class);
        $event = new FormEvent($form, $order);
        $preSetDataListener = null;

        $this->orderDraftManager
            ->expects(self::once())
            ->method('getDraftSessionUuid')
            ->willReturn('draft-session-uuid');

        $builder
            ->expects(self::exactly(2))
            ->method('addEventListener')
            ->willReturnCallback(
                static function (string $eventName, callable $listener) use (&$preSetDataListener, $builder) {
                    if ($eventName === FormEvents::PRE_SET_DATA) {
                        $preSetDataListener = $listener;
                    }

                    return $builder;
                }
            );

        $this->orderDraftManager
            ->expects(self::once())
            ->method('loadFromEntityDraft')
            ->with($order)
            ->willReturn($order);

        $this->extension->buildForm($builder, ['draft_session_sync' => true]);

        self::assertIsCallable($preSetDataListener);
        $preSetDataListener($event);
    }

    public function testBuildFormPostSetDataListenerDoesNotSaveWhenDraftExists(): void
    {
        $builder = $this->createMock(FormBuilderInterface::class);
        $order = new Order();
        $form = $this->createMock(FormInterface::class);
        $event = new FormEvent($form, $order);
        $postSetDataListener = null;

        $this->orderDraftManager
            ->expects(self::once())
            ->method('getDraftSessionUuid')
            ->willReturn('draft-session-uuid');

        $builder
            ->expects(self::exactly(2))
            ->method('addEventListener')
            ->willReturnCallback(
                static function (string $eventName, callable $listener) use (&$postSetDataListener, $builder) {
                    if ($eventName === FormEvents::POST_SET_DATA) {
                        $postSetDataListener = $listener;
                    }

                    return $builder;
                }
            );

        $this->orderDraftManager
            ->expects(self::once())
            ->method('hasEntityDraft')
            ->with($order)
            ->willReturn(true);

        $this->orderDraftManager
            ->expects(self::never())
            ->method('saveToEntityDraft');

        $this->extension->buildForm($builder, ['draft_session_sync' => true]);

        self::assertIsCallable($postSetDataListener);
        $postSetDataListener($event);
    }

    public function testBuildFormPostSetDataListenerSavesWhenDraftDoesNotExist(): void
    {
        $builder = $this->createMock(FormBuilderInterface::class);
        $order = new Order();
        $form = $this->createMock(FormInterface::class);
        $event = new FormEvent($form, $order);
        $postSetDataListener = null;

        $this->orderDraftManager
            ->expects(self::once())
            ->method('getDraftSessionUuid')
            ->willReturn('draft-session-uuid');

        $builder
            ->expects(self::exactly(2))
            ->method('addEventListener')
            ->willReturnCallback(
                static function (string $eventName, callable $listener) use (&$postSetDataListener, $builder) {
                    if ($eventName === FormEvents::POST_SET_DATA) {
                        $postSetDataListener = $listener;
                    }

                    return $builder;
                }
            );

        $this->orderDraftManager
            ->expects(self::once())
            ->method('hasEntityDraft')
            ->with($order)
            ->willReturn(false);

        $this->orderDraftManager
            ->expects(self::once())
            ->method('saveToEntityDraft')
            ->with($order)
            ->willReturn($order);

        $this->extension->buildForm($builder, ['draft_session_sync' => true]);

        self::assertIsCallable($postSetDataListener);
        $postSetDataListener($event);
    }
}
