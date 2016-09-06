<?php

namespace Oro\Bundle\WebsiteSearchBundle\Engine;

use Doctrine\Common\Collections\Expr\Comparison;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\QueryBuilder;
use Doctrine\Common\Collections\Expr\Expression;
use Doctrine\Common\Collections\Expr\CompositeExpression;

use Oro\Bundle\CatalogBundle\Entity\Category;
use Oro\Bundle\SearchBundle\Engine\EngineV2Interface;
use Oro\Bundle\SearchBundle\Query\Criteria\Criteria;
use Oro\Bundle\SearchBundle\Query\Query;
use Oro\Bundle\SearchBundle\Query\Result;

class ExampleMockEngine implements EngineV2Interface
{
    /**
     * @var EntityManager
     */
    private $entityManager;

    /**
     * @var array
     */
    private $selectFieldsMapping = [
        'sku' => 'product.sku',
        'shortDescription' => 'descriptions.text',
        'minimum_price' => 'product_price.value',
        'name' => 'names.string'
    ];

    /**
     * @param EntityManager $entityManager
     */
    public function __construct(EntityManager $entityManager)
    {
        $this->entityManager = $entityManager;
    }

    /**
     * @param Query $query
     * @param array $context
     * @return Result
     */
    public function search(Query $query, $context = [])
    {
        $selectedColumns = $query->getSelect();
        $queryBuilder = $this->prepareQueryBuilder($selectedColumns);

        $this->applyFiltersToQueryBuilder($queryBuilder, $query->getCriteria());

        $count = count($queryBuilder->getQuery()->getArrayResult());

        $this->applyPagination($query, $queryBuilder);
        $this->applyOrderBy($query, $queryBuilder);

        $results = $queryBuilder
            ->getQuery()
            ->getArrayResult();

        return new Result($query, $results, $count);
    }

    /**
     * @param array $selectedColumns
     * @return QueryBuilder
     */
    private function prepareQueryBuilder(array $selectedColumns)
    {
        $queryBuilder = $this->entityManager->getRepository(Category::class)->createQueryBuilder('category');

        $queryBuilder
            ->select('product.id as id')
            ->addSelect('category.id as cat_id')
            ->join('category.products', 'product')
            ->leftJoin('product.names', 'names')
            ->leftJoin('product.descriptions', 'descriptions')
            ->groupBy('product.id, category.id')
        ;

        foreach ($selectedColumns as $column) {
            $column = $this->getRawFieldName($column);

            if (isset($this->selectFieldsMapping[$column])) {
                $queryBuilder->addSelect($this->selectFieldsMapping[$column].' as '.$column);
                $queryBuilder->addGroupBy($column);
            }
        }

        return $queryBuilder;
    }

    /**
     * @param QueryBuilder $queryBuilder
     * @param Expression $expression
     */
    private function applyExpressionToQueryBuilder(QueryBuilder $queryBuilder, Expression $expression)
    {
        if ($expression instanceof CompositeExpression) {
            foreach ($expression->getExpressionList() as $expression) {
                $this->applyExpressionToQueryBuilder($queryBuilder, $expression);
            }

            return;
        }

        if ($expression instanceof Comparison) {
            $fieldName = $expression->getField();

            $fieldName = $this->getRawFieldName($fieldName);

            $value = $expression->getValue()->getValue();
            $operator = $expression->getOperator();

            if ($fieldName == 'all_text') {
                $value = strtolower($value);
                $value = "'%$value%'";

                $queryBuilder->andWhere(
                    $queryBuilder->expr()->orX(
                        $queryBuilder->expr()->like('lower(names.string)', $value),
                        $queryBuilder->expr()->like('lower(descriptions.text)', $value)
                    )
                );

                return;
            }

            if ($fieldName == 'cat_id') {
                if (is_array($value)) {
                    $queryBuilder->andWhere('category.id in (:catIds)');
                    $queryBuilder->setParameter('catIds', $value);

                    return;
                }

                $queryBuilder->andWhere('category.id = :catId');
                $queryBuilder->setParameter('catId', $value);

                return;
            }

            if ($operator == Comparison::CONTAINS) {
                $operator = 'like';
                $value = '%'.strtolower($value).'%';
            }

            if (isset($this->selectFieldsMapping[$fieldName])) {
                $fieldNameMapped = $this->selectFieldsMapping[$fieldName];

                $queryBuilder->andWhere('lower('.$fieldNameMapped.') '.$operator.' :p_'.$fieldName);
                $queryBuilder->setParameter('p_'.$fieldName, $value);

                return;
            }

            $paramName = $fieldName;
            $queryBuilder->andHaving($fieldName.' '.$operator.' :p_'.$paramName);
            $queryBuilder->setParameter('p_'.$paramName, $value);
        }
    }

    /**
     * @param Query $query
     * @param QueryBuilder $queryBuilder
     */
    private function applyPagination(Query $query, QueryBuilder $queryBuilder)
    {
        $criteria = $query->getCriteria();
        $offset = $criteria->getFirstResult();
        $limit = $criteria->getMaxResults();

        $queryBuilder->setMaxResults($limit);
        $queryBuilder->setFirstResult($offset);
    }

    /**
     * @param QueryBuilder $queryBuilder
     * @param Criteria $criteria
     */
    private function applyFiltersToQueryBuilder(QueryBuilder $queryBuilder, Criteria $criteria)
    {
        if (!$criteria->getWhereExpression()) {
            return;
        }

        $expression = $criteria->getWhereExpression();

        $this->applyExpressionToQueryBuilder($queryBuilder, $expression);
    }

    /**
     * @param Query $query
     * @param QueryBuilder $queryBuilder
     */
    private function applyOrderBy(Query $query, QueryBuilder $queryBuilder)
    {
        foreach ($query->getCriteria()->getOrderings() as $field => $ordering) {
            $field = $this->getRawFieldName($field);

            $queryBuilder->addOrderBy($field, $ordering);
        }
    }

    /**
     * @param $column
     * @return array|mixed
     */
    private function getRawFieldName($column)
    {
        $column = explode('.', $column);
        $column = array_pop($column);
        $column = str_replace('_LOCALIZATION_ID', '', $column);

        return $column;
    }
}
