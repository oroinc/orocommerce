<?php

namespace Oro\Bundle\WebsiteSearchBundle\Query\Factory;

use Oro\Bundle\SearchBundle\Engine\EngineV2Interface;
use Oro\Bundle\SearchBundle\Query\Factory\QueryFactoryInterface;

class CompositeQueryFactory implements QueryFactoryInterface
{
    use WebsiteQueryFactoryTrait;

    /** @var QueryFactoryInterface */
    protected $parent;

    /**
     * @param QueryFactoryInterface $parentQueryFactory
     * @param EngineV2Interface $engine
     */
    public function __construct(
        QueryFactoryInterface $parentQueryFactory,
        EngineV2Interface $engine
    ) {
        $this->parent = $parentQueryFactory;
        $this->engine = $engine;
    }

    /**
     * {@inheritdoc}
     */
    public function create(array $config = [])
    {
        if (!isset($config['search_index']) || $config['search_index'] !== 'website') {
            return $this->parent->create($config);
        }

        return $this->createWebsiteSearchQuery($config);
    }
}
