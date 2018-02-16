<?php

namespace Oro\Bundle\ProductBundle\EventListener\Visibility\Restrictions;

use Oro\Bundle\ConfigBundle\Config\ConfigManager;
use Oro\Bundle\FrontendBundle\Request\FrontendHelper;
use Oro\Bundle\ProductBundle\DependencyInjection\Configuration;
use Oro\Bundle\ProductBundle\Event\ProductDBQueryRestrictionEvent;
use Oro\Bundle\ProductBundle\Event\ProductSearchQueryRestrictionEvent;
use Oro\Bundle\SearchBundle\Query\Criteria\Criteria;

class RestrictProductVariationsEventListener
{
    /** @var ConfigManager */
    private $configManager;

    /** @var FrontendHelper */
    private $frontendHelper;

    /**
     * @param ConfigManager  $configManager
     * @param FrontendHelper $frontendHelper
     */
    public function __construct(ConfigManager $configManager, FrontendHelper $frontendHelper)
    {
        $this->configManager = $configManager;
        $this->frontendHelper = $frontendHelper;
    }

    /**
     * @param ProductSearchQueryRestrictionEvent $event
     */
    public function onSearchQuery(ProductSearchQueryRestrictionEvent $event)
    {
        if ($this->isRestrictionApplicable()) {
            $event->getQuery()->getCriteria()->andWhere(
                Criteria::expr()->eq('integer.is_variant', 0)
            );
        }
    }

    /**
     * @param ProductDBQueryRestrictionEvent $event
     */
    public function onDBQuery(ProductDBQueryRestrictionEvent $event)
    {
        if ($this->isRestrictionApplicable()) {
            $qb = $event->getQueryBuilder();
            list($rootAlias) = $qb->getRootAliases();

            $qb->andWhere(sprintf('%s.parentVariantLinks IS EMPTY', $rootAlias));
        }
    }

    /**
     * @return bool
     */
    protected function isRestrictionApplicable()
    {
        $displaySimpleVariations = $this->getDisplayVariationsConfigurationValue();

        return $displaySimpleVariations === Configuration::DISPLAY_SIMPLE_VARIATIONS_HIDE_COMPLETELY &&
            $this->frontendHelper->isFrontendRequest();
    }

    /**
     * @return string
     */
    private function getDisplayVariationsConfigurationValue()
    {
        return $this->configManager
            ->get(sprintf('%s.%s', Configuration::ROOT_NODE, Configuration::DISPLAY_SIMPLE_VARIATIONS));
    }
}
