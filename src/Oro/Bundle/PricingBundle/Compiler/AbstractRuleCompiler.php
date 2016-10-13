<?php

namespace Oro\Bundle\PricingBundle\Compiler;

use Doctrine\Common\Cache\Cache;
use Doctrine\ORM\QueryBuilder;
use Oro\Bundle\PricingBundle\Query\PriceListExpressionQueryConverter;
use Oro\Bundle\ProductBundle\Expression\NodeToQueryDesignerConverter;
use Oro\Component\Expression\ExpressionParser;
use Oro\Component\Expression\Preprocessor\ExpressionPreprocessorInterface;
use Oro\Component\Expression\QueryExpressionBuilder;

abstract class AbstractRuleCompiler
{
    /**
     * @var ExpressionParser
     */
    protected $expressionParser;

    /**
     * @var ExpressionPreprocessorInterface
     */
    protected $expressionPreprocessor;

    /**
     * @var NodeToQueryDesignerConverter
     */
    protected $nodeConverter;

    /**
     * @var PriceListExpressionQueryConverter
     */
    protected $queryConverter;

    /**
     * @var QueryExpressionBuilder
     */
    protected $expressionBuilder;

    /**
     * @var Cache
     */
    protected $cache;

    /**
     * @param ExpressionParser $parser
     * @param ExpressionPreprocessorInterface $preprocessor
     * @param NodeToQueryDesignerConverter $nodeConverter
     * @param PriceListExpressionQueryConverter $queryConverter
     * @param QueryExpressionBuilder $expressionBuilder
     * @param Cache $cache
     */
    public function __construct(
        ExpressionParser $parser,
        ExpressionPreprocessorInterface $preprocessor,
        NodeToQueryDesignerConverter $nodeConverter,
        PriceListExpressionQueryConverter $queryConverter,
        QueryExpressionBuilder $expressionBuilder,
        Cache $cache
    ) {
        $this->expressionParser = $parser;
        $this->expressionPreprocessor = $preprocessor;
        $this->nodeConverter = $nodeConverter;
        $this->queryConverter = $queryConverter;
        $this->expressionBuilder = $expressionBuilder;
        $this->cache = $cache;
    }

    /**
     * @param QueryBuilder $qb
     * @param array $params
     */
    protected function applyParameters(QueryBuilder $qb, array $params)
    {
        foreach ($params as $key => $value) {
            $qb->setParameter($key, $value);
        }
    }

    /**
     * @param QueryBuilder $qb
     * @param array $fieldsMap
     */
    protected function addSelectInOrder(QueryBuilder $qb, array $fieldsMap)
    {
        $select = [];
        foreach ($this->getOrderedFields() as $fieldName) {
            $select[] = $fieldsMap[$fieldName] . ' ' . $fieldName;
        }
        $qb->select($select);
    }

    /**
     * @return array
     */
    abstract public function getOrderedFields();
}
