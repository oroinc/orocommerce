<?php

namespace Oro\Bundle\WebsiteSearchBundle\Placeholder;

use Doctrine\Common\Collections\Expr\Comparison;
use Doctrine\Common\Collections\Expr\CompositeExpression;
use Doctrine\Common\Collections\Expr\ExpressionVisitor;
use Doctrine\Common\Collections\Expr\Value;

class PlaceholderExpressionVisitor extends ExpressionVisitor
{
    /**
     * @var PlaceholderInterface
     */
    private $placeholder;

    /**
     * @param PlaceholderInterface $placeholder
     */
    public function __construct(PlaceholderInterface $placeholder)
    {
        $this->placeholder = $placeholder;
    }

    /**
     * {@inheritdoc}
     */
    public function walkComparison(Comparison $comparison)
    {
        return new Comparison(
            $this->placeholder->replaceDefault($comparison->getField()),
            $comparison->getOperator(),
            $this->walkValue($comparison->getValue())
        );
    }

    /**
     * {@inheritdoc}
     */
    public function walkValue(Value $value)
    {
        return $value;
    }

    /**
     * {@inheritdoc}
     */
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
