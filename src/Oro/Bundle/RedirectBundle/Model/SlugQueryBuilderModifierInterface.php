<?php

namespace Oro\Bundle\RedirectBundle\Model;

use Doctrine\ORM\QueryBuilder;

/**
 * The interface for classes that need to make a modification of a query builder object
 * in order to apply a restriction to query that is used to load slugs.
 */
interface SlugQueryBuilderModifierInterface
{
    public function modify(QueryBuilder $qb): void;
}
