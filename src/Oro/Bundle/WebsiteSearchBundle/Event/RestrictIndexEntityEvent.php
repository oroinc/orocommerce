<?php

namespace Oro\Bundle\WebsiteSearchBundle\Event;

use Doctrine\ORM\QueryBuilder;
use Symfony\Contracts\EventDispatcher\Event;

/**
 * Dispatched during entity indexation to allow modification of the query that selects entities to be indexed.
 *
 * Event listeners can modify the provided {@see QueryBuilder} to restrict which entities are included
 * in the search index. This is useful for filtering entities based on status, visibility, organization,
 * website, or other criteria. For example, listeners can exclude disabled products, restrict indexation
 * to specific organizations, or filter entities based on website-specific configuration. The event provides access
 * to the indexation context which contains information about the current website and other parameters.
 */
class RestrictIndexEntityEvent extends Event
{
    public const NAME = 'oro_website_search.event.restrict_index_entity';

    /** @var QueryBuilder */
    protected $queryBuilder;

    /** @var array */
    protected $context;

    public function __construct(QueryBuilder $qb, array $context)
    {
        $this->queryBuilder = $qb;
        $this->context = $context;
    }

    /**
     * @return QueryBuilder
     */
    public function getQueryBuilder()
    {
        return $this->queryBuilder;
    }

    /**
     * @return array
     */
    public function getContext()
    {
        return $this->context;
    }
}
