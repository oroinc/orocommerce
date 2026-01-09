<?php

namespace Oro\Bundle\WebsiteSearchBundle\Query;

use Oro\Bundle\SearchBundle\Provider\AbstractSearchMappingProvider;
use Oro\Bundle\SearchBundle\Query\Factory\QueryFactoryInterface;
use Oro\Bundle\SearchBundle\Query\SearchRepository;

/**
 * Provides a repository interface for creating website search queries.
 *
 * This repository extends {@see SearchRepository} to work specifically with the website search index rather than
 * the standard search index. It automatically configures created queries to use the `website` search index.
 * Developers can extend this class to create entity-specific search repositories that encapsulate common
 * search operations, similar to Doctrine entity repositories. This provides a clean abstraction
 * for building and executing website search queries in application code.
 */
class WebsiteSearchRepository extends SearchRepository
{
    public function __construct(
        QueryFactoryInterface $queryFactory,
        AbstractSearchMappingProvider $mappingProvider
    ) {
        parent::__construct($queryFactory, $mappingProvider);

        $this->queryConfiguration['search_index'] = 'website';
    }
}
