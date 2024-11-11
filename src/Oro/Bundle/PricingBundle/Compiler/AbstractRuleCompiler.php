<?php

namespace Oro\Bundle\PricingBundle\Compiler;

use Doctrine\ORM\QueryBuilder;
use Oro\Bundle\PricingBundle\Cache\RuleCache;
use Oro\Bundle\ProductBundle\Expression\NodeToQueryDesignerConverter;
use Oro\Bundle\ProductBundle\Expression\QueryConverter;
use Oro\Component\Expression\ExpressionParser;
use Oro\Component\Expression\Node;
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
     * Add check that denominator is not equal to 0 for all denominators.
     */
    protected function addDivisionSafeguardConditions(QueryBuilder $qb, string $expression, array &$params): void
    {
        $node = $this->expressionParser->parse($expression);
        if (!$node) {
            return;
        }

        $denominatorNodes = $this->safeguardDenominators($node);
        if (!$denominatorNodes) {
            return;
        }

        $qb->andWhere(
            $this->expressionBuilder->convert(
                $denominatorNodes,
                $qb->expr(),
                $params,
                $this->queryConverter->getTableAliasByColumn()
            )
        );
    }

    protected function safeguardDenominators(Node\NodeInterface $node): ?Node\NodeInterface
    {
        $resultNode = null;
        foreach ($this->getDenominators($node) as $divisor) {
            $notZeroNode = new Node\BinaryNode(
                $divisor,
                new Node\ValueNode(0.0),
                '!='
            );
            if ($resultNode) {
                $resultNode = new Node\BinaryNode(
                    $resultNode,
                    $notZeroNode,
                    'and'
                );
            } else {
                $resultNode = $notZeroNode;
            }
        }

        return $resultNode;
    }

    protected function getDenominators(Node\NodeInterface $node): \Generator
    {
        if ($node instanceof Node\BinaryNode) {
            if ($node->getOperation() === '/' || $node->getOperation() === '%') {
                yield $node->getRight();
            } else {
                yield from $this->getDenominators($node->getLeft());
                yield from $this->getDenominators($node->getRight());
            }
        }
    }

    /**
     * @return array
     */
    abstract public function getOrderedFields();
}
