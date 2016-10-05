<?php

namespace Oro\Bundle\WebsiteSearchBundle\Query;

use Oro\Bundle\SearchBundle\Provider\AbstractSearchMappingProvider;
use Oro\Bundle\SearchBundle\Query\Factory\QueryFactoryInterface;
use Oro\Bundle\SearchBundle\Query\SearchRepository;

class WebsiteSearchRepository extends SearchRepository
{
    /**
     * {@inheritdoc}
     */
    public function __construct(
        QueryFactoryInterface $queryFactory,
        AbstractSearchMappingProvider $mappingProvider
    ) {
        parent::__construct($queryFactory, $mappingProvider);

        $this->queryConfiguration['search_index'] = 'website';
    }
}
