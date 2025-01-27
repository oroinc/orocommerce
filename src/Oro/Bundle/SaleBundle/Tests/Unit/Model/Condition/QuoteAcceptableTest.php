<?php

namespace Oro\Bundle\SaleBundle\Tests\Unit\Model\Condition;

use Doctrine\Common\Collections\ArrayCollection;
use Oro\Bundle\SaleBundle\Entity\QuoteDemand;
use Oro\Bundle\SaleBundle\Model\Condition\QuoteAcceptable;
use Oro\Bundle\SaleBundle\Tests\Unit\Stub\QuoteStub as Quote;
use Oro\Component\ConfigExpression\ContextAccessor;
use Oro\Component\ConfigExpression\Exception\InvalidArgumentException;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\PropertyAccess\PropertyPath;
use Symfony\Component\Validator\ConstraintViolationInterface;
use Symfony\Component\Validator\ConstraintViolationList;
use Symfony\Component\Validator\Validator\ValidatorInterface;

class QuoteAcceptableTest extends TestCase
{
    private ValidatorInterface|MockObject $validator;
    private QuoteAcceptable $condition;

    #[\Override]
    protected function setUp(): void
    {
        $this->validator = $this->createMock(ValidatorInterface::class);

        $this->condition = new QuoteAcceptable($this->validator);
        $this->condition->setContextAccessor(new ContextAccessor());
    }

    private function getQuote(bool $isAcceptable = false): Quote
    {
        $quote = $this->createMock(Quote::class);
        $quote->expects(self::any())
            ->method('isAcceptable')
            ->willReturn($isAcceptable);
        $quote->expects(self::any())
            ->method('getQid')
            ->willReturn(42);
        $quote->expects(self::any())
            ->method('getQuoteProducts')
            ->willReturn([]);

        return $quote;
    }

    private function getQuoteDemand(bool $isAcceptable, bool $withQuote = true): QuoteDemand
    {
        $quoteDemand = new QuoteDemand();
        if ($withQuote) {
            $quoteDemand->setQuote($this->getQuote($isAcceptable));
        }

        return $quoteDemand;
    }

    public function testGetName(): void
    {
        self::assertEquals('quote_acceptable', $this->condition->getName());
    }

    public function testInitializeException(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('First option should be valid property definition.');

        $this->condition->initialize([]);
    }

    /**
     * @dataProvider evaluateDataProvider
     */
    public function testEvaluate(array $options, ?object $quote, bool $expected): void
    {
        $errors = new ArrayCollection();

        $violationsArray = [];
        if (!$expected) {
            $violationsArray[] = $this->createMock(ConstraintViolationInterface::class);
        }
        $violations = new ConstraintViolationList($violationsArray);
        $this->validator->expects(self::once())
            ->method('validate')
            ->willReturn($violations);

        self::assertSame($this->condition, $this->condition->initialize($options));
        self::assertEquals($expected, $this->condition->evaluate(['quote' => $quote], $errors));

        self::assertCount((int)!$expected, $errors->toArray());

        if (!$expected) {
            $quote = $quote instanceof QuoteDemand ? $quote->getQuote() : $quote;
            $id = $quote instanceof Quote ? $quote->getQid() : null;

            self::assertEquals(
                [
                    'message' => 'oro.frontend.sale.message.quote.not_available',
                    'parameters' => ['%qid%' => (int)$id]
                ],
                $errors->first()
            );
        }
    }

    public function evaluateDataProvider(): array
    {
        return [
            'without quote and default false' => [
                'options' => [new PropertyPath('quote')],
                'quote' => null,
                'expected' => false
            ],
            'without quote and default true' => [
                'options' => [new PropertyPath('quote'), true],
                'quote' => null,
                'expected' => true
            ],
            'not acceptable quote and default false' => [
                'options' => [new PropertyPath('quote')],
                'quote' => $this->getQuote(),
                'expected' => false
            ],
            'not acceptable quote and default true' => [
                'options' => [new PropertyPath('quote'), true],
                'quote' => $this->getQuote(),
                'expected' => false
            ],
            'acceptable quote and default false' => [
                'options' => [new PropertyPath('quote')],
                'quote' => $this->getQuote(true),
                'expected' => true
            ],
            'acceptable quote and default true' => [
                'options' => [new PropertyPath('quote'), true],
                'quote' => $this->getQuote(true),
                'expected' => true
            ],
            'quoteDemand without quote and default false' => [
                'options' => [new PropertyPath('quote')],
                'quote' => $this->getQuoteDemand(true, false),
                'expected' => false
            ],
            'quoteDemand without quote and default true' => [
                'options' => [new PropertyPath('quote'), true],
                'quote' => $this->getQuoteDemand(true, false),
                'expected' => true
            ],
            'quoteDemand with not acceptable quote and default false' => [
                'options' => [new PropertyPath('quote')],
                'quote' => $this->getQuoteDemand(false),
                'expected' => false
            ],
            'quoteDemand with not acceptable quote and default true' => [
                'options' => [new PropertyPath('quote'), true],
                'quote' => $this->getQuoteDemand(false),
                'expected' => false
            ],
            'quoteDemand with acceptable quote and default false' => [
                'options' => [new PropertyPath('quote')],
                'quote' => $this->getQuoteDemand(true),
                'expected' => true
            ],
            'quoteDemand with acceptable quote and default true' => [
                'options' => [new PropertyPath('quote'), true],
                'quote' => $this->getQuoteDemand(true),
                'expected' => true
            ]
        ];
    }

    public function testEvaluateWithDefaultPropertyPath(): void
    {
        $errors = new ArrayCollection();
        $options = [new PropertyPath('quote'), true];

        self::assertSame($this->condition, $this->condition->initialize($options));
        self::assertTrue($this->condition->evaluate(['quote' => null], $errors));

        self::assertCount(0, $errors->toArray());
    }
}
