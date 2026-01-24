<?php

namespace Oro\Bundle\WebsiteSearchBundle\Placeholder;

use Doctrine\Common\Collections\Expr\Comparison;
use Doctrine\Common\Collections\Expr\CompositeExpression;
use Doctrine\Common\Collections\Expr\ExpressionVisitor;
use Doctrine\Common\Collections\Expr\Value;

/**
 * Traverses Doctrine expression trees and replaces placeholders in field names with their default values.
 *
 * This visitor implements the Visitor pattern to walk through Doctrine Criteria expression trees
 * (comparisons, composite expressions) and applies placeholder replacement to field names using
 * the configured {@see PlaceholderInterface}. This is essential for resolving dynamic field names
 * in search queries, transforming field names like "price_WEBSITE_ID_CURRENCY" into concrete names
 * like "price_1_USD" based on the current context. The visitor is used by {@see QueryPlaceholderResolver}
 * to process `WHERE` clauses in search queries.
 */
class PlaceholderExpressionVisitor extends ExpressionVisitor
{
    /**
     * @var PlaceholderInterface
     */
    private $placeholder;

    public function __construct(PlaceholderInterface $placeholder)
    {
        $this->placeholder = $placeholder;
    }

    #[\Override]
    public function walkComparison(Comparison $comparison)
    {
        return new Comparison(
            $this->placeholder->replaceDefault($comparison->getField()),
            $comparison->getOperator(),
            $this->walkValue($comparison->getValue())
        );
    }

    #[\Override]
    public function walkValue(Value $value)
    {
        return $value;
    }

    #[\Override]
    public function walkCompositeExpression(CompositeExpression $expr)
    {
        return new CompositeExpression(
            $expr->getType(),
            array_map(
                function ($child) {
                    return $this->dispatch($child);
                },
                $expr->getExpressionList()
            )
        );
    }
}
