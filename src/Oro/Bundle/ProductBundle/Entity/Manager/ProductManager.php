<?php

namespace Oro\Bundle\ProductBundle\Entity\Manager;

use Doctrine\ORM\QueryBuilder;

use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\HttpFoundation\ParameterBag;

use Oro\Bundle\ProductBundle\Event\ProductSelectDBQueryEvent;

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
}
