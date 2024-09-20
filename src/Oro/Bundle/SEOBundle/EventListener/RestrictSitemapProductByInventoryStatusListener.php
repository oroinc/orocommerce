<?php

namespace Oro\Bundle\SEOBundle\EventListener;

use Oro\Bundle\ConfigBundle\Config\ConfigManager;
use Oro\Bundle\SEOBundle\Event\RestrictSitemapEntitiesEvent;
use Oro\Bundle\SEOBundle\Sitemap\Provider\UrlItemsProvider;

/**
 * Add to sitemap only products with visible inventory statuses.
 */
class RestrictSitemapProductByInventoryStatusListener
{
    public function __construct(private ConfigManager $configManager)
    {
    }

    public function restrictQueryBuilder(RestrictSitemapEntitiesEvent $event): void
    {
        $allowedStatuses = $this->configManager->get(
            'oro_product.general_frontend_product_visibility',
            false,
            false,
            $event->getWebsite()
        );
        if ($allowedStatuses) {
            $qb = $event->getQueryBuilder();
            $qb->andWhere($qb->expr()->in(
                "JSON_EXTRACT(" . UrlItemsProvider::ENTITY_ALIAS . ".serialized_data, 'inventory_status')",
                ':inventoryStatuses'
            ))
                ->setParameter('inventoryStatuses', $allowedStatuses);
        } else {
            // When allowed statuses list is empty - hide all products
            $event->getQueryBuilder()->andWhere('1 = 0');
        }
    }
}
