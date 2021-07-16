<?php

namespace Oro\Bundle\ProductBundle\Event;

use Doctrine\ORM\QueryBuilder;
use Symfony\Component\HttpFoundation\ParameterBag;
use Symfony\Contracts\EventDispatcher\Event;

class ProductDBQueryRestrictionEvent extends Event
{
    const NAME = 'oro_product.product_db_query.restriction';

    /** @var QueryBuilder */
    protected $queryBuilder;

    /** @var ParameterBag */
    protected $dataParameters;

    public function __construct(QueryBuilder $queryBuilder, ParameterBag $dataParameters)
    {
        $this->queryBuilder = $queryBuilder;
        $this->dataParameters = $dataParameters;
    }

    /**
     * @return QueryBuilder
     */
    public function getQueryBuilder()
    {
        return $this->queryBuilder;
    }

    /**
     * @return ParameterBag
     */
    public function getDataParameters()
    {
        return $this->dataParameters;
    }

    public function setQueryBuilder(QueryBuilder $queryBuilder)
    {
        $this->queryBuilder = $queryBuilder;
    }
}
