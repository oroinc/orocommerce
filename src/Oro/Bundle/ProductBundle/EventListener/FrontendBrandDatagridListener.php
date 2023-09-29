<?php

namespace Oro\Bundle\ProductBundle\EventListener;

use Oro\Bundle\DataGridBundle\Event\BuildBefore;
use Oro\Bundle\ProductBundle\Filter\FrontendBrandFilter;

/**
 * This class will help the brand filtering frontend to use a custom filter.
 */
class FrontendBrandDatagridListener
{
    private const FILTERS_COLUMNS_BRAND_TYPE_PATH = '[filters][columns][brand][type]';

    public function onBuildBefore(BuildBefore $event): void
    {
        $config = $event->getConfig();

        if ($config->offsetExistByPath(self::FILTERS_COLUMNS_BRAND_TYPE_PATH)) {
            $config->offsetSetByPath(
                self::FILTERS_COLUMNS_BRAND_TYPE_PATH,
                FrontendBrandFilter::FILTER_ALIAS
            );
        }
    }
}
