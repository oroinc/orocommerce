<?php

namespace OroB2B\Bundle\RFPBundle\Tests\Unit\EventListener;

use Oro\Bundle\DataGridBundle\Datagrid\DatagridInterface;
use Oro\Bundle\DataGridBundle\Datasource\ResultRecord;
use Oro\Bundle\DataGridBundle\Event\OrmResultAfter;

use OroB2B\Bundle\RFPBundle\Entity\RequestStatus;
use OroB2B\Bundle\RFPBundle\EventListener\RequestFrontendDatagridListener;

class RequestFrontendDatagridListenerTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var RequestFrontendDatagridListener
     */
    protected $listener;

    /**
     * {@inheritdoc}
     */
    protected function setUp()
    {
        $this->listener = new RequestFrontendDatagridListener();
    }

    /**
     * {@inheritdoc}
     */
    protected function tearDown()
    {
        unset($this->listener);
    }

    /**
     * @param array $sourceResults
     * @param array $expectedResults
     *
     * @dataProvider onResultAfterDataProvider
     */
    public function testOnResultAfter(array $sourceResults = [], array $expectedResults = [])
    {
        $sourceResultRecords = [];
        foreach ($sourceResults as $sourceResult) {
            $sourceResultRecords[] = new ResultRecord($sourceResult);
        }

        /** @var \PHPUnit_Framework_MockObject_MockObject|DatagridInterface $datagrid */
        $datagrid = $this->getMock('Oro\Bundle\DataGridBundle\Datagrid\DatagridInterface');
        $event = new OrmResultAfter($datagrid, $sourceResultRecords);
        $this->listener->onResultAfter($event);
        $actualResults = $event->getRecords();

        static::assertSameSize($expectedResults, $actualResults);
        foreach ($expectedResults as $key => $expectedResult) {
            $actualResult = $actualResults[$key];
            foreach ($expectedResult as $name => $value) {
                static::assertEquals($value, $actualResult->getValue($name));
            }
        }
    }

    /**
     * @return array
     */
    public function onResultAfterDataProvider()
    {
        $date = time();
        return [
            'no request' => [],
            'valid data' => [
                'sourceResults' => [
                    [
                        'id' => 1,
                        'statusName' => RequestStatus::DRAFT,
                        'createdAt' => $date,
                    ],
                    [
                        'id' => 2,
                        'statusName' => RequestStatus::OPEN,
                        'createdAt' => $date,
                    ],
                    [
                        'id' => 3,
                        'statusName' => RequestStatus::CLOSED,
                        'createdAt' => $date,
                    ],
                ],
                'expectedResults' => [
                    [
                        'id' => 1,
                        'statusName' => RequestStatus::DRAFT,
                        'isDraft' => true,
                        'createdAt' => $date,
                    ],
                    [
                        'id' => 2,
                        'statusName' => RequestStatus::OPEN,
                        'isDraft' => false,
                        'createdAt' => $date,
                    ],
                    [
                        'id' => 3,
                        'statusName' => RequestStatus::CLOSED,
                        'isDraft' => false,
                        'createdAt' => $date,
                    ],
                ],
            ],
        ];
    }
}
