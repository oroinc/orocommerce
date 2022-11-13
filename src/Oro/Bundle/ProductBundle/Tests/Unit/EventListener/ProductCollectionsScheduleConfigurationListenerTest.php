<?php

namespace Oro\Bundle\ProductBundle\Tests\Unit\EventListener;

use Oro\Bundle\ConfigBundle\Event\ConfigUpdateEvent;
use Oro\Bundle\CronBundle\Entity\Manager\DeferredScheduler;
use Oro\Bundle\ProductBundle\Command\ProductCollectionsIndexCronCommand;
use Oro\Bundle\ProductBundle\EventListener\ProductCollectionsScheduleConfigurationListener;

class ProductCollectionsScheduleConfigurationListenerTest extends \PHPUnit\Framework\TestCase
{
    /** @var DeferredScheduler|\PHPUnit\Framework\MockObject\MockObject */
    private $deferredScheduler;

    /** @var ProductCollectionsScheduleConfigurationListener */
    private $listener;

    protected function setUp(): void
    {
        $this->deferredScheduler = $this->createMock(DeferredScheduler::class);

        $this->listener = new ProductCollectionsScheduleConfigurationListener($this->deferredScheduler);
    }

    public function testOnUpdateAfterWhenConfigurationHasNotChanged()
    {
        $event = new ConfigUpdateEvent(['some_config' => ['old' => 0, 'new' => 1]]);
        $this->deferredScheduler->expects($this->never())
            ->method('removeSchedule');
        $this->deferredScheduler->expects($this->never())
            ->method('addSchedule');
        $this->deferredScheduler->expects($this->never())
            ->method('flush');
        $this->listener->onUpdateAfter($event);
    }

    public function testOnUpdateAfterWhenConfigurationHasChanged()
    {
        $oldValue = 'old_value';
        $newValue = 'new_value';
        $event = new ConfigUpdateEvent([
            'oro_product.product_collections_indexation_cron_schedule' => ['old' => $oldValue, 'new' => $newValue]
        ]);
        $this->deferredScheduler->expects($this->once())
            ->method('removeSchedule')
            ->with(ProductCollectionsIndexCronCommand::getDefaultName(), [], $oldValue);
        $this->deferredScheduler->expects($this->once())
            ->method('addSchedule')
            ->with(ProductCollectionsIndexCronCommand::getDefaultName(), [], $newValue);
        $this->deferredScheduler->expects($this->once())
            ->method('flush');
        $this->listener->onUpdateAfter($event);
    }
}
