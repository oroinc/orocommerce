<?php

namespace Oro\Bundle\RedirectBundle\Tests\Unit\EventListener;

use Oro\Bundle\ImportExportBundle\Event\StrategyEvent;
use Oro\Bundle\RedirectBundle\EventListener\ImportSluggableEntityListener;
use Oro\Bundle\RedirectBundle\Helper\SlugifyEntityHelper;
use Oro\Bundle\RedirectBundle\Tests\Unit\Entity\SluggableEntityStub;
use Oro\Bundle\TestFrameworkBundle\Entity\TestActivity;

class ImportSluggableEntityListenerTest extends \PHPUnit\Framework\TestCase
{
    /** @var ImportSluggableEntityListener */
    private $listener;

    /** @var SlugifyEntityHelper|\PHPUnit\Framework\MockObject\MockObject */
    private $slugifyEntityHelper;

    protected function setUp(): void
    {
        $this->slugifyEntityHelper = $this->createMock(SlugifyEntityHelper::class);
        $this->listener = new ImportSluggableEntityListener($this->slugifyEntityHelper);
    }

    public function testSluggableEntity(): void
    {
        $entity = new SluggableEntityStub();
        $event = $this->createMock(StrategyEvent::class);
        $event
            ->expects($this->once())
            ->method('getEntity')
            ->willReturn($entity);

        $this->slugifyEntityHelper
            ->expects($this->once())
            ->method('fill');

        $this->listener->onProcessBefore($event);
    }

    public function testNonSluggableEntity(): void
    {
        $entity = new TestActivity();
        $event = $this->createMock(StrategyEvent::class);
        $event
            ->expects($this->once())
            ->method('getEntity')
            ->willReturn($entity);

        $this->slugifyEntityHelper
            ->expects($this->never())
            ->method('fill');

        $this->listener->onProcessBefore($event);
    }
}
