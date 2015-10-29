<?php

namespace OroB2B\Bundle\ProductBundle\Event;

use Doctrine\ORM\QueryBuilder;

use Symfony\Component\EventDispatcher\Event;

class ProductSelectDBQueryEvent extends Event
{
    const NAME = 'orob2b_product.product_select.db.query';

    /**
     * @var QueryBuilder
     */
    protected $queryBuilder;

    /**
     * @var array
     */
    protected $dataParameters;

    /**
     * @param QueryBuilder $queryBuilder
     * @param array $dataParameters
     */
    public function __construct(QueryBuilder $queryBuilder, array $dataParameters)
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
     * @return array
     */
    public function getDataParameters()
    {
        return $this->dataParameters;
    }
}
