<?php

namespace Oro\Bundle\ProductBundle\EventListener\Visibility\Restrictions;

use Oro\Bundle\ConfigBundle\Config\ConfigManager;
use Oro\Bundle\FrontendBundle\Request\FrontendHelper;
use Oro\Bundle\ProductBundle\DependencyInjection\Configuration;
use Oro\Bundle\ProductBundle\Event\ProductDBQueryRestrictionEvent;
use Oro\Bundle\ProductBundle\Event\ProductSearchQueryRestrictionEvent;
use Oro\Bundle\SearchBundle\Query\Criteria\Criteria;
use Oro\Bundle\SearchBundle\Query\Modifier\QueryBuilderModifierInterface;

/**
 * The listener that adds restriction condition to the query
 * that filters out all products that are variation of configurable product
 */
class RestrictProductVariationsEventListener
{
    /** @var ConfigManager */
    private $configManager;

    /** @var FrontendHelper */
    private $frontendHelper;

    /** @var QueryBuilderModifierInterface */
    private $dbQueryBuilderModifier;

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
     * @param QueryBuilderModifierInterface $dbQueryBuilderModifier
     */
    public function setDBQueryBuilderModifier(QueryBuilderModifierInterface $dbQueryBuilderModifier)
    {
        $this->dbQueryBuilderModifier = $dbQueryBuilderModifier;
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
            $this->dbQueryBuilderModifier->modify($event->getQueryBuilder());
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
