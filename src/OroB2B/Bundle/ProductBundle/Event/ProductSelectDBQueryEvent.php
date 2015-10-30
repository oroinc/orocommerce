<?php

namespace OroB2B\Bundle\ProductBundle\Event;

use Symfony\Component\EventDispatcher\Event;
use Symfony\Component\HttpFoundation\ParameterBag;
use Symfony\Component\HttpFoundation\Request;

use Doctrine\ORM\QueryBuilder;

class ProductSelectDBQueryEvent extends Event
{
    const NAME = 'orob2b_product.product_select.db.query';

    /** @var QueryBuilder */
    protected $queryBuilder;

    /** @var ParameterBag */
    protected $dataParameters;

    /** @var  Request */
    protected $request;

    /**
     * @param QueryBuilder $queryBuilder
     * @param ParameterBag $dataParameters
     * @param Request $request
     */
    public function __construct(QueryBuilder $queryBuilder, ParameterBag $dataParameters, Request $request = null)
    {
        $this->queryBuilder = $queryBuilder;
        $this->dataParameters = $dataParameters;
        $this->request = $request;
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

    /**
     * @param QueryBuilder $queryBuilder
     */
    public function setQueryBuilder($queryBuilder)
    {
        $this->queryBuilder = $queryBuilder;
    }

    /**
     * @return Request
     */
    public function getRequest()
    {
        return $this->request;
    }
}
