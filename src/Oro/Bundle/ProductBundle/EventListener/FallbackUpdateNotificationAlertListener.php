<?php

declare(strict_types=1);

namespace Oro\Bundle\ProductBundle\EventListener;

use Oro\Bundle\InstallerBundle\Command\PlatformUpdateCommand;
use Oro\Bundle\ProductBundle\NotificationAlert\ProductFallbackUpdateNotificationAlert;
use Oro\Bundle\ProductBundle\NotificationAlert\ProductFallbackUpdateNotificationAlertProvider;
use Symfony\Component\Console\ConsoleEvents;
use Symfony\Component\Console\Event\ConsoleTerminateEvent;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Displays a warning after platform:update when fallback data still needs to be processed.
 */
class FallbackUpdateNotificationAlertListener implements EventSubscriberInterface
{
    public function __construct(private ProductFallbackUpdateNotificationAlertProvider $alertProvider)
    {
    }

    #[\Override]
    public static function getSubscribedEvents(): array
    {
        return [
            ConsoleEvents::TERMINATE => 'onConsoleTerminate',
        ];
    }

    public function onConsoleTerminate(ConsoleTerminateEvent $event): void
    {
        if ($event->getCommand()?->getName() !== PlatformUpdateCommand::getDefaultName()) {
            return;
        }

        if (!$this->alertProvider->hasPendingReminders()) {
            return;
        }

        $io = new SymfonyStyle($event->getInput(), $event->getOutput());
        $io->warning([
            'Product fallback data was not processed automatically to avoid long upgrade downtime.',
            sprintf(
                'Run the "%s" command to backfill the data asynchronously once the upgrade is finished.',
                ProductFallbackUpdateNotificationAlert::COMMAND_NAME
            ),
        ]);
    }
}
