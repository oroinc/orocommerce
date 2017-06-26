<?php

namespace Oro\Bundle\PricingBundle\Tests\Unit\Api;

use Oro\Bundle\PricingBundle\Api\UpdateLexemesOnPriceRuleDeleteProcessor;
use Oro\Bundle\PricingBundle\Entity\PriceList;
use Oro\Bundle\PricingBundle\Entity\PriceRule;
use Oro\Bundle\PricingBundle\Handler\PriceRuleLexemeHandler;
use Oro\Component\ChainProcessor\ContextInterface;
use Oro\Component\ChainProcessor\ProcessorInterface;

class UpdateLexemesOnPriceRuleDeleteProcessorTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var PriceRuleLexemeHandler|\PHPUnit_Framework_MockObject_MockObject
     */
    private $priceRuleLexemesHandlerMock;

    /**
     * @var ProcessorInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    private $deleteProcessorMock;

    /**
     * @var UpdateLexemesOnPriceRuleDeleteProcessor
     */
    private $testedProcessor;

    protected function setUp()
    {
        $this->priceRuleLexemesHandlerMock = $this->createMock(PriceRuleLexemeHandler::class);
        $this->deleteProcessorMock = $this->createMock(ProcessorInterface::class);

        $this->testedProcessor = new UpdateLexemesOnPriceRuleDeleteProcessor(
            $this->priceRuleLexemesHandlerMock,
            $this->deleteProcessorMock
        );
    }

    /**
     * @param PriceRule|null $priceRule
     *
     * @return ContextInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    private function createContextMock(PriceRule $priceRule = null)
    {
        $contextMock = $this->createMock(ContextInterface::class);

        if (null === $priceRule) {
            return $contextMock;
        }

        $contextMock
            ->expects(static::any())
            ->method('getResult')
            ->willReturn($priceRule);

        return $contextMock;
    }

    /**
     * @param PriceList|null $priceList
     *
     * @return PriceRule|\PHPUnit_Framework_MockObject_MockObject
     */
    private function createPriceRuleMock(PriceList $priceList = null)
    {
        $priceRuleMock = $this->createMock(PriceRule::class);

        if (null === $priceList) {
            return $priceRuleMock;
        }

        $priceRuleMock
            ->expects(static::any())
            ->method('getPriceList')
            ->willReturn($priceList);

        return $priceRuleMock;
    }

    /**
     * @return PriceList|\PHPUnit_Framework_MockObject_MockObject
     */
    private function createPriceListMock()
    {
        return $this->createMock(PriceList::class);
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