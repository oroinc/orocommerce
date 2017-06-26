<?php

namespace Oro\Bundle\PricingBundle\Tests\Unit\Api;

use Oro\Bundle\PricingBundle\Api\UpdateLexemesOnPriceRuleDeleteListProcessor;
use Oro\Bundle\PricingBundle\Entity\PriceList;
use Oro\Bundle\PricingBundle\Entity\PriceRule;
use Oro\Bundle\PricingBundle\Handler\PriceRuleLexemeHandler;
use Oro\Component\ChainProcessor\ContextInterface;
use Oro\Component\ChainProcessor\ProcessorInterface;

class UpdateLexemesOnPriceRuleDeleteListProcessorTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var PriceRuleLexemeHandler|\PHPUnit_Framework_MockObject_MockObject
     */
    private $priceRuleLexemesHandlerMock;

    /**
     * @var ProcessorInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    private $deleteListProcessorMock;

    /**
     * @var UpdateLexemesOnPriceRuleDeleteListProcessor
     */
    private $testedProcessor;

    protected function setUp()
    {
        $this->priceRuleLexemesHandlerMock = $this->createMock(PriceRuleLexemeHandler::class);
        $this->deleteListProcessorMock = $this->createMock(ProcessorInterface::class);

        $this->testedProcessor = new UpdateLexemesOnPriceRuleDeleteListProcessor(
            $this->priceRuleLexemesHandlerMock,
            $this->deleteListProcessorMock
        );
    }

    /**
     * @return ContextInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    private function createContextMock()
    {
        return $this->createMock(ContextInterface::class);
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
     * @param int|null $id
     *
     * @return PriceList|\PHPUnit_Framework_MockObject_MockObject
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
        $contextMock = $this->createContextMock();

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

        $contextMock
            ->expects(static::once())
            ->method('getResult')
            ->willReturn($priceRulesMocks);

        $this->deleteListProcessorMock
            ->expects(static::once())
            ->method('process')
            ->with($contextMock);

        $this->priceRuleLexemesHandlerMock
            ->expects(static::exactly(3))
            ->method('updateLexemes')
            ->withConsecutive([$priceLists[0]], [$priceLists[1]], [$priceLists[3]]);

        $this->testedProcessor->process($contextMock);
    }

    public function testProcessWithWrongEntities()
    {
        $contextMock = $this->createContextMock();

        $priceRulesMocks = [
            new \stdClass(),
            new \stdClass()
        ];

        $contextMock
            ->expects(static::once())
            ->method('getResult')
            ->willReturn($priceRulesMocks);

        $this->deleteListProcessorMock
            ->expects(static::once())
            ->method('process')
            ->with($contextMock);

        $this->priceRuleLexemesHandlerMock
            ->expects(static::never())
            ->method('updateLexemes');

        $this->testedProcessor->process($contextMock);
    }

    public function testProcessWithNullPriceList()
    {
        $contextMock = $this->createContextMock();

        $priceRulesMocks = [
            $this->createPriceRuleMock(),
            $this->createPriceRuleMock(),
            $this->createPriceRuleMock(),
            $this->createPriceRuleMock(),
        ];

        $contextMock
            ->expects(static::once())
            ->method('getResult')
            ->willReturn($priceRulesMocks);

        $this->deleteListProcessorMock
            ->expects(static::once())
            ->method('process')
            ->with($contextMock);

        $this->priceRuleLexemesHandlerMock
            ->expects(static::never())
            ->method('updateLexemes');

        $this->testedProcessor->process($contextMock);
    }

    public function testProcessWithResultNonArray()
    {
        $contextMock = $this->createContextMock();

        $contextMock
            ->expects(static::once())
            ->method('getResult')
            ->willReturn(new \stdClass());

        $this->deleteListProcessorMock
            ->expects(static::once())
            ->method('process')
            ->with($contextMock);

        $this->priceRuleLexemesHandlerMock
            ->expects(static::never())
            ->method('updateLexemes');

        $this->testedProcessor->process($contextMock);
    }
}