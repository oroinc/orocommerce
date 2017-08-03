<?php

namespace Oro\Bundle\PricingBundle\Tests\Unit\Api\PriceRule\Processor;

use Oro\Bundle\PricingBundle\Api\PriceRule\Processor\UpdateLexemesPriceRuleProcessor;
use Oro\Bundle\PricingBundle\Handler\PriceRuleLexemeHandler;

class UpdateLexemesPriceRuleProcessorTest extends AbstractUpdateLexemesTest
{
    /**
     * @var PriceRuleLexemeHandler|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $priceRuleLexemesHandler;

    /**
     * @var UpdateLexemesPriceRuleProcessor
     */
    protected $testedProcessor;

    protected function setUp()
    {
        $this->priceRuleLexemesHandler = $this->createMock(PriceRuleLexemeHandler::class);

        $this->testedProcessor = new UpdateLexemesPriceRuleProcessor($this->priceRuleLexemesHandler);
    }

    public function testProcess()
    {
        $priceListMock = $this->createPriceListMock();
        $priceRuleMock = $this->createPriceRuleMock($priceListMock);
        $contextMock = $this->createContextMock($priceRuleMock);

        $this->priceRuleLexemesHandler
            ->expects(static::once())
            ->method('updateLexemes')
            ->with($priceListMock);

        $this->testedProcessor->process($contextMock);
    }

    public function testProcessWithNullResult()
    {
        $contextMock = $this->createContextMock();

        $this->priceRuleLexemesHandler
            ->expects(static::never())
            ->method('updateLexemes');

        $this->testedProcessor->process($contextMock);
    }

    public function testProcessWithNullPriceList()
    {
        $priceRuleMock = $this->createPriceRuleMock();
        $contextMock = $this->createContextMock($priceRuleMock);

        $this->priceRuleLexemesHandler
            ->expects(static::never())
            ->method('updateLexemes');

        $this->testedProcessor->process($contextMock);
    }
}
