<?php

namespace Oro\Bundle\VisibilityBundle\Tests\Unit\EventListener;

use Oro\Bundle\FrontendBundle\Request\FrontendHelper;
use Oro\Bundle\ProductBundle\Event\ProductSearchQueryRestrictionEvent;
use Oro\Bundle\SearchBundle\Query\Modifier\QueryModifierInterface;
use Oro\Bundle\SearchBundle\Query\Query;
use Oro\Bundle\VisibilityBundle\EventListener\ProductSearchQueryRestrictionEventListener;

class ProductSearchQueryRestrictionEventListenerTest extends \PHPUnit\Framework\TestCase
{
    /** @var FrontendHelper|\PHPUnit\Framework\MockObject\MockObject */
    private $frontendHelper;

    /** @var QueryModifierInterface|\PHPUnit\Framework\MockObject\MockObject */
    private $queryModifier;

    /** @var ProductSearchQueryRestrictionEventListener */
    private $listener;

    protected function setUp(): void
    {
        $this->frontendHelper = $this->createMock(FrontendHelper::class);
        $this->queryModifier = $this->createMock(QueryModifierInterface::class);

        $this->listener = new ProductSearchQueryRestrictionEventListener(
            $this->frontendHelper,
            $this->queryModifier
        );
    }

    private function getEvent(): ProductSearchQueryRestrictionEvent
    {
        return new ProductSearchQueryRestrictionEvent(new Query());
    }

    public function testOnSearchQueryWithFrontendRequest()
    {
        $this->frontendHelper->expects($this->once())
            ->method('isFrontendRequest')
            ->willReturn(true);
        $this->queryModifier->expects($this->once())
            ->method('modify');

        $this->listener->onSearchQuery($this->getEvent());
    }

    public function testOnSearchQueryWithoutFrontendRequest()
    {
        $this->frontendHelper->expects($this->once())
            ->method('isFrontendRequest')
            ->willReturn(false);
        $this->queryModifier->expects($this->never())
            ->method('modify');

        $this->listener->onSearchQuery($this->getEvent());
    }
}
