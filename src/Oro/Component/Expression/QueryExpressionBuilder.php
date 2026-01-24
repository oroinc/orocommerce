<?php

namespace Oro\Component\Expression;

use Doctrine\ORM\Query\Expr;
use Oro\Component\Expression\Node\NodeInterface;
use Oro\Component\Expression\QueryExpressionConverter\ConverterAwareInterface;
use Oro\Component\Expression\QueryExpressionConverter\QueryExpressionConverterInterface;
use Oro\Component\PhpUtils\ArrayUtil;

/**
 * Orchestrates the conversion of expression nodes to Doctrine ORM query expressions.
 *
 * This builder manages a collection of specialized converters for different node types and
 * delegates node conversion to the appropriate converter based on node type. Converters are
 * registered with sort orders to control the order in which they are consulted, allowing for
 * flexible and extensible expression-to-query conversion.
 */
class QueryExpressionBuilder implements QueryExpressionConverterInterface
{
    const CONVERTER = 'converter';
    const SORT_ORDER = 'sort_order';

    /**
     * @var array
     */
    protected $registeredConverters = [];

    /**
     * @var array|QueryExpressionConverterInterface[]|null
     */
    protected $sortedConverters;

    /**
     * @param QueryExpressionConverterInterface $converter
     * @param int $sortOrder
     */
    public function registerConverter(QueryExpressionConverterInterface $converter, $sortOrder = 0)
    {
        if ($converter instanceof ConverterAwareInterface) {
            $converter->setConverter($this);
        }

        $this->registeredConverters[] = [
            self::CONVERTER => $converter,
            self::SORT_ORDER => $sortOrder
        ];
        $this->sortedConverters = null;
    }

    /**
     * @return array|QueryExpressionConverterInterface[]
     */
    protected function getSortedConverters()
    {
        if (null === $this->sortedConverters) {
            ArrayUtil::sortBy($this->registeredConverters, true, self::SORT_ORDER);
            $this->sortedConverters = array_column($this->registeredConverters, self::CONVERTER);
        }

        return $this->sortedConverters;
    }

    #[\Override]
    public function convert(NodeInterface $node, Expr $expr, array &$params, array $aliasMapping = [])
    {
        $convertedExpression = null;

        foreach ($this->getSortedConverters() as $converter) {
            $convertedExpression = $converter->convert($node, $expr, $params, $aliasMapping);
            if ($convertedExpression !== null) {
                break;
            }
        }

        if ($convertedExpression === null) {
            throw new \InvalidArgumentException(sprintf('Unsupported node type %s', get_class($node)));
        }

        return $convertedExpression;
    }
}
