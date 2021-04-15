<?php

namespace Oro\Bundle\WebsiteSearchBundle\Tests\Unit\Event;

use Oro\Bundle\WebsiteSearchBundle\Event\AfterReindexEvent;

class AfterReindexEventTest extends \PHPUnit\Framework\TestCase
{
    public function testAccessors(): void
    {
        $context = ['sample_key' => 'sample_value'];
        $indexedEntityIds = [10, 20];
        $removedEntityIds = [30, 40];
        $afterReindexEvent = new AfterReindexEvent(
            \stdClass::class,
            $context,
            $indexedEntityIds,
            $removedEntityIds
        );

        $this->assertEquals(\stdClass::class, $afterReindexEvent->getEntityClass());
        $this->assertEquals($context, $afterReindexEvent->getWebsiteContext());
        $this->assertEquals($indexedEntityIds, $afterReindexEvent->getIndexedEntityIds());
        $this->assertEquals($removedEntityIds, $afterReindexEvent->getRemovedEntityIds());
    }
}
