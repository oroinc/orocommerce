<?php

namespace Oro\Bundle\SaleBundle\Tests\Unit\Model\Condition;

use Doctrine\Common\Collections\ArrayCollection;
use Oro\Bundle\SaleBundle\Entity\QuoteDemand;
use Oro\Bundle\SaleBundle\Model\Condition\QuoteAcceptable;
use Oro\Bundle\SaleBundle\Tests\Unit\Stub\QuoteStub as Quote;
use Oro\Component\ConfigExpression\ContextAccessor;
use Symfony\Component\PropertyAccess\PropertyPath;

class QuoteAcceptableTest extends \PHPUnit\Framework\TestCase
{
    /** @var QuoteAcceptable */
    protected $condition;

    protected function setUp(): void
    {
        $this->condition = new QuoteAcceptable();
        $this->condition->setContextAccessor(new ContextAccessor());
    }

    public function testGetName()
    {
        $this->assertEquals(QuoteAcceptable::NAME, $this->condition->getName());
    }

    public function testInitializeException()
    {
        $this->expectException(\Oro\Component\ConfigExpression\Exception\InvalidArgumentException::class);
        $this->expectExceptionMessage('First option should be valid property definition.');

        $this->condition->initialize([]);
    }

    /**
     * @dataProvider evaluateDataProvider
     *
     * @param array $options
     * @param Quote|mixed $quote
     * @param bool $expected
     */
    public function testEvaluate(array $options, $quote, $expected)
    {
        $errors = new ArrayCollection();

        $this->assertSame($this->condition, $this->condition->initialize($options));
        $this->assertEquals($expected, $this->condition->evaluate(['quote' => $quote], $errors));

        $this->assertCount((int)!$expected, $errors->toArray());

        if (!$expected) {
            $quote = $quote instanceof QuoteDemand ? $quote->getQuote() : $quote;
            $id = $quote instanceof Quote ? $quote->getQid() : null;

            $this->assertEquals(
                [
                    'message' => 'oro.frontend.sale.message.quote.not_available',
                    'parameters' => ['%qid%' => (int)$id]
                ],
                $errors->first()
            );
        }
    }

    /**
     * @return \Generator
     */
    public function evaluateDataProvider()
    {
        yield 'without quote and default false' => [
            'options' => [new PropertyPath('quote')],
            'quote' => null,
            'expected' => false
        ];

        yield 'without quote and default true' => [
            'options' => [new PropertyPath('quote'), true],
            'quote' => null,
            'expected' => true
        ];

        yield 'not acceptable quote and default false' => [
            'options' => [new PropertyPath('quote')],
            'quote' => $this->getQuote(),
            'expected' => false
        ];

        yield 'not acceptable quote and default true' => [
            'options' => [new PropertyPath('quote'), true],
            'quote' => $this->getQuote(),
            'expected' => false
        ];

        yield 'acceptable quote and default false' => [
            'options' => [new PropertyPath('quote')],
            'quote' => $this->getQuote(true),
            'expected' => true
        ];

        yield 'acceptable quote and default true' => [
            'options' => [new PropertyPath('quote'), true],
            'quote' => $this->getQuote(true),
            'expected' => true
        ];

        yield 'quoteDemand without quote and default false' => [
            'options' => [new PropertyPath('quote')],
            'quote' => $this->getQuoteDemand(true, false),
            'expected' => false
        ];

        yield 'quoteDemand without quote and default true' => [
            'options' => [new PropertyPath('quote'), true],
            'quote' => $this->getQuoteDemand(true, false),
            'expected' => true
        ];

        yield 'quoteDemand with not acceptable quote and default false' => [
            'options' => [new PropertyPath('quote')],
            'quote' => $this->getQuoteDemand(false),
            'expected' => false
        ];

        yield 'quoteDemand with not acceptable quote and default true' => [
            'options' => [new PropertyPath('quote'), true],
            'quote' => $this->getQuoteDemand(false),
            'expected' => false
        ];

        yield 'quoteDemand with acceptable quote and default false' => [
            'options' => [new PropertyPath('quote')],
            'quote' => $this->getQuoteDemand(true),
            'expected' => true
        ];

        yield 'quoteDemand with acceptable quote and default true' => [
            'options' => [new PropertyPath('quote'), true],
            'quote' => $this->getQuoteDemand(true),
            'expected' => true
        ];
    }

    /**
     * @param bool $isAcceptable
     * @return Quote|\PHPUnit\Framework\MockObject\MockObject
     */
    protected function getQuote($isAcceptable = false)
    {
        $quote = $this->createMock(Quote::class);
        $quote->expects($this->any())->method('isAcceptable')->willReturn($isAcceptable);
        $quote->expects($this->any())->method('getQid')->willReturn(42);
        $quote->expects($this->any())->method('getQuoteProducts')->willReturn([]);

        return $quote;
    }

    /**
     * @param bool $isAcceptable
     * @param bool $withQuote
     * @return QuoteDemand|\PHPUnit\Framework\MockObject\MockObject
     */
    protected function getQuoteDemand($isAcceptable = false, $withQuote = true)
    {
        $quoteDemand = new QuoteDemand();
        if ($withQuote) {
            $quoteDemand->setQuote($this->getQuote($isAcceptable));
        }

        return $quoteDemand;
    }
}
