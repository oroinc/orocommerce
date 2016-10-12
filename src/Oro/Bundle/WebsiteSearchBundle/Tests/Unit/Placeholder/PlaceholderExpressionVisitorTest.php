<?php

namespace Oro\Bundle\WebsiteSearchBundle\Tests\Unit\Placeholder;

use Doctrine\Common\Collections\Expr\CompositeExpression;
use Doctrine\Common\Collections\Expr\Value;

use Oro\Bundle\SearchBundle\Query\Criteria\Comparison;
use Oro\Bundle\WebsiteSearchBundle\Placeholder\PlaceholderExpressionVisitor;
use Oro\Bundle\WebsiteSearchBundle\Placeholder\PlaceholderInterface;

class PlaceholderExpressionVisitorTest extends \PHPUnit_Framework_TestCase
{
    /** @var PlaceholderInterface|\PHPUnit_Framework_MockObject_MockObject */
    private $placeholder;

    /** @var PlaceholderExpressionVisitor */
    private $visitor;

    protected function setUp()
    {
        $this->placeholder = $this->getMock(PlaceholderInterface::class);
        $this->visitor = new PlaceholderExpressionVisitor($this->placeholder);
    }

    protected function tearDown()
    {
        unset($this->visitor, $this->placeholder);
    }

    public function testWalkValue()
    {
        $value = new Value('test_value');
        $result = $this->visitor->walkValue($value);
        $this->assertSame($value, $result);
    }

    public function testWalkComparison()
    {
        $expr = new Comparison("field_name_NAME_ID", "=", "value");

        $this->placeholder->expects($this->once())
            ->method('replaceDefault')
            ->with("field_name_NAME_ID")
            ->willReturn('field_name_1');

        $result = $this->visitor->walkComparison($expr);

        $this->assertEquals('field_name_1', $result->getField());
        $this->assertEquals('=', $result->getOperator());
        $this->assertEquals('value', $result->getValue()->getValue());
    }

    public function testWalkCompositeExpression()
    {
        $exprs = new CompositeExpression(
            CompositeExpression::TYPE_AND,
            [
                new Comparison('field_name_NAME_ID', '=', 'value'),
                new Comparison('field_name_TEXT_ID', '=', 'value'),
            ]
        );

        $result = $this->visitor->walkCompositeExpression($exprs);

        $this->assertInstanceOf(CompositeExpression::class, $result);
        $this->assertCount(2, $result->getExpressionList());
    }
}
