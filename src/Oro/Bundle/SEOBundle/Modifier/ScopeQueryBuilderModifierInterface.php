<?php

namespace Oro\Bundle\SEOBundle\Modifier;

use Doctrine\ORM\QueryBuilder;

/**
 * The interface for services that modifies query in query builder that contains scope and requires filtering
 * by sitemap scope criteria
 */
interface ScopeQueryBuilderModifierInterface
{
    public function applyScopeCriteria(QueryBuilder $queryBuilder, string $fieldAlias): void;
}
