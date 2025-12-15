<?php

declare(strict_types=1);

namespace Oro\Bundle\ProductBundle\Async;

use Oro\Bundle\ProductBundle\Async\Topic\ProductFallbackFinalizeTopic;
use Oro\Bundle\ProductBundle\Manager\ProductFallbackUpdateManager;
use Oro\Bundle\ProductBundle\NotificationAlert\ProductFallbackUpdateNotificationAlertProvider;
use Oro\Component\MessageQueue\Client\TopicSubscriberInterface;
use Oro\Component\MessageQueue\Consumption\MessageProcessorInterface;
use Oro\Component\MessageQueue\Transport\MessageInterface;
use Oro\Component\MessageQueue\Transport\SessionInterface;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerAwareTrait;
use Psr\Log\NullLogger;

/**
 * Finalizes product fallback updates by resolving notification alerts when processing is complete.
 */
class ProductFallbackFinalizeProcessor implements
    MessageProcessorInterface,
    TopicSubscriberInterface,
    LoggerAwareInterface
{
    use LoggerAwareTrait;

    public function __construct(
        private ProductFallbackUpdateManager $updateManager,
        private ProductFallbackUpdateNotificationAlertProvider $alertProvider
    ) {
        $this->logger = new NullLogger();
    }

    #[\Override]
    public static function getSubscribedTopics(): array
    {
        return [ProductFallbackFinalizeTopic::getName()];
    }

    #[\Override]
    public function process(MessageInterface $message, SessionInterface $session)
    {
        try {
            if ($this->updateManager->hasPendingProducts()) {
                return self::ACK;
            }

            $this->alertProvider->resolveCommandReminders();

            $this->logger->notice('Finished fixing product entity field fallback values');

            return self::ACK;
        } catch (\Throwable $exception) {
            $this->logger->error(
                'Failed to finalize product fallback updates. '
                . 'The processor was unable to resolve notification alerts '
                . 'after completing the update of product fallback fields. '
                . 'The notification alert may remain active in the system until manually resolved. '
                . 'To retry, run the command: bin/console oro:platform:post-upgrade-tasks --task=product_fallback',
                ['exception' => $exception]
            );

            return self::REJECT;
        }
    }
}
