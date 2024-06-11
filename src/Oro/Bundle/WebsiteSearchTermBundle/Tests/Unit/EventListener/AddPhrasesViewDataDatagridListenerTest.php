<?php

namespace Oro\Bundle\WebsiteSearchTermBundle\Tests\Unit\EventListener;

use Oro\Bundle\DataGridBundle\Datagrid\DatagridInterface;
use Oro\Bundle\DataGridBundle\Datasource\ResultRecord;
use Oro\Bundle\DataGridBundle\Event\OrmResultAfter;
use Oro\Bundle\WebsiteSearchTermBundle\EventListener\AddPhrasesViewDataDatagridListener;
use Oro\Bundle\WebsiteSearchTermBundle\Formatter\SearchTermPhrasesFormatter;
use PHPUnit\Framework\TestCase;

class AddPhrasesViewDataDatagridListenerTest extends TestCase
{
    private AddPhrasesViewDataDatagridListener $listener;

    protected function setUp(): void
    {
        $this->listener = new AddPhrasesViewDataDatagridListener(
            new SearchTermPhrasesFormatter(',')
        );
    }

    public function testOnResultAfter(): void
    {
        $resultRecord1 = new ResultRecord(['id' => 1, 'phrases' => '']);
        $resultRecord2 = new ResultRecord(['id' => 2, 'phrases' => 'foo']);
        $resultRecord3 = new ResultRecord(['id' => 3, 'phrases' => 'foo,bar']);

        $event = new OrmResultAfter(
            $this->createMock(DatagridInterface::class),
            [$resultRecord1, $resultRecord2, $resultRecord3]
        );

        $this->listener->onResultAfter($event);

        $expectedRecord1 = new ResultRecord(['id' => 1, 'phrases' => '']);
        $expectedRecord2 = new ResultRecord(['id' => 2, 'phrases' => 'foo']);
        $expectedRecord3 = new ResultRecord(['id' => 3, 'phrases' => 'foo,bar']);
        $expectedRecord1->setValue('phrasesViewData', []);
        $expectedRecord2->setValue(
            'phrasesViewData',
            [
                'foo',
            ]
        );
        $expectedRecord3->setValue(
            'phrasesViewData',
            [
                'foo',
                'bar',
            ]
        );

        self::assertEquals(
            [
                $expectedRecord1,
                $expectedRecord2,
                $expectedRecord3,
            ],
            $event->getRecords()
        );
    }
}
