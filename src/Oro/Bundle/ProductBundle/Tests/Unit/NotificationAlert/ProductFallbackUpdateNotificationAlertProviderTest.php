<?php

declare(strict_types=1);

namespace Oro\Bundle\ProductBundle\Tests\Unit\NotificationAlert;

use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\NotificationBundle\NotificationAlert\NotificationAlertManager;
use Oro\Bundle\ProductBundle\Manager\ProductFallbackUpdateManager;
use Oro\Bundle\ProductBundle\NotificationAlert\ProductFallbackUpdateNotificationAlertProvider;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

final class ProductFallbackUpdateNotificationAlertProviderTest extends TestCase
{
    private NotificationAlertManager&MockObject $notificationAlertManager;
    private ManagerRegistry&MockObject $doctrine;
    private ProductFallbackUpdateManager&MockObject $updateManager;
    private LoggerInterface&MockObject $logger;
    private ProductFallbackUpdateNotificationAlertProvider $provider;

    #[\Override]
    protected function setUp(): void
    {
        $this->notificationAlertManager = $this->createMock(NotificationAlertManager::class);
        $this->doctrine = $this->createMock(ManagerRegistry::class);
        $this->updateManager = $this->createMock(ProductFallbackUpdateManager::class);
        $this->logger = $this->createMock(LoggerInterface::class);

        $this->provider = new ProductFallbackUpdateNotificationAlertProvider(
            $this->notificationAlertManager,
            $this->doctrine,
            $this->updateManager,
            $this->logger
        );
    }

    public function testGetPendingProductCount(): void
    {
        $expectedCount = 250;

        $this->updateManager->expects(self::once())
            ->method('getPendingProductCount')
            ->willReturn($expectedCount);

        $result = $this->provider->getPendingProductCount();

        self::assertSame($expectedCount, $result);
    }

    public function testFixProductsFallbacksProcessesChunks(): void
    {
        $chunkSize = 100;
        $chunks = [
            [1, 2, 3],
            [4, 5, 6],
            [7, 8, 9],
        ];

        $this->updateManager->expects(self::once())
            ->method('getProductIdChunks')
            ->with($chunkSize)
            ->willReturn(new \ArrayIterator($chunks));

        $callCount = 0;
        $this->updateManager->expects(self::exactly(3))
            ->method('processChunk')
            ->willReturnCallback(function ($productIds) use ($chunks, &$callCount) {
                self::assertSame($chunks[$callCount], $productIds);
                $callCount++;

                return match ($callCount) {
                    1 => 2,
                    2 => 3,
                    3 => 1,
                };
            });

        $callCountLogger = 0;
        $this->logger->expects(self::exactly(2))
            ->method('notice')
            ->willReturnCallback(function ($message) use (&$callCountLogger) {
                $callCountLogger++;

                if ($callCountLogger === 1) {
                    self::assertSame('Started fixing product entity field fallback values', $message);
                } elseif ($callCountLogger === 2) {
                    self::assertSame('Finished fixing product entity field fallback values', $message);
                }
            });

        $result = $this->provider->fixProductsFallbacks($chunkSize);

        self::assertSame(6, $result);
    }

    public function testFixProductsFallbacksReturnsZeroWhenNoChunks(): void
    {
        $chunkSize = 50;

        $this->updateManager->expects(self::once())
            ->method('getProductIdChunks')
            ->with($chunkSize)
            ->willReturn(new \ArrayIterator([]));

        $this->updateManager->expects(self::never())
            ->method('processChunk');

        $callCount = 0;
        $this->logger->expects(self::exactly(2))
            ->method('notice')
            ->willReturnCallback(function ($message) use (&$callCount) {
                $callCount++;

                if ($callCount === 1) {
                    self::assertSame('Started fixing product entity field fallback values', $message);
                } elseif ($callCount === 2) {
                    self::assertSame('Finished fixing product entity field fallback values', $message);
                }
            });

        $result = $this->provider->fixProductsFallbacks($chunkSize);

        self::assertSame(0, $result);
    }

    public function testFixProductsFallbacksLogsNoticeEvenWhenExceptionThrown(): void
    {
        $chunkSize = 100;
        $exception = new \RuntimeException('Processing failed');

        $this->updateManager->expects(self::once())
            ->method('getProductIdChunks')
            ->with($chunkSize)
            ->willThrowException($exception);

        $callCount = 0;
        $this->logger->expects(self::exactly(2))
            ->method('notice')
            ->willReturnCallback(function ($message) use (&$callCount) {
                $callCount++;

                if ($callCount === 1) {
                    self::assertSame('Started fixing product entity field fallback values', $message);
                } elseif ($callCount === 2) {
                    self::assertSame('Finished fixing product entity field fallback values', $message);
                }
            });

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Processing failed');

        $this->provider->fixProductsFallbacks($chunkSize);
    }
}
