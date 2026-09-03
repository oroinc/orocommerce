<?php

declare(strict_types=1);

namespace Oro\Bundle\ProductBundle\Tests\Unit\Async;

use Oro\Bundle\ProductBundle\Async\ProductFallbackFinalizeProcessor;
use Oro\Bundle\ProductBundle\Async\Topic\ProductFallbackFinalizeTopic;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\ProductBundle\Manager\ProductFallbackUpdateManager;
use Oro\Bundle\ProductBundle\NotificationAlert\ProductFallbackUpdateNotificationAlertProvider;
use Oro\Bundle\WebsiteSearchBundle\Event\ReindexationRequestEvent;
use Oro\Component\MessageQueue\Consumption\MessageProcessorInterface;
use Oro\Component\MessageQueue\Transport\Message;
use Oro\Component\MessageQueue\Transport\SessionInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;

final class ProductFallbackFinalizeProcessorTest extends TestCase
{
    private ProductFallbackUpdateManager&MockObject $updateManager;
    private ProductFallbackUpdateNotificationAlertProvider&MockObject $alertProvider;
    private EventDispatcherInterface&MockObject $eventDispatcher;
    private LoggerInterface&MockObject $logger;
    private ProductFallbackFinalizeProcessor $processor;

    protected function setUp(): void
    {
        $this->updateManager = $this->createMock(ProductFallbackUpdateManager::class);
        $this->alertProvider = $this->createMock(ProductFallbackUpdateNotificationAlertProvider::class);
        $this->eventDispatcher = $this->createMock(EventDispatcherInterface::class);
        $this->logger = $this->createMock(LoggerInterface::class);

        $this->processor = new ProductFallbackFinalizeProcessor(
            $this->updateManager,
            $this->alertProvider,
            $this->eventDispatcher
        );
        $this->processor->setLogger($this->logger);
    }

    public function testGetSubscribedTopics(): void
    {
        self::assertSame(
            [ProductFallbackFinalizeTopic::getName()],
            ProductFallbackFinalizeProcessor::getSubscribedTopics()
        );
    }

    public function testProcessSuccessfulFinalization(): void
    {
        $message = new Message();
        $session = $this->createMock(SessionInterface::class);

        $this->updateManager
            ->expects(self::once())
            ->method('hasPendingProducts')
            ->willReturn(false);

        $this->eventDispatcher
            ->expects(self::once())
            ->method('dispatch')
            ->with(
                self::callback(static function (ReindexationRequestEvent $event): bool {
                    self::assertSame([Product::class], $event->getClassesNames());
                    self::assertSame([], $event->getIds());
                    self::assertTrue($event->isScheduled());
                    self::assertNull($event->getFieldGroups());

                    return true;
                }),
                ReindexationRequestEvent::EVENT_NAME
            );

        $this->alertProvider
            ->expects(self::once())
            ->method('resolveCommandReminders');

        $this->logger
            ->expects(self::once())
            ->method('notice')
            ->with('Finished fixing product entity field fallback values');

        $result = $this->processor->process($message, $session);

        self::assertSame(MessageProcessorInterface::ACK, $result);
    }

    public function testProcessWithPendingProductsRequestsNoReindexationAndResolvesNoAlerts(): void
    {
        $message = new Message();
        $session = $this->createMock(SessionInterface::class);

        $this->updateManager
            ->expects(self::once())
            ->method('hasPendingProducts')
            ->willReturn(true);

        $this->eventDispatcher
            ->expects(self::never())
            ->method('dispatch');

        $this->alertProvider
            ->expects(self::never())
            ->method('resolveCommandReminders');

        $this->logger
            ->expects(self::never())
            ->method('notice');

        $result = $this->processor->process($message, $session);

        self::assertSame(MessageProcessorInterface::ACK, $result);
    }

    public function testProcessWithException(): void
    {
        $message = new Message();
        $session = $this->createMock(SessionInterface::class);
        $exception = new \RuntimeException('Test exception');

        $this->updateManager
            ->expects(self::once())
            ->method('hasPendingProducts')
            ->willThrowException($exception);

        $this->logger
            ->expects(self::once())
            ->method('error')
            ->with(
                self::stringContains('Failed to finalize product fallback updates'),
                ['exception' => $exception]
            );

        $result = $this->processor->process($message, $session);

        self::assertSame(MessageProcessorInterface::REJECT, $result);
    }

    public function testProcessWithExceptionInResolveCommandReminders(): void
    {
        $message = new Message();
        $session = $this->createMock(SessionInterface::class);
        $exception = new \RuntimeException('Alert provider exception');

        $this->updateManager
            ->expects(self::once())
            ->method('hasPendingProducts')
            ->willReturn(false);

        // Requested before the reminders are resolved, so a failure here leaves the reminder active.
        $this->eventDispatcher
            ->expects(self::once())
            ->method('dispatch');

        $this->alertProvider
            ->expects(self::once())
            ->method('resolveCommandReminders')
            ->willThrowException($exception);

        $this->logger
            ->expects(self::once())
            ->method('error')
            ->with(
                self::stringContains('Failed to finalize product fallback updates'),
                ['exception' => $exception]
            );

        $result = $this->processor->process($message, $session);

        self::assertSame(MessageProcessorInterface::REJECT, $result);
    }
}
