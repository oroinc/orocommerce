<?php

namespace Oro\Bundle\PricingBundle\Expression\QueryExpressionConverter;

use Doctrine\ORM\Query\Expr;
use Oro\Bundle\PricingBundle\Expression\NodeInterface;
use Oro\Bundle\PricingBundle\Expression\ValueNode;

class ValueNodeConverter implements QueryExpressionConverterInterface
{
    const PARAMETER_PREFIX = '_vn';

    /**
     * @var int
     */
    protected $paramCount = 0;

    /**
     * {@inheritdoc}
     */
    public function convert(NodeInterface $node, Expr $expr, array &$params, array $aliasMapping = [])
    {
        if ($node instanceof ValueNode) {
            $value = $node->getValue();
            if (!is_numeric($value)) {
                $param = self::PARAMETER_PREFIX . $this->paramCount;
                $params[$param] = $value;
                $value = ':' . $param;
                $this->paramCount++;
            }

            return $value;
        }

        return null;
    }
}
