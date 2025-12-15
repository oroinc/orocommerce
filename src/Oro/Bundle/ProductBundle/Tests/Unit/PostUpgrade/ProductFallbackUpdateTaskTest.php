<?php

declare(strict_types=1);

namespace Oro\Bundle\ProductBundle\Tests\Unit\PostUpgrade;

use Oro\Bundle\ProductBundle\Async\Topic\ProductFallbackUpdateTopic;
use Oro\Bundle\ProductBundle\Manager\ProductFallbackUpdateManager;
use Oro\Bundle\ProductBundle\NotificationAlert\ProductFallbackUpdateNotificationAlertProvider;
use Oro\Bundle\ProductBundle\PostUpgrade\ProductFallbackUpdateTask;
use Oro\Component\MessageQueue\Client\MessageProducerInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

final class ProductFallbackUpdateTaskTest extends TestCase
{
    private MessageProducerInterface&MockObject $producer;
    private ProductFallbackUpdateManager&MockObject $updateManager;
    private ProductFallbackUpdateNotificationAlertProvider&MockObject $alertProvider;
    private ProductFallbackUpdateTask $task;

    #[\Override]
    protected function setUp(): void
    {
        $this->producer = $this->createMock(MessageProducerInterface::class);
        $this->updateManager = $this->createMock(ProductFallbackUpdateManager::class);
        $this->alertProvider = $this->createMock(ProductFallbackUpdateNotificationAlertProvider::class);

        $this->task = new ProductFallbackUpdateTask(
            $this->producer,
            $this->updateManager,
            $this->alertProvider
        );
    }

    public function testGetName(): void
    {
        self::assertSame('product_fallback', $this->task->getName());
    }

    public function testGetDescription(): void
    {
        self::assertSame(
            'Update product fallback values (page template, inventory, etc.)',
            $this->task->getDescription()
        );
    }

    public function testExecuteWithDefaultBatchSize(): void
    {
        $input = $this->createMock(InputInterface::class);
        $output = $this->createMock(OutputInterface::class);
        $io = $this->createMock(SymfonyStyle::class);

        $input->expects(self::once())
            ->method('getOption')
            ->with('batch-size')
            ->willReturn(null);

        $this->updateManager->expects(self::once())
            ->method('getPendingProductCount')
            ->willReturn(1500);

        $this->producer->expects(self::once())
            ->method('send')
            ->with(
                ProductFallbackUpdateTopic::getName(),
                [ProductFallbackUpdateTopic::BATCH_SIZE_OPTION => 500]
            );

        $this->alertProvider->expects(self::once())
            ->method('resolveCommandReminders');

        $result = $this->task->execute($input, $output, $io);

        self::assertSame('product_fallback', $result->getTaskName());
        self::assertTrue($result->isExecuted());
        self::assertSame(1500, $result->getScheduledCount());
        self::assertSame('Scheduled product fallback updates for 1500 products.', $result->getMessage());
    }

    public function testExecuteWithCustomBatchSize(): void
    {
        $input = $this->createMock(InputInterface::class);
        $output = $this->createMock(OutputInterface::class);
        $io = $this->createMock(SymfonyStyle::class);

        $input->expects(self::once())
            ->method('getOption')
            ->with('batch-size')
            ->willReturn('1000');

        $this->updateManager->expects(self::once())
            ->method('getPendingProductCount')
            ->willReturn(5000);

        $this->producer->expects(self::once())
            ->method('send')
            ->with(
                ProductFallbackUpdateTopic::getName(),
                [ProductFallbackUpdateTopic::BATCH_SIZE_OPTION => 1000]
            );

        $this->alertProvider->expects(self::once())
            ->method('resolveCommandReminders');

        $result = $this->task->execute($input, $output, $io);

        self::assertSame('product_fallback', $result->getTaskName());
        self::assertTrue($result->isExecuted());
        self::assertSame(5000, $result->getScheduledCount());
        self::assertSame('Scheduled product fallback updates for 5000 products.', $result->getMessage());
    }
}
