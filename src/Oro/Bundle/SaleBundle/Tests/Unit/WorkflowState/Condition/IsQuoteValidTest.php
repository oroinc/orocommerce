<?php

namespace Oro\Bundle\SaleBundle\Tests\Unit\WorkflowState\Condition;

use Oro\Bundle\FormBundle\Utils\ValidationGroupUtils;
use Oro\Bundle\SaleBundle\Entity\Quote;
use Oro\Bundle\SaleBundle\WorkflowState\Condition\IsQuoteValid;
use Oro\Component\ConfigExpression\Condition\AbstractCondition;
use Oro\Component\ConfigExpression\Exception\InvalidArgumentException;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Validator\ConstraintViolation;
use Symfony\Component\Validator\ConstraintViolationList;
use Symfony\Component\Validator\Validator\ValidatorInterface;

class IsQuoteValidTest extends TestCase
{
    private ValidatorInterface|MockObject $validator;

    private IsQuoteValid $condition;

    protected function setUp(): void
    {
        $this->validator = $this->createMock(ValidatorInterface::class);
        $this->condition = new IsQuoteValid($this->validator);
    }

    /**
     * @dataProvider getInitializeInvalidDataProvider
     */
    public function testInitializeInvalid(array $options): void
    {
        $this->expectExceptionObject(new InvalidArgumentException('Missing "quote" option'));

        $this->condition->initialize($options);
    }

    public function getInitializeInvalidDataProvider(): array
    {
        return [
            'no option' => [
                'options' => [],
            ],
            'empty option' => [
                'options' => [
                    'quote' => null,
                ],
            ],
        ];
    }

    public function testInitialize(): void
    {
        self::assertInstanceOf(
            AbstractCondition::class,
            $this->condition->initialize(['quote' => new Quote()])
        );
    }

    public function testGetName(): void
    {
        self::assertEquals('is_quote_valid', $this->condition->getName());
    }

    public function testToArray(): void
    {
        $quote = new Quote();
        $validationGroups = ['foo', 'bar'];

        $this->condition->initialize(['quote' => $quote, 'validationGroups' => $validationGroups]);

        $result = $this->condition->toArray();

        self::assertEquals(
            [
                '@is_quote_valid' => [
                    'parameters' => [
                        $quote,
                        $validationGroups
                    ],
                ],
            ],
            $result
        );
    }

    public function testEvaluateNotQuote(): void
    {
        $this->validator->expects(self::never())
            ->method('validate');

        $this->condition->initialize(['quote' => new \stdClass(), 'validationGroups' => ['foo', 'bar']]);

        self::assertFalse($this->condition->evaluate([]));
    }

    public function testEvaluateValidationGroupsNotArray(): void
    {
        $this->validator->expects(self::never())
            ->method('validate');

        $this->condition->initialize(['quote' => new Quote(), 'validationGroups' => 'foo']);

        self::assertFalse($this->condition->evaluate([]));
    }

    public function testEvaluateQuoteInvalid(): void
    {
        $quote = new Quote();
        $validationGroups = [['groupA', 'groupB'], 'groupC'];

        $this->condition->initialize(['quote' => $quote, 'validationGroups' => $validationGroups]);

        $constraintViolationsList = new ConstraintViolationList([
            new ConstraintViolation('msg', null, [], null, null, $quote)
        ]);
        $this->validator->expects(self::once())
            ->method('validate')
            ->with($quote, null, ValidationGroupUtils::resolveValidationGroups($validationGroups))
            ->willReturn($constraintViolationsList);

        self::assertFalse($this->condition->evaluate([]));
    }

    public function testEvaluateQuoteValid(): void
    {
        $quote = new Quote();
        $validationGroups = [['groupA', 'groupB'], 'groupC'];

        $this->condition->initialize(['quote' => $quote, 'validationGroups' => $validationGroups]);

        $this->validator->expects(self::once())
            ->method('validate')
            ->with($quote, null, ValidationGroupUtils::resolveValidationGroups($validationGroups))
            ->willReturn(new ConstraintViolationList([]));

        self::assertTrue($this->condition->evaluate([]));
    }
}
