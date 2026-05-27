<?php

declare(strict_types=1);

namespace Oro\Bundle\OrderBundle\Tests\Unit\Datagrid\DraftSession;

use Oro\Bundle\DataGridBundle\Event\OrmResultAfter;
use Oro\Bundle\DataGridBundle\Event\OrmResultBefore;
use Oro\Bundle\OrderBundle\Datagrid\DraftSession\OrderLineItemsDraftSessionFilterListener;
use Oro\Component\DraftSession\Manager\DraftSessionOrmFilterManager;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

final class OrderLineItemsDraftSessionFilterListenerTest extends TestCase
{
    private DraftSessionOrmFilterManager&MockObject $draftSessionOrmFilterManager;
    private OrderLineItemsDraftSessionFilterListener $listener;

    #[\Override]
    protected function setUp(): void
    {
        $this->draftSessionOrmFilterManager = $this->createMock(DraftSessionOrmFilterManager::class);
        $this->listener = new OrderLineItemsDraftSessionFilterListener($this->draftSessionOrmFilterManager);
    }

    public function testOnResultBeforeDisablesFilter(): void
    {
        $event = $this->createMock(OrmResultBefore::class);

        $this->draftSessionOrmFilterManager
            ->expects(self::once())
            ->method('disable');

        $this->listener->onResultBefore($event);
    }

    public function testOnResultAfterEnablesFilter(): void
    {
        $event = $this->createMock(OrmResultAfter::class);

        $this->draftSessionOrmFilterManager
            ->expects(self::once())
            ->method('enable');

        $this->listener->onResultAfter($event);
    }
}
