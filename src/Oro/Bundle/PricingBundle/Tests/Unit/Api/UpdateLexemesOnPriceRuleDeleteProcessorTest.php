<?php

namespace Oro\Bundle\PricingBundle\Tests\Unit\Api;

use Oro\Bundle\PricingBundle\Api\UpdateLexemesOnPriceRuleDeleteProcessor;
use Oro\Bundle\PricingBundle\Handler\PriceRuleLexemeHandler;
use Oro\Component\ChainProcessor\ProcessorInterface;

class UpdateLexemesOnPriceRuleDeleteProcessorTest extends AbstractUpdateLexemesTest
{
    /**
     * @var PriceRuleLexemeHandler|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $priceRuleLexemesHandlerMock;

    /**
     * @var ProcessorInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $deleteProcessorMock;

    /**
     * @var UpdateLexemesOnPriceRuleDeleteProcessor
     */
    protected $testedProcessor;

    protected function setUp()
    {
        $this->priceRuleLexemesHandlerMock = $this->createMock(PriceRuleLexemeHandler::class);
        $this->deleteProcessorMock = $this->createMock(ProcessorInterface::class);

        $this->testedProcessor = new UpdateLexemesOnPriceRuleDeleteProcessor(
            $this->priceRuleLexemesHandlerMock,
            $this->deleteProcessorMock
        );
    }

    public function testProcess()
    {
        $priceListMock = $this->createPriceListMock();
        $priceRuleMock = $this->createPriceRuleMock($priceListMock);
        $contextMock = $this->createContextMock($priceRuleMock);

        $this->deleteProcessorMock
            ->expects(static::once())
            ->method('process')
            ->with($contextMock);

        $this->priceRuleLexemesHandlerMock
            ->expects(static::once())
            ->method('updateLexemes')
            ->with($priceListMock);

        $this->testedProcessor->process($contextMock);
    }

    public function testProcessWithNullResult()
    {
        $contextMock = $this->createContextMock();

        $this->priceRuleLexemesHandlerMock
            ->expects(static::never())
            ->method('updateLexemes');

        $this->deleteProcessorMock
            ->expects(static::once())
            ->method('process')
            ->with($contextMock);

        $this->testedProcessor->process($contextMock);
    }

    public function testProcessWithNullPriceList()
    {
        $priceRuleMock = $this->createPriceRuleMock();
        $contextMock = $this->createContextMock($priceRuleMock);

        $contextMock
            ->expects(static::once())
            ->method('getResult')
            ->willReturn($priceRuleMock);

        $this->deleteProcessorMock
            ->expects(static::once())
            ->method('process')
            ->with($contextMock);

        $this->priceRuleLexemesHandlerMock
            ->expects(static::never())
            ->method('updateLexemes');

        $this->testedProcessor->process($contextMock);
    }
}
