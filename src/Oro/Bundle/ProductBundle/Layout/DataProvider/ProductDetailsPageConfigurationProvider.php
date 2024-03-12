<?php

declare(strict_types=1);

namespace Oro\Bundle\ProductBundle\Layout\DataProvider;

use Oro\Bundle\ConfigBundle\Config\ConfigManager;
use Oro\Bundle\ProductBundle\DependencyInjection\Configuration;

/**
 * Provides various theme configuration options related to product details page.
 * #BB-23597
 */
class ProductDetailsPageConfigurationProvider
{
    public function __construct(
        protected ConfigManager $configManager
    ) {
    }

    /**
     * Example:
     *      '=data["product_details_page_configuration"].getDisplayPriceTiersAs()'
     */
    public function getDisplayPriceTiersAs(): string
    {
        return $this->configManager->get(
            Configuration::getConfigKeyByName(Configuration::DISPLAY_PRICE_TIERS_AS)
        );
    }
}
