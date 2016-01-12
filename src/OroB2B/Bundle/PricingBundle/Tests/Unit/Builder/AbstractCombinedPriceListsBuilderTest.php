<?php

namespace OroB2B\Bundle\PricingBundle\Tests\Unit\Builder;

use OroB2B\Bundle\PricingBundle\Builder\CombinedPriceListGarbageCollector;
use OroB2B\Bundle\PricingBundle\Provider\CombinedPriceListProvider;

abstract class AbstractCombinedPriceListsBuilderTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @param $collection
     * @param $actualCPLId
     * @return CombinedPriceListProvider|\PHPUnit_Framework_MockObject_MockObject
     */
    protected function getCombinedPriceListProviderMock($collection, $actualCPLId)
    {
        $actualCPL = $this->getMockBuilder('OroB2B\Bundle\PricingBundle\Entity\CombinedPriceList')
            ->disableOriginalConstructor()
            ->getMock();

        $actualCPL->expects($this->any())->method('getId')->willReturn($actualCPLId);

        $CPLProvider = $this->getMockBuilder('OroB2B\Bundle\PricingBundle\Provider\CombinedPriceListProvider')
            ->disableOriginalConstructor()
            ->getMock();
        $CPLProvider->expects($this->once())
            ->method('getCombinedPriceList')
            ->with($collection)
            ->willReturn($actualCPL);

        return $CPLProvider;
    }

    /**
     * @param $buildForOne
     * @return CombinedPriceListGarbageCollector|\PHPUnit_Framework_MockObject_MockObject
     */
    protected function getGarbageCollectorMock($buildForOne)
    {
        $collector = $this->getMockBuilder('OroB2B\Bundle\PricingBundle\Builder\CombinedPriceListGarbageCollector')
            ->disableOriginalConstructor()
            ->getMock();

        if ($buildForOne) {
            $collector->expects($this->once())->method('cleanCombinedPriceLists');
        } else {
            $collector->expects($this->never())->method('cleanCombinedPriceLists');
        }

        return $collector;
    }

}
