<?php

namespace Oro\Bundle\ProductBundle\Entity\Manager;

use Doctrine\ORM\QueryBuilder;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\ProductBundle\Event\ProductSearchRestrictionEvent;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\HttpFoundation\ParameterBag;

use Oro\Bundle\ProductBundle\Event\ProductSelectDBQueryEvent;
use Oro\Bundle\SearchBundle\Query\Query as SearchQuery;

class ProductManager
{
    /** @var  EventDispatcherInterface */
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
     * @param array $dataParameters
     * @return QueryBuilder
     */
    public function restrictQueryBuilder(
        QueryBuilder $queryBuilder,
        array $dataParameters
    ) {
        $event = new ProductSelectDBQueryEvent($queryBuilder, new ParameterBag($dataParameters));
        $this->eventDispatcher->dispatch(ProductSelectDBQueryEvent::NAME, $event);

        return $event->getQueryBuilder();
    }

    /**
     * @param SearchQuery $query
     * @return SearchQuery
     */
    public function restrictSearchEngineQuery(SearchQuery $query)
    {
        $productSearchRestrictionEvent = new ProductSearchRestrictionEvent($query);
        $this->eventDispatcher->dispatch(ProductSearchRestrictionEvent::NAME, $productSearchRestrictionEvent);

        return $productSearchRestrictionEvent->getQuery();
    }
}
