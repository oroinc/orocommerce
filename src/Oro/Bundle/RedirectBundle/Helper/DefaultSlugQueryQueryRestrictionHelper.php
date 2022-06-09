<?php

namespace Oro\Bundle\RedirectBundle\Helper;

use Doctrine\ORM\QueryBuilder;

/**
 * The default implementation of SlugQueryRestrictionHelperInterface
 */
class DefaultSlugQueryQueryRestrictionHelper implements SlugQueryRestrictionHelperInterface
{
    public function restrictQueryBuilder(QueryBuilder $qb): QueryBuilder
    {
        return $qb;
    }
}
