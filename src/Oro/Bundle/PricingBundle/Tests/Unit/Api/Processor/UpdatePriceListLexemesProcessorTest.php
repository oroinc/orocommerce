<?php

namespace Oro\Bundle\PricingBundle\Tests\Unit\Api\Processor;

use Oro\Bundle\PricingBundle\Api\Processor\UpdatePriceListLexemesProcessor;
use Oro\Bundle\PricingBundle\Entity\PriceList;
use Oro\Bundle\PricingBundle\Handler\PriceRuleLexemeHandler;
use Oro\Component\ChainProcessor\ContextInterface;
use PHPUnit\Framework\TestCase;

class UpdatePriceListLexemesProcessorTest extends TestCase
{
    /**
     * @var PriceRuleLexemeHandler|\PHPUnit_Framework_MockObject_MockObject
     */
    private $priceRuleLexemeHandler;

    /**
     * @var UpdatePriceListLexemesProcessor
     */
    private $processor;

    protected function setUp()
    {
        $this->priceRuleLexemeHandler = $this->createMock(PriceRuleLexemeHandler::class);

        $this->processor = new UpdatePriceListLexemesProcessor($this->priceRuleLexemeHandler);
    }

    public function testProcessWrongType()
    {
        $this->priceRuleLexemeHandler
            ->expects(static::any())
            ->method('updateLexemes');

        $context = $this->createMock(ContextInterface::class);

        $this->processor->process($context);
    }

    public function testProcess()
    {
        $priceList = new PriceList();

        $this->priceRuleLexemeHandler
            ->expects(static::once())
            ->method('updateLexemes')
        ->with($priceList);

        $context = $this->createMock(ContextInterface::class);
        $context->expects(static::once())
            ->method('getResult')
            ->willReturn($priceList);

        $this->processor->process($context);
    }
}
