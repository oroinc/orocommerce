<?php

namespace Oro\Bundle\PricingBundle\Tests\Unit\Api\PriceRule\Processor;

use Oro\Bundle\ApiBundle\Tests\Unit\Processor\FormProcessorTestCase;
use Oro\Bundle\PricingBundle\Api\PriceRule\Processor\UpdateLexemesPriceRule;
use Oro\Bundle\PricingBundle\Entity\PriceList;
use Oro\Bundle\PricingBundle\Entity\PriceRule;
use Oro\Bundle\PricingBundle\Handler\PriceRuleLexemeHandler;

class UpdateLexemesPriceRuleTest extends FormProcessorTestCase
{
    /**
     * @var PriceRuleLexemeHandler|\PHPUnit\Framework\MockObject\MockObject
     */
    protected $priceRuleLexemesHandler;

    /**
     * @var UpdateLexemesPriceRule
     */
    protected $processor;

    protected function setUp()
    {
        parent::setUp();

        $this->priceRuleLexemesHandler = $this->createMock(PriceRuleLexemeHandler::class);

        $this->processor = new UpdateLexemesPriceRule($this->priceRuleLexemesHandler);
    }

    public function testProcess()
    {
        $priceListMock = $this->createPriceListMock();
        $priceRuleMock = $this->createPriceRuleMock($priceListMock);

        $this->priceRuleLexemesHandler
            ->expects(static::once())
            ->method('updateLexemes')
            ->with($priceListMock);

        $this->context->setResult($priceRuleMock);
        $this->processor->process($this->context);
    }

    public function testProcessWithNullResult()
    {
        $this->priceRuleLexemesHandler
            ->expects(static::never())
            ->method('updateLexemes');

        $this->processor->process($this->context);
    }

    public function testProcessWithNullPriceList()
    {
        $priceRuleMock = $this->createPriceRuleMock();

        $this->priceRuleLexemesHandler
            ->expects(static::never())
            ->method('updateLexemes');

        $this->context->setResult($priceRuleMock);
        $this->processor->process($this->context);
    }

    /**
     * @param PriceList|null $priceList
     *
     * @return PriceRule|\PHPUnit\Framework\MockObject\MockObject
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
     * @return PriceList|\PHPUnit\Framework\MockObject\MockObject
     */
    protected function createPriceListMock()
    {
        return $this->createMock(PriceList::class);
    }
}
