<?php

namespace Oro\Bundle\WebsiteSearchBundle\Engine;

use Oro\Bundle\SearchBundle\Engine\EngineV2Interface;
use Oro\Bundle\SearchBundle\Provider\AbstractSearchMappingProvider;
use Oro\Bundle\SearchBundle\Query\Query;
use Oro\Bundle\SearchBundle\Query\Result;
use Oro\Bundle\WebsiteSearchBundle\Event\BeforeSearchEvent;
use Oro\Bundle\WebsiteSearchBundle\Resolver\QueryPlaceholderResolverInterface;

use Symfony\Component\EventDispatcher\EventDispatcherInterface;

abstract class AbstractEngine implements EngineV2Interface
{
    /** @var EventDispatcherInterface */
    private $eventDispatcher;

    /** @var QueryPlaceholderResolverInterface */
    private $queryPlaceholderResolver;

    /** @var AbstractSearchMappingProvider */
    protected $mappingProvider;

    /**
     * @param EventDispatcherInterface $eventDispatcher
     * @param QueryPlaceholderResolverInterface $queryPlaceholderResolver
     * @param AbstractSearchMappingProvider $mappingProvider
     */
    public function __construct(
        EventDispatcherInterface $eventDispatcher,
        QueryPlaceholderResolverInterface $queryPlaceholderResolver,
        AbstractSearchMappingProvider $mappingProvider
    ) {
        $this->eventDispatcher = $eventDispatcher;
        $this->queryPlaceholderResolver = $queryPlaceholderResolver;
        $this->mappingProvider = $mappingProvider;
    }

    /**
     * @param Query $query
     * @param array $context
     * @return Result
     */
    abstract protected function doSearch(Query $query, array $context = []);

    /**
     * {@inheritdoc}
     */
    public function search(Query $query, array $context = [])
    {
        $event = new BeforeSearchEvent($query, $context);
        $this->eventDispatcher->dispatch(BeforeSearchEvent::EVENT_NAME, $event);

        $query = $event->getQuery();

        $this->queryPlaceholderResolver->replace($query);
$where = $query->getCriteria()->getWhereExpression();

        return $this->doSearch($query, $context);
    }
}
