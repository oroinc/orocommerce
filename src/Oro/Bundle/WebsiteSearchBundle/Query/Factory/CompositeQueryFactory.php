<?php

namespace Oro\Bundle\WebsiteSearchBundle\Query\Factory;

use Oro\Bundle\SearchBundle\Engine\EngineV2Interface;
use Oro\Bundle\SearchBundle\Query\Factory\QueryFactoryInterface;

class CompositeQueryFactory implements QueryFactoryInterface
{
    /** @var QueryFactoryInterface */
    protected $backendQueryFactory;

    /** @var QueryFactoryInterface */
    protected $websiteQueryFactory;

    /**
     * @param QueryFactoryInterface $backendQueryFactory
     * @param QueryFactoryInterface $websiteQueryFactory
     */
    public function __construct(
        QueryFactoryInterface $backendQueryFactory,
        QueryFactoryInterface $websiteQueryFactory
    ) {
        $this->backendQueryFactory = $backendQueryFactory;
        $this->websiteQueryFactory = $websiteQueryFactory;
    }

    /**
     * {@inheritdoc}
     */
    public function create(array $config = [])
    {
        if (!isset($config['search_index']) || $config['search_index'] !== 'website') {
            return $this->backendQueryFactory->create($config);
        } else {
            return $this->websiteQueryFactory->create($config);
        }
    }
}
