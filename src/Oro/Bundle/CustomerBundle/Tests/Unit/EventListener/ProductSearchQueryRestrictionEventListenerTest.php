<?php

namespace Oro\Bundle\CustomerBundle\Tests\Unit\EventListener;

use Oro\Bundle\CustomerBundle\EventListener\ProductSearchQueryRestrictionEventListener;
use Oro\Bundle\FrontendBundle\Request\FrontendHelper;
use Oro\Bundle\ProductBundle\Event\ProductSearchQueryRestrictionEvent;
use Oro\Bundle\SearchBundle\Query\Modifier\QueryModifierInterface;
use Oro\Bundle\SearchBundle\Query\Query;

class ProductSearchQueryRestrictionEventListenerTest extends \PHPUnit_Framework_TestCase
{
    public function testOnSearchQueryWithFrontendRequest()
    {
        $listener = new ProductSearchQueryRestrictionEventListener(
            $this->getFrontendHelper(true),
            $this->getQueryModifier($this->once())
        );

        $listener->onSearchQuery($this->getEvent());
    }

    public function testOnSearchQueryWithoutFrontendRequest()
    {
        $listener = new ProductSearchQueryRestrictionEventListener(
            $this->getFrontendHelper(false),
            $this->getQueryModifier($this->never())
        );

        $listener->onSearchQuery($this->getEvent());
    }

    /**
     * @param bool $isFrontendRequest
     *
     * @return FrontendHelper|\PHPUnit_Framework_MockObject_MockObject
     */
    private function getFrontendHelper($isFrontendRequest = true)
    {
        /** @var FrontendHelper|\PHPUnit_Framework_MockObject_MockObject $frontendHelper */
        $frontendHelper = $this->getMockBuilder(FrontendHelper::class)
            ->disableOriginalConstructor()->getMock();

        $frontendHelper
            ->expects($this->once())
            ->method('isFrontendRequest')
            ->willReturn($isFrontendRequest);

        return $frontendHelper;
    }

    /**
     * @param \PHPUnit_Framework_MockObject_Matcher_InvokedCount $expectedToBeCalled
     *
     * @return QueryModifierInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    private function getQueryModifier(\PHPUnit_Framework_MockObject_Matcher_InvokedCount $expectedToBeCalled)
    {
        /** @var QueryModifierInterface|\PHPUnit_Framework_MockObject_MockObject $queryModifier */
        $queryModifier = $this->getMockBuilder(QueryModifierInterface::class)->getMock();

        $queryModifier
            ->expects($expectedToBeCalled)
            ->method('modify');

        return $queryModifier;
    }

    /**
     * @return ProductSearchQueryRestrictionEvent
     */
    private function getEvent()
    {
        return new ProductSearchQueryRestrictionEvent(new Query());
    }
}
