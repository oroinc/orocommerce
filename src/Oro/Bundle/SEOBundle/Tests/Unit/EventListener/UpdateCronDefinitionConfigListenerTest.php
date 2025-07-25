<?php

namespace Oro\Bundle\SEOBundle\Tests\Unit\EventListener;

use Oro\Bundle\ConfigBundle\Event\ConfigUpdateEvent;
use Oro\Bundle\CronBundle\Entity\Manager\DeferredScheduler;
use Oro\Bundle\SEOBundle\EventListener\UpdateCronDefinitionConfigListener;

class UpdateCronDefinitionConfigListenerTest extends \PHPUnit\Framework\TestCase
{
    /** @var DeferredScheduler|\PHPUnit\Framework\MockObject\MockObject */
    private $deferredScheduler;

    /** @var UpdateCronDefinitionConfigListener */
    private $listener;

    #[\Override]
    protected function setUp(): void
    {
        $this->deferredScheduler = $this->createMock(DeferredScheduler::class);

        $this->listener = new UpdateCronDefinitionConfigListener($this->deferredScheduler);
    }

    public function testOnUpdateAfterWithoutNeededConfigOption()
    {
        $event = new ConfigUpdateEvent(['some_config' => ['old' => 0, 'new' => 1]], 'global', 0);
        $this->deferredScheduler->expects($this->never())
            ->method('removeSchedule');
        $this->deferredScheduler->expects($this->never())
            ->method('addSchedule');
        $this->deferredScheduler->expects($this->never())
            ->method('flush');
        $this->listener->onUpdateAfter($event);
    }

    public function testOnUpdateAfter()
    {
        $oldValue = 'old_value';
        $newValue = 'new_value';
        $event = new ConfigUpdateEvent(
            [UpdateCronDefinitionConfigListener::CONFIG_FIELD => ['old' => $oldValue, 'new' => $newValue]],
            'global',
            0
        );
        $this->deferredScheduler->expects($this->once())
            ->method('removeSchedule')
            ->with('oro:cron:sitemap:generate', [], $oldValue);
        $this->deferredScheduler->expects($this->once())
            ->method('addSchedule')
            ->with('oro:cron:sitemap:generate', [], $newValue);
        $this->deferredScheduler->expects($this->once())
            ->method('flush');
        $this->listener->onUpdateAfter($event);
    }
}
