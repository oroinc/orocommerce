<?php

namespace Oro\Bundle\RedirectBundle\Model;

use Doctrine\ORM\QueryBuilder;

/**
 * The default implementation of the slug query builder modifier.
 */
class SlugQueryBuilderModifier implements SlugQueryBuilderModifierInterface
{
    #[\Override]
    public function modify(QueryBuilder $qb): void
    {
    }
}
