<?php

declare(strict_types=1);

namespace Oro\Bundle\ProductBundle\Migrations\Data\ORM;

use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Persistence\ObjectManager;
use Oro\Bundle\ProductBundle\NotificationAlert\ProductFallbackUpdateNotificationAlertProvider;
use Symfony\Component\DependencyInjection\ContainerAwareInterface;
use Symfony\Component\DependencyInjection\ContainerAwareTrait;

/**
 * Preload fallback values for product page template field and product inventory fields.
 */
final class UpdateProductPageTemplateFallbackData extends AbstractFixture implements ContainerAwareInterface
{
    use ContainerAwareTrait;

    private const MAX_PRODUCTS_FOR_SYNC_FIX = 15000;
    private const CHUNK_SIZE = 1000;

    #[\Override]
    public function load(ObjectManager $manager): void
    {
        if (!$this->container?->has('oro_product.notification_alert.fallback_update_provider')) {
            return;
        }

        /** @var ProductFallbackUpdateNotificationAlertProvider $provider */
        $provider = $this->container->get('oro_product.notification_alert.fallback_update_provider');

        $pendingProductCount = $provider->getPendingProductCount();
        if (!$pendingProductCount) {
            return;
        }

        if ($pendingProductCount < self::MAX_PRODUCTS_FOR_SYNC_FIX) {
            // Fix products during migration
            $provider->fixProductsFallbacks(self::CHUNK_SIZE);
        } else {
            // Too many products - create notification to run command manually
            $provider->scheduleCommandReminder();
        }
    }
}
