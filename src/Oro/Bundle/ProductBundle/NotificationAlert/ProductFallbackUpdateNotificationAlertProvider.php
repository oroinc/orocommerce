<?php

declare(strict_types=1);

namespace Oro\Bundle\ProductBundle\NotificationAlert;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\NotificationBundle\Entity\NotificationAlert;
use Oro\Bundle\NotificationBundle\NotificationAlert\NotificationAlertManager;
use Oro\Bundle\OrganizationBundle\Entity\Organization;
use Oro\Bundle\ProductBundle\Manager\ProductFallbackUpdateManager;
use Psr\Log\LoggerInterface;

/**
 * Helper that stores and resolves notification alerts related
 * to the product fallback update command.
 */
class ProductFallbackUpdateNotificationAlertProvider
{
    public function __construct(
        private readonly NotificationAlertManager $notificationAlertManager,
        private readonly ManagerRegistry $doctrine,
        private readonly ProductFallbackUpdateManager $updateManager,
        private readonly LoggerInterface $logger
    ) {
    }

    public function scheduleCommandReminder(?string $message = null): void
    {
        $message ??= ProductFallbackUpdateNotificationAlert::buildDefaultMessage();
        $organizations = $this->doctrine->getRepository(Organization::class)->findAll();
        if (!$organizations) {
            return;
        }

        foreach ($organizations as $organization) {
            $this->notificationAlertManager->addNotificationAlert(
                ProductFallbackUpdateNotificationAlert::createForCommandReminder(
                    $message,
                    $organization->getId()
                )
            );
        }
    }

    public function resolveCommandReminders(): void
    {
        $organizations = $this->doctrine->getRepository(Organization::class)->findAll();
        if (!$organizations) {
            return;
        }

        foreach ($organizations as $organization) {
            $this->notificationAlertManager->resolveNotificationAlertsByAlertTypeForUserAndOrganization(
                ProductFallbackUpdateNotificationAlert::ALERT_TYPE_COMMAND_REQUIRED,
                null,
                $organization->getId()
            );
        }
    }

    public function hasPendingReminders(): bool
    {
        /** @var EntityManagerInterface $em */
        $em = $this->doctrine->getManagerForClass(NotificationAlert::class);
        $qb = $em->createQueryBuilder();

        $count = (int) $qb
            ->select('COUNT(alert.id)')
            ->from(NotificationAlert::class, 'alert')
            ->where('alert.sourceType = :source')
            ->andWhere('alert.resourceType = :resource')
            ->andWhere('alert.alertType = :alertType')
            ->andWhere('alert.resolved = :resolved')
            ->setParameter('source', ProductFallbackUpdateNotificationAlert::SOURCE_TYPE)
            ->setParameter('resource', ProductFallbackUpdateNotificationAlert::RESOURCE_TYPE)
            ->setParameter('alertType', ProductFallbackUpdateNotificationAlert::ALERT_TYPE_COMMAND_REQUIRED)
            ->setParameter('resolved', false)
            ->getQuery()
            ->getSingleScalarResult();

        return $count > 0;
    }

    public function getPendingProductCount(): int
    {
        return $this->updateManager->getPendingProductCount();
    }

    public function fixProductsFallbacks(int $chunkSize): int
    {
        $this->logger->notice('Started fixing product entity field fallback values');

        try {
            $updatedCount = 0;

            foreach ($this->updateManager->getProductIdChunks($chunkSize) as $productIds) {
                $updatedCount += $this->updateManager->processChunk($productIds);
            }

            return $updatedCount;
        } finally {
            $this->logger->notice('Finished fixing product entity field fallback values');
        }
    }
}
