<?php

namespace Oro\Bundle\PricingBundle\Tests\Unit\Api\PriceRule\Processor;

use Oro\Bundle\ApiBundle\Tests\Unit\Processor\DeleteList\DeleteListProcessorTestCase;
use Oro\Bundle\PricingBundle\Api\PriceRule\Processor\UpdateLexemesOnPriceRuleDeleteList;
use Oro\Bundle\PricingBundle\Entity\PriceList;
use Oro\Bundle\PricingBundle\Entity\PriceRule;
use Oro\Bundle\PricingBundle\Handler\PriceRuleLexemeHandler;
use Oro\Component\ChainProcessor\ProcessorInterface;

class UpdateLexemesOnPriceRuleDeleteListTest extends DeleteListProcessorTestCase
{
    /**
     * @var PriceRuleLexemeHandler|\PHPUnit\Framework\MockObject\MockObject
     */
    private $priceRuleLexemesHandlerMock;

    /**
     * @var ProcessorInterface|\PHPUnit\Framework\MockObject\MockObject
     */
    private $deleteListProcessorMock;

    /**
     * @var UpdateLexemesOnPriceRuleDeleteList
     */
    private $processor;

    protected function setUp()
    {
        parent::setUp();

        $this->priceRuleLexemesHandlerMock = $this->createMock(PriceRuleLexemeHandler::class);
        $this->deleteListProcessorMock = $this->createMock(ProcessorInterface::class);

        $this->processor = new UpdateLexemesOnPriceRuleDeleteList(
            $this->priceRuleLexemesHandlerMock,
            $this->deleteListProcessorMock
        );
    }

    /**
     * @param PriceList|null $priceList
     *
     * @return PriceRule|\PHPUnit\Framework\MockObject\MockObject
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
     * @param int|null $id
     *
     * @return PriceList|\PHPUnit\Framework\MockObject\MockObject
     */
    private function createPriceListMock(int $id = null)
    {
        $priceListMock = $this->createMock(PriceList::class);

        if (null === $priceListMock) {
            return $priceListMock;
        }

        $priceListMock
            ->expects(static::any())
            ->method('getId')
            ->willReturn($id);

        return $priceListMock;
    }

    public function testProcess()
    {
        $priceLists = [
            $this->createPriceListMock(1),
            $this->createPriceListMock(2),
            $this->createPriceListMock(1),
            $this->createPriceListMock(4)
        ];

        $priceRulesMocks = [
            $this->createPriceRuleMock($priceLists[0]),
            $this->createPriceRuleMock($priceLists[1]),
            $this->createPriceRuleMock($priceLists[2]),
            $this->createPriceRuleMock($priceLists[3]),
        ];

        $this->deleteListProcessorMock
            ->expects(static::once())
            ->method('process')
            ->with($this->context);

        $this->priceRuleLexemesHandlerMock
            ->expects(static::exactly(3))
            ->method('updateLexemes')
            ->withConsecutive([$priceLists[0]], [$priceLists[1]], [$priceLists[3]]);

        $this->context->setResult($priceRulesMocks);
        $this->processor->process($this->context);
    }

    public function testProcessWithWrongEntities()
    {
        $priceRulesMocks = [
            new \stdClass(),
            new \stdClass()
        ];

        $this->deleteListProcessorMock
            ->expects(static::once())
            ->method('process')
            ->with($this->context);

        $this->priceRuleLexemesHandlerMock
            ->expects(static::never())
            ->method('updateLexemes');

        $this->context->setResult($priceRulesMocks);
        $this->processor->process($this->context);
    }

    public function testProcessWithNullPriceList()
    {
        $priceRulesMocks = [
            $this->createPriceRuleMock(),
            $this->createPriceRuleMock(),
            $this->createPriceRuleMock(),
            $this->createPriceRuleMock(),
        ];

        $this->deleteListProcessorMock
            ->expects(static::once())
            ->method('process')
            ->with($this->context);

        $this->priceRuleLexemesHandlerMock
            ->expects(static::never())
            ->method('updateLexemes');

        $this->context->setResult($priceRulesMocks);
        $this->processor->process($this->context);
    }

    public function testProcessWithResultNonArray()
    {
        $this->deleteListProcessorMock
            ->expects(static::once())
            ->method('process')
            ->with($this->context);

        $this->priceRuleLexemesHandlerMock
            ->expects(static::never())
            ->method('updateLexemes');

        $this->context->setResult(new \stdClass());
        $this->processor->process($this->context);
    }
}
