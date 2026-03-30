<?php

declare(strict_types=1);

namespace Oro\Bundle\OrderBundle\Tests\Unit\Form\Extension;

use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\OrderBundle\DraftSession\Manager\OrderDraftManager;
use Oro\Bundle\OrderBundle\Form\Extension\OrderDraftSyncExtension;
use Oro\Bundle\OrderBundle\Form\Type\OrderType;
use Oro\Bundle\OrderBundle\Form\Type\SubOrderType;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\Exception\InvalidOptionsException;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Contracts\Service\ResetInterface;

final class OrderDraftSyncExtensionTest extends TestCase
{
    private OrderDraftManager&MockObject $orderDraftManager;
    private OrderDraftSyncExtension $extension;

    #[\Override]
    protected function setUp(): void
    {
        $this->orderDraftManager = $this->createMock(OrderDraftManager::class);
        $doctrine = $this->createMock(ManagerRegistry::class);

        $this->extension = new OrderDraftSyncExtension(
            $this->orderDraftManager,
            $doctrine
        );
    }

    public function testImplementsResetInterface(): void
    {
        self::assertInstanceOf(ResetInterface::class, $this->extension);
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

        $builder->expects(self::never())
            ->method('addEventListener');

        $this->orderDraftManager->expects(self::never())
            ->method('getDraftSessionUuid');

        $this->extension->buildForm($builder, ['draft_session_sync' => false]);
    }

    public function testBuildFormDoesNothingWhenDraftSessionUuidIsNotSet(): void
    {
        $builder = $this->createMock(FormBuilderInterface::class);

        $this->orderDraftManager->expects(self::once())
            ->method('getDraftSessionUuid')
            ->willReturn(null);

        $builder->expects(self::never())
            ->method('addEventListener');

        $this->extension->buildForm($builder, ['draft_session_sync' => true]);
    }
}
