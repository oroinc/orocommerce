<?php

declare(strict_types=1);

namespace Oro\Bundle\ProductBundle\PostUpgrade;

use Oro\Bundle\PlatformBundle\PostUpgrade\PostUpgradeTaskInterface;
use Oro\Bundle\PlatformBundle\PostUpgrade\PostUpgradeTaskResult;
use Oro\Bundle\ProductBundle\Async\Topic\ProductFallbackUpdateTopic;
use Oro\Bundle\ProductBundle\Manager\ProductFallbackUpdateManager;
use Oro\Bundle\ProductBundle\NotificationAlert\ProductFallbackUpdateNotificationAlertProvider;
use Oro\Component\MessageQueue\Client\MessageProducerInterface;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

/**
 * Task for updating product fallback values
 */
class ProductFallbackUpdateTask implements PostUpgradeTaskInterface
{
    public function __construct(
        private MessageProducerInterface $producer,
        private ProductFallbackUpdateManager $updateManager,
        private ProductFallbackUpdateNotificationAlertProvider $alertProvider
    ) {
    }

    #[\Override]
    public function getName(): string
    {
        return 'product_fallback';
    }

    #[\Override]
    public function getDescription(): string
    {
        return 'Update product fallback values (page template, inventory, etc.)';
    }

    #[\Override]
    public function execute(InputInterface $input, OutputInterface $output, SymfonyStyle $io): PostUpgradeTaskResult
    {
        $batchSize = (int)($input->getOption('batch-size') ?? 500);
        $pendingCount = $this->updateManager->getPendingProductCount();

        // Send to queue
        $this->producer->send(ProductFallbackUpdateTopic::getName(), [
            ProductFallbackUpdateTopic::BATCH_SIZE_OPTION => $batchSize,
        ]);

        // Resolve notification alerts
        $this->alertProvider->resolveCommandReminders();

        return new PostUpgradeTaskResult(
            taskName: $this->getName(),
            executed: true,
            scheduledCount: $pendingCount,
            message: sprintf(
                'Scheduled product fallback updates for %d products.',
                $pendingCount
            )
        );
    }
}
