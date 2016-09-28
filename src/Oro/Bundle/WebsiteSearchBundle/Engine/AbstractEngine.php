<?php

namespace Oro\Bundle\WebsiteSearchBundle\Engine;

use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;
use Oro\Bundle\SearchBundle\Engine\EngineV2Interface;
use Oro\Bundle\SearchBundle\Query\Query;
use Oro\Bundle\SearchBundle\Query\Result;
use Oro\Bundle\WebsiteSearchBundle\Event\BeforeSearchEvent;
use Oro\Bundle\WebsiteSearchBundle\Resolver\QueryPlaceholderResolverInterface;

use Symfony\Component\EventDispatcher\EventDispatcherInterface;

abstract class AbstractEngine implements EngineV2Interface
{
    /**
     * @var EventDispatcherInterface
     */
    private $eventDispatcher;

    /**
     * @var QueryPlaceholderResolverInterface
     */
    private $queryPlaceholderResolver;

    /**
     * @var DoctrineHelper
     */
    protected $doctrineHelper;

    /**
     * @param EventDispatcherInterface $eventDispatcher
     * @param QueryPlaceholderResolverInterface $queryPlaceholderResolver
     * @param DoctrineHelper $doctrineHelper
     */
    public function __construct(
        EventDispatcherInterface $eventDispatcher,
        QueryPlaceholderResolverInterface $queryPlaceholderResolver,
        DoctrineHelper $doctrineHelper
    ) {
        $this->eventDispatcher = $eventDispatcher;
        $this->queryPlaceholderResolver = $queryPlaceholderResolver;
        $this->doctrineHelper = $doctrineHelper;
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
        $query = $this->queryPlaceholderResolver->replace($query);

        return $this->doSearch($query, $context);
    }
}
