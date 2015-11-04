<?php

namespace OroB2B\Bundle\ProductBundle\Entity\Manager;

use Doctrine\ORM\QueryBuilder;

use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\HttpFoundation\ParameterBag;

use OroB2B\Bundle\ProductBundle\Event\ProductSelectDBQueryEvent;

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
    public function restrictQueryBuilderByProductVisibility(
        QueryBuilder $queryBuilder,
        array $dataParameters
    ) {
        $this->eventDispatcher->dispatch(
            ProductSelectDBQueryEvent::NAME,
            new ProductSelectDBQueryEvent($queryBuilder, new ParameterBag($dataParameters))
        );

        return $queryBuilder;
    }
}
