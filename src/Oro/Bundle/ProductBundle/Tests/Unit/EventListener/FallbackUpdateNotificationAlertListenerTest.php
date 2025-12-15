<?php

namespace Oro\Bundle\ProductBundle\Tests\Unit\EventListener;

use Oro\Bundle\InstallerBundle\Command\PlatformUpdateCommand;
use Oro\Bundle\ProductBundle\EventListener\FallbackUpdateNotificationAlertListener;
use Oro\Bundle\ProductBundle\NotificationAlert\ProductFallbackUpdateNotificationAlertProvider;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\ConsoleEvents;
use Symfony\Component\Console\Event\ConsoleTerminateEvent;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\BufferedOutput;

final class FallbackUpdateNotificationAlertListenerTest extends TestCase
{
    private ProductFallbackUpdateNotificationAlertProvider&MockObject $alertProvider;
    private FallbackUpdateNotificationAlertListener $listener;

    #[\Override]
    protected function setUp(): void
    {
        $this->alertProvider = $this->createMock(ProductFallbackUpdateNotificationAlertProvider::class);
        $this->listener = new FallbackUpdateNotificationAlertListener($this->alertProvider);
    }

    public function testGetSubscribedEvents(): void
    {
        $expectedEvents = [
            ConsoleEvents::TERMINATE => 'onConsoleTerminate',
        ];

        self::assertSame($expectedEvents, FallbackUpdateNotificationAlertListener::getSubscribedEvents());
    }

    public function testOnConsoleTerminateDoesNothingForNonPlatformUpdateCommand(): void
    {
        $command = $this->createMock(Command::class);
        $command->expects(self::once())
            ->method('getName')
            ->willReturn('some:other:command');


        $input = $this->createMock(InputInterface::class);
        $output = new BufferedOutput();

        $event = new ConsoleTerminateEvent($command, $input, $output, 0);

        $this->alertProvider->expects(self::never())
            ->method('hasPendingReminders');

        $this->listener->onConsoleTerminate($event);

        self::assertEmpty($output->fetch());
    }

    public function testOnConsoleTerminateDoesNothingWhenNoPendingReminders(): void
    {
        $command = $this->createMock(PlatformUpdateCommand::class);
        $command->expects(self::once())
            ->method('getName')
            ->willReturn(PlatformUpdateCommand::getDefaultName());

        $input = $this->createMock(InputInterface::class);
        $output = new BufferedOutput();

        $event = new ConsoleTerminateEvent($command, $input, $output, 0);

        $this->alertProvider->expects(self::once())
            ->method('hasPendingReminders')
            ->willReturn(false);

        $this->listener->onConsoleTerminate($event);

        self::assertEmpty($output->fetch());
    }

    public function testOnConsoleTerminateDisplaysWarningWhenPendingRemindersExist(): void
    {
        $command = $this->createMock(PlatformUpdateCommand::class);
        $command->expects(self::once())
            ->method('getName')
            ->willReturn(PlatformUpdateCommand::getDefaultName());

        $input = $this->createMock(InputInterface::class);
        $output = new BufferedOutput();

        $event = new ConsoleTerminateEvent($command, $input, $output, 0);

        $this->alertProvider->expects(self::once())
            ->method('hasPendingReminders')
            ->willReturn(true);

        $this->listener->onConsoleTerminate($event);

        $outputContent = $output->fetch();

        $normalizedOutput = preg_replace('/\s+/', ' ', $outputContent);

        self::assertStringContainsString('WARNING', $outputContent);
        self::assertStringContainsString('Product fallback data was not processed automatically', $normalizedOutput);
        self::assertStringContainsString('to avoid long upgrade downtime', $normalizedOutput);
        self::assertStringContainsString('oro:platform:post-upgrade-tasks --task=product_fallback', $normalizedOutput);
        self::assertStringContainsString('backfill the data asynchronously', $normalizedOutput);
    }
}
