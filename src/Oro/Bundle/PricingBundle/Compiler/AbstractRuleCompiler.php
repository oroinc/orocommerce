<?php

namespace Oro\Bundle\PricingBundle\Compiler;

use Doctrine\ORM\QueryBuilder;
use Oro\Bundle\PricingBundle\Cache\RuleCache;
use Oro\Bundle\ProductBundle\Expression\NodeToQueryDesignerConverter;
use Oro\Bundle\ProductBundle\Expression\QueryConverter;
use Oro\Component\Expression\ExpressionParser;
use Oro\Component\Expression\Preprocessor\ExpressionPreprocessorInterface;
use Oro\Component\Expression\QueryExpressionBuilder;

/**
 * Abstract class for price rule compilers
 */
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
     * @var QueryConverter
     */
    protected $queryConverter;

    /**
     * @var QueryExpressionBuilder
     */
    protected $expressionBuilder;

    /**
     * @var RuleCache
     */
    protected $cache;

    public function __construct(
        ExpressionParser $parser,
        ExpressionPreprocessorInterface $preprocessor,
        NodeToQueryDesignerConverter $nodeConverter,
        QueryConverter $queryConverter,
        QueryExpressionBuilder $expressionBuilder,
        RuleCache $cache
    ) {
        $this->expressionParser = $parser;
        $this->expressionPreprocessor = $preprocessor;
        $this->nodeConverter = $nodeConverter;
        $this->queryConverter = $queryConverter;
        $this->expressionBuilder = $expressionBuilder;
        $this->cache = $cache;
    }

    protected function applyParameters(QueryBuilder $qb, array $params)
    {
        foreach ($params as $key => $value) {
            $qb->setParameter($key, $value);
        }
    }

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
