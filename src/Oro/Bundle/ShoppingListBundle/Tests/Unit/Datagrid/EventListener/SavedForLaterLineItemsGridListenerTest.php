<?php

declare(strict_types=1);

namespace Oro\Bundle\ShoppingListBundle\Tests\Unit\Datagrid\EventListener;

use Oro\Bundle\DataGridBundle\Datagrid\Common\DatagridConfiguration;
use Oro\Bundle\DataGridBundle\Datagrid\DatagridInterface;
use Oro\Bundle\DataGridBundle\Datasource\Orm\OrmQueryConfiguration;
use Oro\Bundle\DataGridBundle\Event\BuildBefore;
use Oro\Bundle\ShoppingListBundle\Datagrid\EventListener\SavedForLaterLineItemsGridListener;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

final class SavedForLaterLineItemsGridListenerTest extends TestCase
{
    private DatagridInterface&MockObject $datagrid;

    private SavedForLaterLineItemsGridListener $listener;

    #[\Override]
    protected function setUp(): void
    {
        $this->datagrid = $this->createMock(DatagridInterface::class);

        $this->listener = new SavedForLaterLineItemsGridListener();
    }

    public function testOnBuildBeforeNoOrmQuery(): void
    {
        $config = DatagridConfiguration::create([]);

        $this->listener->onBuildBefore(new BuildBefore($this->datagrid, $config));

        self::assertNull($config->offsetGetByPath(OrmQueryConfiguration::SELECT_PATH));
        self::assertNull($config->offsetGetByPath(OrmQueryConfiguration::WHERE_AND_PATH));
    }

    public function testOnBuildBefore(): void
    {
        $config = DatagridConfiguration::create([]);
        $config->offsetSetByPath(
            OrmQueryConfiguration::SELECT_PATH,
            [SavedForLaterLineItemsGridListener::REPLACED_SELECT_EXPRESSION]
        );
        $config->offsetSetByPath(
            OrmQueryConfiguration::WHERE_AND_PATH,
            [SavedForLaterLineItemsGridListener::REPLACED_AND_WHERE_CONDITION]
        );

        $this->listener->onBuildBefore(new BuildBefore($this->datagrid, $config));

        self::assertSame(
            [SavedForLaterLineItemsGridListener::SELECT_EXPRESSION],
            $config->offsetGetByPath(OrmQueryConfiguration::SELECT_PATH)
        );
        self::assertSame(
            [SavedForLaterLineItemsGridListener::AND_WHERE_CONDITION],
            $config->offsetGetByPath(OrmQueryConfiguration::WHERE_AND_PATH)
        );
    }
}
