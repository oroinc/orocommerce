<?php

namespace Oro\Bundle\WebsiteSearchBundle\Query\Factory;

use Oro\Bundle\SearchBundle\Query\Factory\QueryFactoryInterface;

/**
 * Composite query factory that routes between backend and website search query creation.
 *
 * This factory implements {@see QueryFactoryInterface} and acts as a router to delegate query creation
 * to the appropriate underlying factory based on the search index type specified in the configuration.
 * When the 'search_index' parameter is set to 'website', it uses the website search query factory to create queries
 * against the storefront search index. For all other cases (including when the parameter is absent),
 * it delegates to the backend query factory for the standard admin search index.
 * This separation allows the application to maintain two independent search indexes with different scopes
 * and configurations.
 */
class CompositeQueryFactory implements QueryFactoryInterface
{
    /** @var QueryFactoryInterface */
    protected $backendQueryFactory;

    /** @var QueryFactoryInterface */
    protected $websiteQueryFactory;

    public function __construct(
        QueryFactoryInterface $backendQueryFactory,
        QueryFactoryInterface $websiteQueryFactory
    ) {
        $this->backendQueryFactory = $backendQueryFactory;
        $this->websiteQueryFactory = $websiteQueryFactory;
    }

    #[\Override]
    public function create(array $config = [])
    {
        if (!isset($config['search_index']) || $config['search_index'] !== 'website') {
            return $this->backendQueryFactory->create($config);
        } else {
            return $this->websiteQueryFactory->create($config);
        }
    }
}
