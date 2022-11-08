<?php

namespace Oro\Bundle\VisibilityBundle\Tests\Unit\EventListener;

use Doctrine\ORM\QueryBuilder;
use Oro\Bundle\ProductBundle\Event\RestrictProductVariantEvent;
use Oro\Bundle\VisibilityBundle\EventListener\RestrictProductVariantEventVisibilityListener;
use Oro\Bundle\VisibilityBundle\Model\ProductVisibilityQueryBuilderModifier;

class RestrictProductVariantEventVisibilityListenerTest extends \PHPUnit\Framework\TestCase
{
    /** @var ProductVisibilityQueryBuilderModifier|\PHPUnit\Framework\MockObject\MockObject */
    private $modifier;

    /** @var RestrictProductVariantEvent|\PHPUnit\Framework\MockObject\MockObject */
    private $event;

    /** @var RestrictProductVariantEventVisibilityListener */
    private $listener;

    protected function setUp(): void
    {
        $this->modifier = $this->createMock(ProductVisibilityQueryBuilderModifier::class);
        $this->event = $this->createMock(RestrictProductVariantEvent::class);

        $this->listener = new RestrictProductVariantEventVisibilityListener($this->modifier);
    }

    public function testOnRestrictProductVariantEvent()
    {
        $queryBuilder = $this->createMock(QueryBuilder::class);
        $this->event->expects($this->once())
            ->method('getQueryBuilder')
            ->willReturn($queryBuilder);

        $this->modifier->expects($this->once())
            ->method('modify')
            ->with($queryBuilder);

        $this->listener->onRestrictProductVariantEvent($this->event);
    }
}
