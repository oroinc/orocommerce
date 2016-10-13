<?php

namespace Oro\Bundle\ProductBundle\Tests\Unit\Validator\Constraints;

use Oro\Bundle\ProductBundle\Validator\Constraints\LogicalExpression;
use Oro\Bundle\ProductBundle\Validator\Constraints\LogicalExpressionValidator;
use Oro\Component\Expression\ExpressionParser;
use Oro\Component\Expression\Node\NodeInterface;
use Oro\Component\Expression\Preprocessor\ExpressionPreprocessorInterface;
use Symfony\Component\Validator\Context\ExecutionContextInterface;

class LogicalExpressionValidatorTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var ExpressionParser|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $expressionParser;

    /**
     * @var ExpressionPreprocessorInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $preprocessor;

    /**
     * @var LogicalExpressionValidator
     */
    protected $validator;

    protected function setUp()
    {
        $this->expressionParser = $this->getMockBuilder(ExpressionParser::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->preprocessor = $this->getMock(ExpressionPreprocessorInterface::class);
        $this->validator = new LogicalExpressionValidator($this->expressionParser, $this->preprocessor);
    }

    public function testValidateValidLogicalExpression()
    {
        $constraint = new LogicalExpression();
        $constraint->logicalExpressionsAllowed = true;
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

        $processedValue = 'test < 10';
        $this->preprocessor->expects($this->once())
            ->method('process')
            ->with($value)
            ->willReturn($processedValue);

        $this->expressionParser->expects($this->once())
            ->method('parse')
            ->with($processedValue)
            ->willReturn($node);

        $this->validator->validate($value, $constraint);
    }

    public function testValidateValidNotLogicalExpression()
    {
        $constraint = new LogicalExpression();
        $constraint->logicalExpressionsAllowed = false;
        /** @var ExecutionContextInterface|\PHPUnit_Framework_MockObject_MockObject $context */
        $context = $this->getMock(ExecutionContextInterface::class);
        $context->expects($this->never())
            ->method($this->anything());
        $this->validator->initialize($context);

        $value = 'test === 1';
        $node = $this->getMock(NodeInterface::class);
        $node->expects($this->once())
            ->method('isBoolean')
            ->willReturn(false);

        $processedValue = 'test == 10';
        $this->preprocessor->expects($this->once())
            ->method('process')
            ->with($value)
            ->willReturn($processedValue);

        $this->expressionParser->expects($this->once())
            ->method('parse')
            ->with($processedValue)
            ->willReturn($node);

        $this->validator->validate($value, $constraint);
    }

    public function testValidateInvalidLogicalExpression()
    {
        $constraint = new LogicalExpression();
        $constraint->logicalExpressionsAllowed = true;
        /** @var ExecutionContextInterface|\PHPUnit_Framework_MockObject_MockObject $context */
        $context = $this->getMock(ExecutionContextInterface::class);
        $context->expects($this->once())
            ->method('addViolation');
        $this->validator->initialize($context);

        $value = 'test === 10';
        $node = $this->getMock(NodeInterface::class);
        $node->expects($this->once())
            ->method('isBoolean')
            ->willReturn(false);

        $processedValue = 'test == 10';
        $this->preprocessor->expects($this->once())
            ->method('process')
            ->with($value)
            ->willReturn($processedValue);

        $this->expressionParser->expects($this->once())
            ->method('parse')
            ->with($processedValue)
            ->willReturn($node);

        $this->validator->validate($value, $constraint);
    }

    public function testValidateInvalidNotLogicalExpression()
    {
        $constraint = new LogicalExpression();
        $constraint->logicalExpressionsAllowed = false;
        /** @var ExecutionContextInterface|\PHPUnit_Framework_MockObject_MockObject $context */
        $context = $this->getMock(ExecutionContextInterface::class);
        $context->expects($this->once())
            ->method('addViolation');
        $this->validator->initialize($context);

        $value = 'test > 10';
        $node = $this->getMock(NodeInterface::class);
        $node->expects($this->once())
            ->method('isBoolean')
            ->willReturn(true);

        $processedValue = 'test < 10';
        $this->preprocessor->expects($this->once())
            ->method('process')
            ->with($value)
            ->willReturn($processedValue);

        $this->expressionParser->expects($this->once())
            ->method('parse')
            ->with($processedValue)
            ->willReturn($node);

        $this->validator->validate($value, $constraint);
    }
}
