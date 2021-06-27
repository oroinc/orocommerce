<?php

namespace Oro\Bundle\ProductBundle\Tests\Unit\EventListener;

use Oro\Bundle\DataGridBundle\Datagrid\Common\DatagridConfiguration;
use Oro\Bundle\DataGridBundle\Datasource\ResultRecord;
use Oro\Bundle\DataGridBundle\Event\PreBuild;
use Oro\Bundle\ProductBundle\EventListener\ProductStickersFrontendDatagridListener;
use Oro\Bundle\SearchBundle\Datagrid\Event\SearchResultAfter;

class ProductStickersFrontendDatagridListenerTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var ProductStickersFrontendDatagridListener
     */
    private $listener;

    protected function setUp(): void
    {
        $this->listener = new ProductStickersFrontendDatagridListener();
    }

    public function testOnPreBuild()
    {
        $event = $this->createPreBuildEventMock();

        $config = $this->createDatagridConfigurationMock();

        $event->expects(static::once())
            ->method('getConfig')
            ->willReturn($config);

        $config->expects(static::once())
            ->method('offsetAddToArrayByPath')
            ->with('[properties]', [
                'stickers' => [
                    'type' => 'field',
                    'frontend_type' => 'row_array',
                ],
            ]);

        $this->listener->onPreBuild($event);
    }

    public function testOnResultAfter()
    {
        $event = $this->createSearchResultAfterEventMock();

        $record1 = $this->createResultRecordMock();
        $record1->method('getValue')
            ->with('newArrival')
            ->willReturn(false);

        $record1->expects(static::once())
            ->method('addData')
            ->with([
                'stickers' => [],
            ]);

        $record2 = $this->createResultRecordMock();
        $record2->method('getValue')
            ->with('newArrival')
            ->willReturn(true);

        $record2->expects(static::once())
            ->method('addData')
            ->with([
                'stickers' => [
                    ['type' => 'new_arrival'],
                ],
            ]);

        $event->expects(static::once())
            ->method('getRecords')
            ->willReturn([$record1, $record2]);

        $this->listener->onResultAfter($event);
    }

    /**
     * @return PreBuild|\PHPUnit\Framework\MockObject\MockObject
     */
    private function createPreBuildEventMock()
    {
        return $this->createMock(PreBuild::class);
    }

    /**
     * @return SearchResultAfter|\PHPUnit\Framework\MockObject\MockObject
     */
    private function createSearchResultAfterEventMock()
    {
        return $this->createMock(SearchResultAfter::class);
    }

    /**
     * @return DatagridConfiguration|\PHPUnit\Framework\MockObject\MockObject
     */
    private function createDatagridConfigurationMock()
    {
        return $this->createMock(DatagridConfiguration::class);
    }

    /**
     * @return ResultRecord|\PHPUnit\Framework\MockObject\MockObject
     */
    private function createResultRecordMock()
    {
        return $this->createMock(ResultRecord::class);
    }
}
