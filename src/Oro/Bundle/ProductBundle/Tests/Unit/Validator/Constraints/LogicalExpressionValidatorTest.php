<?php

namespace Oro\Bundle\ProductBundle\Tests\Unit\Validator\Constraints;

use Oro\Bundle\ProductBundle\Validator\Constraints\LogicalExpression;
use Oro\Bundle\ProductBundle\Validator\Constraints\LogicalExpressionValidator;
use Oro\Component\Expression\ExpressionParser;
use Oro\Component\Expression\Node\NodeInterface;
use Oro\Component\Expression\Preprocessor\ExpressionPreprocessorInterface;
use Symfony\Component\ExpressionLanguage\SyntaxError;
use Symfony\Component\Validator\Test\ConstraintValidatorTestCase;

class LogicalExpressionValidatorTest extends ConstraintValidatorTestCase
{
    /** @var ExpressionParser|\PHPUnit\Framework\MockObject\MockObject */
    private $expressionParser;

    /** @var ExpressionPreprocessorInterface|\PHPUnit\Framework\MockObject\MockObject */
    private $preprocessor;

    protected function setUp(): void
    {
        $this->expressionParser = $this->createMock(ExpressionParser::class);
        $this->preprocessor = $this->createMock(ExpressionPreprocessorInterface::class);
        parent::setUp();
    }

    protected function createValidator()
    {
        return new LogicalExpressionValidator($this->expressionParser, $this->preprocessor);
    }

    public function testValidateValidLogicalExpression()
    {
        $value = 'test > 1';
        $node = $this->createMock(NodeInterface::class);
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

        $constraint = new LogicalExpression();
        $constraint->logicalExpressionsAllowed = true;
        $this->validator->validate($value, $constraint);

        $this->assertNoViolation();
    }

    public function testValidateValidNotLogicalExpression()
    {
        $value = 'test === 1';
        $node = $this->createMock(NodeInterface::class);
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

        $constraint = new LogicalExpression();
        $constraint->logicalExpressionsAllowed = false;
        $this->validator->validate($value, $constraint);

        $this->assertNoViolation();
    }

    public function testValidateInvalidLogicalExpression()
    {
        $value = 'test === 10';
        $node = $this->createMock(NodeInterface::class);
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

        $constraint = new LogicalExpression();
        $constraint->logicalExpressionsAllowed = true;
        $this->validator->validate($value, $constraint);

        $this->buildViolation($constraint->message)
            ->assertRaised();
    }

    public function testValidateInvalidNotLogicalExpression()
    {
        $value = 'test > 10';
        $node = $this->createMock(NodeInterface::class);
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

        $constraint = new LogicalExpression();
        $constraint->logicalExpressionsAllowed = false;
        $this->validator->validate($value, $constraint);

        $this->buildViolation($constraint->messageDisallowedLogicalExpression)
            ->assertRaised();
    }

    public function testValidateLogicalExpressionWithSyntaxError()
    {
        $value = 'pricelist[1].';

        $this->expressionParser->expects($this->once())
            ->method('parse')
            ->willThrowException(new SyntaxError('Expected name around position 14.'));

        $constraint = new LogicalExpression();
        $constraint->logicalExpressionsAllowed = true;
        $this->validator->validate($value, $constraint);

        $this->assertNoViolation();
    }
}
