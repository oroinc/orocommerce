<?php

namespace Oro\Bundle\WebsiteSearchBundle\Resolver;

use Oro\Bundle\SearchBundle\Query\Query;

/**
 * Defines the contract for replacing placeholders in website search queries.
 *
 * Implementations traverse all parts of a {@see Query} object (`SELECT`, `FROM`, `WHERE`, `ORDER BY`, aggregations)
 * and replace placeholder tokens in field names with their actual values. This transformation is essential
 * for executing queries against the website search index, converting abstract field references like
 * `price_WEBSITE_ID_CURRENCY` into concrete field names like `price_1_USD` based on the current context.
 * The resolver uses {@see PlaceholderInterface} implementations to perform the actual replacements.
 */
interface QueryPlaceholderResolverInterface
{
    public function replace(Query $query);
}
