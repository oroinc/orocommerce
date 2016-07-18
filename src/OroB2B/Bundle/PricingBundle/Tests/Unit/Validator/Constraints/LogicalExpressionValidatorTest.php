<?php

namespace OroB2B\Bundle\PricingBundle\Tests\Unit\Validator\Constraints;

use Symfony\Component\Validator\Context\ExecutionContextInterface;

use OroB2B\Bundle\PricingBundle\Expression\ExpressionParser;
use OroB2B\Bundle\PricingBundle\Expression\NodeInterface;
use OroB2B\Bundle\PricingBundle\Validator\Constraints\LogicalExpression;
use OroB2B\Bundle\PricingBundle\Validator\Constraints\LogicalExpressionValidator;

class LogicalExpressionValidatorTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var ExpressionParser|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $expressionParser;

    /**
     * @var LogicalExpressionValidator
     */
    protected $validator;

    protected function setUp()
    {
        $this->expressionParser = $this->getMockBuilder(ExpressionParser::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->validator = new LogicalExpressionValidator($this->expressionParser);
    }

    public function testValidateValid()
    {
        /** @var LogicalExpression|\PHPUnit_Framework_MockObject_MockObject $constraint */
        $constraint = $this->getMockBuilder(LogicalExpression::class)
            ->disableOriginalConstructor()
            ->getMock();
        /** @var ExecutionContextInterface|\PHPUnit_Framework_MockObject_MockObject $context */
        $context = $this->getMock(ExecutionContextInterface::class);
        $context->expects($this->never())
            ->method($this->anything());
        $this->validator->initialize($context);

        $value = 'test > 1';
        $node = $this->getMock(NodeInterface::class);
        $node->expects($this->once())
            ->method('isBoolean')
            ->willReturn(true);
        $this->expressionParser->expects($this->once())
            ->method('parse')
            ->with($value)
            ->willReturn($node);

        $this->validator->validate($value, $constraint);
    }

    public function testValidateInvalid()
    {
        /** @var LogicalExpression|\PHPUnit_Framework_MockObject_MockObject $constraint */
        $constraint = $this->getMockBuilder(LogicalExpression::class)
            ->disableOriginalConstructor()
            ->getMock();
        /** @var ExecutionContextInterface|\PHPUnit_Framework_MockObject_MockObject $context */
        $context = $this->getMock(ExecutionContextInterface::class);
        $context->expects($this->once())
            ->method('addViolation');
        $this->validator->initialize($context);

        $value = 'test';
        $node = $this->getMock(NodeInterface::class);
        $node->expects($this->once())
            ->method('isBoolean')
            ->willReturn(false);
        $this->expressionParser->expects($this->once())
            ->method('parse')
            ->with($value)
            ->willReturn($node);

        $this->validator->validate($value, $constraint);
    }
}
