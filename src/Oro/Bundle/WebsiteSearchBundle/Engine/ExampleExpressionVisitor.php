<?php

namespace Oro\Bundle\WebsiteSearchBundle\Engine;

use Doctrine\Common\Collections\Expr\Comparison;
use Doctrine\Common\Collections\Expr\CompositeExpression;
use Doctrine\Common\Collections\Expr\ExpressionVisitor;
use Doctrine\Common\Collections\Expr\Value;

class ExampleExpressionVisitor extends ExpressionVisitor
{
    /**
     * @var array
     */
    protected $data;

    /**
     * ExampleExpressionVisitor constructor.
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
        switch ($comparison->getField()) {
            case 'text.sku':
                if ($comparison->getOperator() === Comparison::CONTAINS) {
                    return strpos($this->data['sku'], $comparison->getValue()->getValue()) !== false;
                }
                if ($comparison->getOperator() === Comparison::EQ) {
                    return strcmp($this->data['sku'], $comparison->getValue()->getValue()) === 0;
                }
                break;
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
        return true;
    }
}
