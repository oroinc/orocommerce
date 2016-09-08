<?php

namespace Oro\Bundle\PricingBundle\Expression;

use Doctrine\ORM\Query\Expr;
use Oro\Bundle\PricingBundle\Expression\QueryExpressionConverter\ConverterAwareInterface;
use Oro\Bundle\PricingBundle\Expression\QueryExpressionConverter\QueryExpressionConverterInterface;
use Oro\Component\PhpUtils\ArrayUtil;

class QueryExpressionBuilder implements QueryExpressionConverterInterface
{
    const CONVERTER = 'converter';
    const SORT_ORDER = 'sort_order';

    /**
     * @var array
     */
    protected $registeredConverters = [];

    /**
     * @var array|null
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

    /**
     * {@inheritdoc}
     */
    public function convert(NodeInterface $node, Expr $expr, array &$params, array $aliasMapping = [])
    {
        $convertedExpression = null;

        foreach ($this->getSortedConverters() as $converter) {
            $convertedExpression = $converter->convert($node, $expr, $params, $aliasMapping);
            if ($convertedExpression !== null) {
                break;
            }
        }

        if (!$convertedExpression) {
            throw new \InvalidArgumentException(sprintf('Unsupported node type %s', get_class($node)));
        }

        return $convertedExpression;
    }
}
