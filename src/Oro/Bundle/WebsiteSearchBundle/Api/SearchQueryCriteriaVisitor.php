<?php

namespace Oro\Bundle\WebsiteSearchBundle\Api;

use Doctrine\Common\Collections\Expr\Comparison;
use Doctrine\Common\Collections\Expr\CompositeExpression;
use Doctrine\Common\Collections\Expr\Expression;
use Doctrine\Common\Collections\Expr\ExpressionVisitor;
use Doctrine\Common\Collections\Expr\Value;
use Oro\Bundle\ApiBundle\Exception\InvalidFilterException;
use Oro\Bundle\SearchBundle\Query\Criteria\Comparison as SearchComparison;
use Oro\Bundle\WebsiteSearchBundle\Placeholder\EnumIdPlaceholder;

/**
 * Prepares a search criteria created by SearchQueryFilter:
 * * updates enum related search query expressions
 */
class SearchQueryCriteriaVisitor extends ExpressionVisitor
{
    /**
     * {@inheritdoc}
     */
    public function walkComparison(Comparison $comparison)
    {
        $field = $comparison->getField();
        if (str_contains($field, '_enum.' . EnumIdPlaceholder::NAME)) {
            return $this->buildEnumComparison($comparison);
        }

        return $comparison;
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

    private function buildEnumComparison(Comparison $comparison): Expression
    {
        switch ($comparison->getOperator()) {
            case SearchComparison::EQ:
                return $this->createEnumComparison(
                    $comparison->getField(),
                    SearchComparison::EXISTS,
                    $comparison->getValue()->getValue()
                );
            case SearchComparison::NEQ:
                return $this->createEnumComparison(
                    $comparison->getField(),
                    SearchComparison::NOT_EXISTS,
                    $comparison->getValue()->getValue()
                );
            case SearchComparison::IN:
                return new CompositeExpression(
                    CompositeExpression::TYPE_OR,
                    array_map(
                        function ($value) use ($comparison) {
                            return $this->createEnumComparison(
                                $comparison->getField(),
                                SearchComparison::EXISTS,
                                $value
                            );
                        },
                        $comparison->getValue()->getValue()
                    )
                );
            case SearchComparison::NIN:
                return new CompositeExpression(
                    CompositeExpression::TYPE_AND,
                    array_map(
                        function ($value) use ($comparison) {
                            return $this->createEnumComparison(
                                $comparison->getField(),
                                SearchComparison::NOT_EXISTS,
                                $value
                            );
                        },
                        $comparison->getValue()->getValue()
                    )
                );
        }

        throw new InvalidFilterException(sprintf(
            'The operator "%s" is not supported for the field "%s". Supported operators: =, !=, in, !in.',
            $comparison->getOperator(),
            $this->normalizeEnumFieldName($comparison->getField())
        ));
    }

    private function createEnumComparison(string $field, string $operator, string $value): Comparison
    {
        return new SearchComparison(
            str_replace(EnumIdPlaceholder::NAME, $value, $field),
            $operator,
            new Value(null)
        );
    }

    private function normalizeEnumFieldName(string $fieldName): string
    {
        $pos = strpos($fieldName, '.');
        if (false !== $pos) {
            $fieldName = substr($fieldName, $pos + 1);
        }
        $pos = strpos($fieldName, '_enum.' . EnumIdPlaceholder::NAME);
        if (false !== $pos) {
            $fieldName = substr($fieldName, 0, $pos);
        }

        return $fieldName;
    }
}
