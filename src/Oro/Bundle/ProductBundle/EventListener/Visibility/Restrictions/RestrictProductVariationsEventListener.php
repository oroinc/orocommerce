<?php

namespace Oro\Bundle\ProductBundle\EventListener\Visibility\Restrictions;

use Doctrine\Common\Collections\Expr\Comparison;
use Doctrine\Common\Collections\Expr\CompositeExpression;
use Doctrine\Common\Collections\Expr\Expression;
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

    public function __construct(
        ConfigManager $configManager,
        FrontendHelper $frontendHelper,
        QueryBuilderModifierInterface $dbQueryBuilderModifier
    ) {
        $this->configManager = $configManager;
        $this->frontendHelper = $frontendHelper;
        $this->dbQueryBuilderModifier = $dbQueryBuilderModifier;
    }

    public function onSearchQuery(ProductSearchQueryRestrictionEvent $event)
    {
        if ($this->isRestrictionApplicableForSearchEvent($event) &&
            !$this->isVariantCriteriaExist($event->getQuery()->getCriteria()->getWhereExpression())
        ) {
            $event->getQuery()->getCriteria()->andWhere(
                Criteria::expr()->eq('integer.is_variant', 0)
            );
        }
    }

    public function onDBQuery(ProductDBQueryRestrictionEvent $event)
    {
        if ($this->isRestrictionApplicableForDbEvent($event)) {
            $this->dbQueryBuilderModifier->modify($event->getQueryBuilder());
        }
    }

    protected function isRestrictionApplicableForSearchEvent(ProductSearchQueryRestrictionEvent $event): bool
    {
        if ($this->isRestrictionApplicable()) {
            return true;
        }

        $aliases = $event->getQuery()->getSelectAliases();
        if (!\in_array('autocomplete_record_id', $aliases, true) && $this->isCatalogRestrictionApplicable()) {
            return true;
        }

        return false;
    }

    protected function isRestrictionApplicableForDbEvent(ProductDBQueryRestrictionEvent $event): bool
    {
        return $this->isRestrictionApplicable();
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

    protected function isCatalogRestrictionApplicable(): bool
    {
        $displaySimpleVariations = $this->getDisplayVariationsConfigurationValue();

        return $displaySimpleVariations === Configuration::DISPLAY_SIMPLE_VARIATIONS_HIDE_CATALOG &&
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

    /**
     * @param Expression|null $expression
     * @return bool
     */
    private function isVariantCriteriaExist(?Expression $expression)
    {
        if ($expression instanceof Comparison && $expression->getField() === 'integer.is_variant') {
            return true;
        }

        if ($expression instanceof CompositeExpression) {
            foreach ($expression->getExpressionList() as $childExpression) {
                $result = $this->isVariantCriteriaExist($childExpression);
                if ($result) {
                    return true;
                }
            }
        }

        return false;
    }
}
