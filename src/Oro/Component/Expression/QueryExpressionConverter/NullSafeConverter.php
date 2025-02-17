<?php

namespace Oro\Component\Expression\QueryExpressionConverter;

use Doctrine\ORM\Query\Expr;
use Oro\Component\Expression\Node\BinaryNode;
use Oro\Component\Expression\Node\NodeInterface;
use Oro\Component\Expression\Node\ValueNode;

/**
 * Converter that ensures safe handling of null comparisons in SQL expressions.
 *
 * Example:
 *  - Expression: 'user.email == null' will be converted to 'user.email IS NULL'
 *  - Expression: 'user.email != null' will be converted to 'user.email IS NOT NULL'
 */
class NullSafeConverter implements QueryExpressionConverterInterface, ConverterAwareInterface
{
    /** @var QueryExpressionConverterInterface|null */
    private ?QueryExpressionConverterInterface $converter = null;

    #[\Override]
    public function setConverter(QueryExpressionConverterInterface $converter): void
    {
        $this->converter = $converter;
    }

    #[\Override]
    public function convert(NodeInterface $node, Expr $expr, array &$params, array $aliasMapping = []): ?Expr\Comparison
    {
        if (!$node instanceof BinaryNode) {
            return null;
        }

        if ($this->converter === null) {
            throw new \LogicException('Converter is not set.');
        }

        $left = $this->extractValueFromNode($node->getLeft());
        $right = $this->extractValueFromNode($node->getRight());

        if ($this->isNullComparisonRequired($left, $right)) {
            $resolvedNode = $this->converter->convert($left ?? $right, $expr, $params, $aliasMapping);

            return $this->createNullComparison($resolvedNode, $node->getOperation());
        }

        return null;
    }

    private function extractValueFromNode(NodeInterface $node): mixed
    {
        return $node instanceof ValueNode ? $node->getValue() : $node;
    }

    private function isNullComparisonRequired(mixed $left, mixed $right): bool
    {
        return ($left instanceof NodeInterface && $right === null) ||
            ($right instanceof NodeInterface && $left === null);
    }

    private function createNullComparison(string $resolvedNode, string $operator): Expr\Comparison
    {
        $nullOperator = $operator === '!=' ? 'IS NOT' : 'IS';

        return new Expr\Comparison($resolvedNode, $nullOperator, new Expr\Literal('NULL'));
    }
}
