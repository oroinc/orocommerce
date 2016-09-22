<?php

namespace Oro\Bundle\WebsiteSearchBundle\Resolver;

use Oro\Bundle\SearchBundle\Query\Query;

interface QueryPlaceholderResolverInterface
{
    /**
     * @param Query $query
     * @return Query
     */
    public function replace(Query $query);
}
