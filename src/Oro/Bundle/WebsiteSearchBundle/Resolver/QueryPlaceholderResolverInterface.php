<?php

namespace Oro\Bundle\WebsiteSearchBundle\Resolver;

use Oro\Bundle\SearchBundle\Query\Query;

interface QueryPlaceholderResolverInterface
{
    public function replace(Query $query);
}
