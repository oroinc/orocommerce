<?php

namespace Oro\Bundle\WebsiteSearchBundle\Engine;

use Doctrine\Common\Collections\Expr\Comparison;
use Doctrine\Common\Collections\Expr\CompositeExpression;
use Doctrine\Common\Collections\Expr\ExpressionVisitor;
use Doctrine\Common\Collections\Expr\Value;

use Oro\Bundle\SearchBundle\Query\Criteria\Criteria;

class ExampleExpressionVisitor extends ExpressionVisitor
{
    /** @var array */
    protected $data;

    /**
     * @param $row
     */
    public function __construct($row)
    {
        $this->data = $row;
    }

    /**
     * @param Comparison $comparison
     * @return bool|int|mixed
     */
    public function walkComparison(Comparison $comparison)
    {
        $field = $comparison->getField();

        list ($type, $value) = Criteria::explodeFieldTypeName($field);

        if (!isset($this->data[$value])) {
            return true;
        }

        if ($comparison->getOperator() === Comparison::CONTAINS) {
            return strpos($this->data[$value], $comparison->getValue()->getValue()) !== false;
        }
        if ($comparison->getOperator() === Comparison::EQ) {
            return strcmp($this->data[$value], $comparison->getValue()->getValue()) === 0;
        }
        if ($comparison->getOperator() === Comparison::IN) {
            return in_array($this->data[$value], $comparison->getValue()->getValue(), true);
        }

        return true;
    }

    /**
     * @param Value $value
     * @return bool|mixed
     */
    public function walkValue(Value $value)
    {
        return true;
    }

    /**
     * @param CompositeExpression $expr
     * @return bool|mixed
     */
    public function walkCompositeExpression(CompositeExpression $expr)
    {
        $list = $expr->getExpressionList();

        foreach ($list as $expression) {
            $partial = $expression->visit($this);
            if (false === $partial) {
                return false;
            }
        }
        return true;
    }
}
