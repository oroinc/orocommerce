<?php

namespace Oro\Bundle\WebsiteSearchBundle\Engine;

use Symfony\Component\EventDispatcher\EventDispatcherInterface;

use Oro\Bundle\SearchBundle\Engine\EngineV2Interface;
use Oro\Bundle\SearchBundle\Query\Query;
use Oro\Bundle\WebsiteSearchBundle\Event\BeforeSearchEvent;
use Oro\Bundle\WebsiteSearchBundle\Resolver\PlaceholderResolver;

abstract class AbstractWebsiteSearchEngine implements EngineV2Interface
{
    /**
     * @var EventDispatcherInterface
     */
    private $eventDispatcher;

    /**
     * @var PlaceholderResolver
     */
    private $placeholderResolver;

    /**
     * @param EventDispatcherInterface $eventDispatcher
     * @param PlaceholderResolver $placeholderResolver
     */
    public function __construct(EventDispatcherInterface $eventDispatcher, PlaceholderResolver $placeholderResolver)
    {
        $this->eventDispatcher = $eventDispatcher;
        $this->placeholderResolver = $placeholderResolver;
    }

    /**
     * @param Query $query
     * @param array $context
     * @return mixed
     */
    abstract public function doSearch(Query $query, $context = []);

    /**
     * {@inheritdoc}
     */
    public function search(Query $query, $context = [])
    {
        $event = new BeforeSearchEvent($query, $context);
        $this->eventDispatcher->dispatch(BeforeSearchEvent::EVENT_NAME, $event);

        $query = $event->getQuery();
        $query = $this->placeholderResolver->replace($query, $context);

        return $this->doSearch($query, $context);
    }
}
