<?php

namespace Oro\Bundle\ProductBundle\Entity\Manager;

use Doctrine\ORM\QueryBuilder;

use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\HttpFoundation\ParameterBag;

use Oro\Bundle\SearchBundle\Query\SearchQueryInterface;
use Oro\Bundle\ProductBundle\Event\ProductDBQueryRestrictionEvent;
use Oro\Bundle\ProductBundle\Event\ProductSearchQueryRestrictionEvent;

class ProductManager
{
    /**
     * @var  EventDispatcherInterface
     */
    protected $eventDispatcher;

    /**
     * @param EventDispatcherInterface $eventDispatcher
     */
    public function __construct(EventDispatcherInterface $eventDispatcher)
    {
        $this->eventDispatcher = $eventDispatcher;
    }

    /**
     * @param QueryBuilder $queryBuilder
     * @param array        $dataParameters
     * @return QueryBuilder
     */
    public function restrictQueryBuilder(
        QueryBuilder $queryBuilder,
        array $dataParameters
    ) {
        $event = new ProductDBQueryRestrictionEvent($queryBuilder, new ParameterBag($dataParameters));
        $this->eventDispatcher->dispatch(ProductDBQueryRestrictionEvent::NAME, $event);

        return $event->getQueryBuilder();
    }

    /**
     * @param SearchQueryInterface $query
     * @return SearchQueryInterface
     */
    public function restrictSearchQuery(SearchQueryInterface $query)
    {
        $productSearchQueryEvent = new ProductSearchQueryRestrictionEvent($query);
        $this->eventDispatcher->dispatch(ProductSearchQueryRestrictionEvent::NAME, $productSearchQueryEvent);

        return $productSearchQueryEvent->getQuery();
    }
}
