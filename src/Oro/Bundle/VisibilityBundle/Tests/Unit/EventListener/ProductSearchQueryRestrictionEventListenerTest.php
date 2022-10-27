<?php

namespace Oro\Bundle\VisibilityBundle\Tests\Unit\EventListener;

use Oro\Bundle\FrontendBundle\Request\FrontendHelper;
use Oro\Bundle\ProductBundle\Event\ProductSearchQueryRestrictionEvent;
use Oro\Bundle\SearchBundle\Query\Modifier\QueryModifierInterface;
use Oro\Bundle\SearchBundle\Query\Query;
use Oro\Bundle\VisibilityBundle\EventListener\ProductSearchQueryRestrictionEventListener;

class ProductSearchQueryRestrictionEventListenerTest extends \PHPUnit\Framework\TestCase
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
     * @return FrontendHelper|\PHPUnit\Framework\MockObject\MockObject
     */
    private function getFrontendHelper($isFrontendRequest = true)
    {
        /** @var FrontendHelper|\PHPUnit\Framework\MockObject\MockObject $frontendHelper */
        $frontendHelper = $this->getMockBuilder(FrontendHelper::class)
            ->disableOriginalConstructor()->getMock();

        $frontendHelper
            ->expects($this->once())
            ->method('isFrontendRequest')
            ->willReturn($isFrontendRequest);

        return $frontendHelper;
    }

    /**
     * @param \PHPUnit\Framework\MockObject\Rule\InvokedCount $expectedToBeCalled
     *
     * @return QueryModifierInterface|\PHPUnit\Framework\MockObject\MockObject
     */
    private function getQueryModifier(\PHPUnit\Framework\MockObject\Rule\InvokedCount $expectedToBeCalled)
    {
        /** @var QueryModifierInterface|\PHPUnit\Framework\MockObject\MockObject $queryModifier */
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
