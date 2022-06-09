<?php

namespace Oro\Bundle\RedirectBundle\Helper;

use Doctrine\ORM\QueryBuilder;

/**
 * This interface allows to apply a restriction to query that select slugs
 */
interface SlugQueryRestrictionHelperInterface
{
    public function restrictQueryBuilder(QueryBuilder $qb): QueryBuilder;
}
