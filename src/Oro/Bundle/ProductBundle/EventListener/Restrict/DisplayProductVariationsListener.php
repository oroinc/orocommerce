<?php

namespace Oro\Bundle\ProductBundle\EventListener\Restrict;

use Oro\Bundle\ConfigBundle\Config\ConfigManager;
use Oro\Bundle\ProductBundle\DependencyInjection\Configuration;
use Oro\Bundle\ProductBundle\Event\ProductDBQueryRestrictionEvent;

/**
 * @deprecated since 1.6 will be removed after 1.6.
 * Use {@see Oro\Bundle\ProductBundle\EventListener\Visibility\Restrictions\RestrictProductVariationsEventListener}
 * instead.
 */
class DisplayProductVariationsListener
{
    /** @var ConfigManager */
    private $configManager;

    /**
     * @param ConfigManager $configManager
     */
    public function __construct(ConfigManager $configManager)
    {
        $this->configManager = $configManager;
    }

    /**
     * @param ProductDBQueryRestrictionEvent $event
     */
    public function onDBQuery(ProductDBQueryRestrictionEvent $event)
    {
        $qb = $event->getQueryBuilder();
        list($rootAlias) = $qb->getRootAliases();

        $displaySimpleVariations = $this->configManager
            ->get(sprintf('%s.%s', Configuration::ROOT_NODE, Configuration::DISPLAY_SIMPLE_VARIATIONS));

        if ($displaySimpleVariations === Configuration::DISPLAY_SIMPLE_VARIATIONS_HIDE_COMPLETELY) {
            $qb->andWhere(sprintf('%s.parentVariantLinks IS EMPTY', $rootAlias));
        }
    }
}
