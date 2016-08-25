<?php

namespace Oro\Bundle\WebsiteSearchBundle\Engine;

use Symfony\Component\EventDispatcher\EventDispatcherInterface;

use Oro\Bundle\SearchBundle\Engine\EngineV2Interface;
use Oro\Bundle\SearchBundle\Query\Query;
use Oro\Bundle\SearchBundle\Query\Result;
use Oro\Bundle\WebsiteSearchBundle\Event\BeforeSearchEvent;
use Oro\Bundle\WebsiteSearchBundle\Resolver\QueryPlaceholderResolver;

abstract class AbstractWebsiteSearchEngine implements EngineV2Interface
{
    /**
     * @var EventDispatcherInterface
     */
    private $eventDispatcher;

    /**
     * @var QueryPlaceholderResolver
     */
    private $queryPlaceholderResolver;

    /**
     * @param EventDispatcherInterface $eventDispatcher
     * @param QueryPlaceholderResolver $queryPlaceholderResolver
     */
    public function __construct(
        EventDispatcherInterface $eventDispatcher,
        QueryPlaceholderResolver $queryPlaceholderResolver
    ) {
        $this->eventDispatcher = $eventDispatcher;
        $this->queryPlaceholderResolver = $queryPlaceholderResolver;
    }

    /**
     * @param Query $query
     * @param array $context
     * @return array
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
        $query = $this->queryPlaceholderResolver->replace($query, $context);

        $result = $this->doSearch($query, $context);

        return new Result($query, $result['results'], $result['records_count']);
    }
}
