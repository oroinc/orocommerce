<?php

namespace Oro\Bundle\PricingBundle\Tests\Unit\Api;

use Oro\Bundle\PricingBundle\Entity\PriceList;
use Oro\Bundle\PricingBundle\Entity\PriceRule;
use Oro\Component\ChainProcessor\ContextInterface;

abstract class AbstractUpdateLexemesTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @param PriceRule|null $priceRule
     *
     * @return ContextInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    protected function createContextMock(PriceRule $priceRule = null)
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
