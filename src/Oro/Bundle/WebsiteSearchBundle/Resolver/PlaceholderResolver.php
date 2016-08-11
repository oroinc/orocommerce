<?php

namespace Oro\Bundle\WebsiteSearchBundle\Resolver;

use Oro\Bundle\SearchBundle\Query\Query;
use Oro\Bundle\WebsiteSearchBundle\Placeholder\WebsiteSearchPlaceholderRegistry;

class PlaceholderResolver
{
    /**
     * @var WebsiteSearchPlaceholderRegistry
     */
    private $placeholderRegistry;

    /**
     * @param WebsiteSearchPlaceholderRegistry $placeholderRegistry
     */
    public function __construct(WebsiteSearchPlaceholderRegistry $placeholderRegistry)
    {
        $this->placeholderRegistry = $placeholderRegistry;
    }

    /**
     * @param Query $query
     * @return Query
     */
    public function replace(Query $query)
    {
        foreach ($this->placeholderRegistry->getPlaceholders() as $placeholder => $placeholderValue) {
            $this->replaceInFrom($query, $placeholder, $placeholderValue);
        }

        return $query;
    }

    /**
     * @param Query $query
     * @param string $placeholder
     * @param string $placeholderValue
     * @return array
     */
    private function replaceInFrom(Query $query, $placeholder, $placeholderValue)
    {
        $entities = [];

        foreach ($query->getFrom() as $alias) {
            $alias = str_replace($placeholder, $placeholderValue, $alias);

            $entities[] = $alias;
        }

        return $query->from($entities);
    }
}
