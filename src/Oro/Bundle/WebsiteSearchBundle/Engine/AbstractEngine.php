<?php

namespace Oro\Bundle\WebsiteSearchBundle\Engine;

use Oro\Bundle\SearchBundle\Engine\EngineInterface;
use Oro\Bundle\SearchBundle\Provider\AbstractSearchMappingProvider;
use Oro\Bundle\SearchBundle\Query\Query;
use Oro\Bundle\SearchBundle\Query\Result;
use Oro\Bundle\WebsiteSearchBundle\Event\AfterSearchEvent;
use Oro\Bundle\WebsiteSearchBundle\Event\BeforeSearchEvent;
use Oro\Bundle\WebsiteSearchBundle\Resolver\QueryPlaceholderResolverInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

/**
 * Abstract implementation of search engine. Performs search operation for search index.
 */
abstract class AbstractEngine implements EngineInterface
{
    /** @var EventDispatcherInterface */
    private $eventDispatcher;

    /** @var QueryPlaceholderResolverInterface */
    private $queryPlaceholderResolver;

    /** @var AbstractSearchMappingProvider */
    protected $mappingProvider;

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
        $this->eventDispatcher->dispatch($event, BeforeSearchEvent::EVENT_NAME);

        $query = $event->getQuery();

        $this->queryPlaceholderResolver->replace($query);

        $result = $this->doSearch($query, $context);

        $afterEvent = new AfterSearchEvent($result, $query, $context);
        $this->eventDispatcher->dispatch($afterEvent, AfterSearchEvent::EVENT_NAME);

        return $result;
    }
}
