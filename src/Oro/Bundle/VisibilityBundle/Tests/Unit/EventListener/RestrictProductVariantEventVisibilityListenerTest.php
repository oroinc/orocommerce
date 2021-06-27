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

    /** @var RestrictProductVariantEventVisibilityListener */
    private $listener;

    /** @var RestrictProductVariantEvent|\PHPUnit\Framework\MockObject\MockObject */
    private $event;

    /**
     * {@inheritdoc}
     */
    protected function setUp(): void
    {
        $this->modifier = $this->getMockBuilder(ProductVisibilityQueryBuilderModifier::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->event = $this->getMockBuilder(RestrictProductVariantEvent::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->listener = new RestrictProductVariantEventVisibilityListener($this->modifier);
    }

    public function testOnRestrictProductVariantEvent()
    {
        $queryBuilder = $this->getMockBuilder(QueryBuilder::class)->disableOriginalConstructor()->getMock();
        $this->event->expects($this->exactly(1))->method('getQueryBuilder')->willReturn($queryBuilder);

        $this->modifier->expects($this->once())->method('modify')->with($queryBuilder);

        $this->listener->onRestrictProductVariantEvent($this->event);
    }
}
