<?php

namespace Oro\Bundle\PricingBundle\Tests\Unit\Api\PriceRule\Processor;

use Oro\Bundle\ApiBundle\Tests\Unit\Processor\Delete\DeleteProcessorTestCase;
use Oro\Bundle\PricingBundle\Api\PriceRule\Processor\UpdateLexemesOnPriceRuleDelete;
use Oro\Bundle\PricingBundle\Entity\PriceList;
use Oro\Bundle\PricingBundle\Entity\PriceRule;
use Oro\Bundle\PricingBundle\Handler\PriceRuleLexemeHandler;
use Oro\Component\ChainProcessor\ProcessorInterface;

class UpdateLexemesOnPriceRuleDeleteTest extends DeleteProcessorTestCase
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
     * @var UpdateLexemesOnPriceRuleDelete
     */
    protected $processor;

    protected function setUp()
    {
        parent::setUp();

        $this->priceRuleLexemesHandlerMock = $this->createMock(PriceRuleLexemeHandler::class);
        $this->deleteProcessorMock = $this->createMock(ProcessorInterface::class);

        $this->processor = new UpdateLexemesOnPriceRuleDelete(
            $this->priceRuleLexemesHandlerMock,
            $this->deleteProcessorMock
        );
    }

    public function testProcess()
    {
        $priceListMock = $this->createPriceListMock();
        $priceRuleMock = $this->createPriceRuleMock($priceListMock);

        $this->deleteProcessorMock
            ->expects(static::once())
            ->method('process')
            ->with($this->context);

        $this->priceRuleLexemesHandlerMock
            ->expects(static::once())
            ->method('updateLexemes')
            ->with($priceListMock);

        $this->context->setResult($priceRuleMock);
        $this->processor->process($this->context);
    }

    public function testProcessWithNullResult()
    {
        $this->priceRuleLexemesHandlerMock
            ->expects(static::never())
            ->method('updateLexemes');

        $this->deleteProcessorMock
            ->expects(static::once())
            ->method('process')
            ->with($this->context);

        $this->processor->process($this->context);
    }

    public function testProcessWithNullPriceList()
    {
        $priceRuleMock = $this->createPriceRuleMock();

        $this->deleteProcessorMock
            ->expects(static::once())
            ->method('process')
            ->with($this->context);

        $this->priceRuleLexemesHandlerMock
            ->expects(static::never())
            ->method('updateLexemes');

        $this->context->setResult($priceRuleMock);
        $this->processor->process($this->context);
    }

    /**
     * @param PriceList|null $priceList
     *
     * @return PriceRule|\PHPUnit_Framework_MockObject_MockObject
     */
    protected function createPriceRuleMock(PriceList $priceList = null)
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
    protected function createPriceListMock()
    {
        return $this->createMock(PriceList::class);
    }
}
