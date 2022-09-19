<?php

namespace Oro\Bundle\SEOBundle\Modifier;

use Doctrine\ORM\QueryBuilder;
use Oro\Bundle\LocaleBundle\Provider\LocalizationScopeCriteriaProvider;
use Oro\Bundle\ScopeBundle\Manager\ScopeManager;

/**
 * Applies sitemap criteria to query in query builder to filter out all corresponding entity
 */
class ScopeQueryBuilderModifier implements ScopeQueryBuilderModifierInterface
{
    private ScopeManager $scopeManager;

    public function __construct(ScopeManager $scopeManager)
    {
        $this->scopeManager = $scopeManager;
    }

    public function applyScopeCriteria(QueryBuilder $queryBuilder, string $fieldAlias): void
    {
        $scopeCriteria = $this->scopeManager->getCriteria('web_content_for_sitemap');
        $scopeCriteria->applyWhereWithPriority(
            $queryBuilder,
            $fieldAlias,
            [LocalizationScopeCriteriaProvider::LOCALIZATION]
        );
    }
}
