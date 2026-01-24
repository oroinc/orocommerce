<?php

namespace Oro\Component\Expression\QueryExpressionConverter;

use Doctrine\ORM\Query\Expr;
use Oro\Component\Expression\Node\NodeInterface;
use Oro\Component\Expression\Node\ValueNode;

/**
 * Converts literal value nodes to Doctrine ORM query parameters.
 *
 * This converter transforms constant values from expression nodes into parameterized query values,
 * automatically handling numeric values that can be used directly and non-numeric values that require
 * parameterization. It manages parameter naming and counting to ensure unique parameter identifiers.
 */
class ValueNodeConverter implements QueryExpressionConverterInterface
{
    const PARAMETER_PREFIX = '_vn';

    /**
     * @var int
     */
    protected $paramCount = 0;

    #[\Override]
    public function convert(NodeInterface $node, Expr $expr, array &$params, array $aliasMapping = [])
    {
        if ($node instanceof ValueNode) {
            $value = $node->getValue();
            if (!is_numeric($value) || array_key_exists(self::REQUIRE_PARAMETRIZATION, $params)) {
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
