<?php

namespace Oro\Bundle\InventoryBundle\Provider;

use Oro\Bundle\ConfigBundle\Config\ConfigManager;
use Oro\Bundle\EntityBundle\Fallback\EntityFallbackResolver;
use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;
use Oro\Bundle\InventoryBundle\DependencyInjection\Configuration;
use Oro\Bundle\ProductBundle\Entity\Product;

/**
 * Provides information on product's upcoming status and availability date.
 * If oro_inventory.hide_labels_past_availability_date config option is on then product looses upcoming status after
 * availability date if it's given.
 */
class UpcomingProductProvider extends ProductUpcomingProvider
{
    /**
     * @var ConfigManager
     */
    private $configManager;

    /**
     * @param EntityFallbackResolver $entityFallbackResolver
     * @param DoctrineHelper $doctrineHelper
     * @param ConfigManager $configManager
     */
    public function __construct(
        EntityFallbackResolver $entityFallbackResolver,
        DoctrineHelper $doctrineHelper,
        ConfigManager $configManager
    ) {
        parent::__construct($entityFallbackResolver, $doctrineHelper);

        $this->configManager = $configManager;
    }

    /**
     * {@inheritdoc}
     */
    public function isUpcoming(Product $product)
    {
        if (!$this->isUpcomingStatusOffPastAvailabilityDate()) {
            return parent::isUpcoming($product);
        }

        if (!parent::isUpcoming($product)) {
            return false;
        }

        $availabilityDate = $this->extractDate($product);
        if ($availabilityDate && $availabilityDate < new \DateTime('now', new \DateTimeZone('UTC'))) {
            return false;
        }

        return true;
    }

    /**
     * @param Product $product
     * @throws \LogicException
     * @return \DateTime|null
     */
    public function getAvailabilityDate(Product $product)
    {
        if (!$this->isUpcomingStatusOffPastAvailabilityDate()) {
            return parent::getAvailabilityDate($product);
        }

        if (!$this->isUpcoming($product)) {
            throw new \LogicException('You cant get Availability Date for product, which is not upcoming');
        }

        return $this->extractDate($product);
    }

    /**
     * @return bool
     */
    private function isUpcomingStatusOffPastAvailabilityDate(): bool
    {
        return $this->configManager->get(
            sprintf('%s.%s', Configuration::ROOT_NODE, Configuration::HIDE_LABELS_PAST_AVAILABILITY_DATE)
        );
    }
}
