<?php

namespace Oro\Bundle\WebsiteSearchBundle\Query\Factory;

use Oro\Bundle\SearchBundle\Engine\EngineV2Interface;
use Oro\Bundle\SearchBundle\Query\Factory\QueryFactoryInterface;

class WebsiteQueryFactory implements QueryFactoryInterface
{
    use WebsiteQueryFactoryTrait;

    /**
     * @param EngineV2Interface $engine
     */
    public function __construct(
        EngineV2Interface $engine
    ) {
        $this->engine = $engine;
    }

    /**
     * {@inheritdoc}
     */
    public function create(array $config = [])
    {
        return $this->createWebsiteSearchQuery($config);
    }
}
