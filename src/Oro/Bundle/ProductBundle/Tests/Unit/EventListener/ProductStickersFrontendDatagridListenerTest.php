<?php

namespace Oro\Bundle\ProductBundle\Tests\Unit\EventListener;

use Oro\Bundle\DataGridBundle\Datagrid\Common\DatagridConfiguration;
use Oro\Bundle\DataGridBundle\Datasource\ResultRecord;
use Oro\Bundle\DataGridBundle\Event\PreBuild;
use Oro\Bundle\ProductBundle\EventListener\ProductStickersFrontendDatagridListener;
use Oro\Bundle\SearchBundle\Datagrid\Event\SearchResultAfter;

class ProductStickersFrontendDatagridListenerTest extends \PHPUnit\Framework\TestCase
{
    /** @var ProductStickersFrontendDatagridListener */
    private $listener;

    protected function setUp(): void
    {
        $this->listener = new ProductStickersFrontendDatagridListener();
    }

    public function testOnPreBuild()
    {
        $config = $this->createMock(DatagridConfiguration::class);
        $config->expects(self::once())
            ->method('offsetAddToArrayByPath')
            ->with(
                '[properties]',
                [
                    'stickers' => [
                        'type' => 'field',
                        'frontend_type' => 'row_array'
                    ]
                ]
            );

        $event = $this->createMock(PreBuild::class);
        $event->expects(self::once())
            ->method('getConfig')
            ->willReturn($config);
        $this->listener->onPreBuild($event);
    }

    public function testOnResultAfter()
    {
        $record1 = $this->createMock(ResultRecord::class);
        $record1->expects(self::once())
            ->method('getValue')
            ->with('newArrival')
            ->willReturn(false);
        $record1->expects(self::once())
            ->method('addData')
            ->with([
                'stickers' => []
            ]);

        $record2 = $this->createMock(ResultRecord::class);
        $record2->expects(self::once())
            ->method('getValue')
            ->with('newArrival')
            ->willReturn(true);
        $record2->expects(self::once())
            ->method('addData')
            ->with([
                'stickers' => [
                    ['type' => 'new_arrival']
                ]
            ]);

        $event = $this->createMock(SearchResultAfter::class);
        $event->expects(self::once())
            ->method('getRecords')
            ->willReturn([$record1, $record2]);

        $this->listener->onResultAfter($event);
    }
}
