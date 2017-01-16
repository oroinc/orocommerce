<?php

namespace Oro\Bundle\OrderBundle\Tests\Unit\EventListener;

use Oro\Bundle\DataGridBundle\Datasource\ResultRecord;
use Oro\Bundle\DataGridBundle\Event\OrmResultAfter;
use Oro\Bundle\OrderBundle\EventListener\LineItemsFrontendDatagridListener;
use Oro\Bundle\ProductBundle\Provider\ConfigurableProductProvider;

class LineItemsFrontendDatagridListenerTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var LineItemsFrontendDatagridListener
     */
    protected $listener;

    /** @var ConfigurableProductProvider|\PHPUnit_Framework_MockObject_MockObject */
    protected $configurableProductProvider;

    /**
     * {@inheritdoc}
     */
    protected function setUp()
    {
        $this->configurableProductProvider = $this->getMockBuilder(ConfigurableProductProvider::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->listener = new LineItemsFrontendDatagridListener($this->configurableProductProvider);
    }

    /**
     * @dataProvider methodsDataProvider
     * @param array $returnResult
     * @param array $expectation
     */
    public function testOnResultAfter($returnResult, $expectation)
    {
        /** @var OrmResultAfter|\PHPUnit_Framework_MockObject_MockObject $eventMock */
        $eventMock = $this->getMockBuilder(OrmResultAfter::class)->disableOriginalConstructor()->getMock();
        $recordId = 1;
        $record = new ResultRecord(['id' => $recordId]);
        $records = [$record];
        $eventMock->expects($this->once())->method('getRecords')->willReturn($records);
        $this->configurableProductProvider->expects($this->once())->method('getLineItemProduct')
            ->willReturn($returnResult);

        $this->listener->onResultAfter($eventMock);
        $this->assertEquals($expectation, $record->getValue(1));
    }

    /**
     * @return array
     */
    public function methodsDataProvider()
    {
        return [
            'one method exists' => [
                'returnResult' => [
                    1 => [
                        'field1' => [
                            'value' => 2,
                            'label' => 'test.label'
                        ]
                    ],
                ],
                'expectation' => [
                    'field1' => [
                        'value' => 2,
                        'label' => 'test.label'
                    ]
                ],
            ],
            'few method exists' => [
                'returnResult' => [
                    1 => [
                        'field2' => [
                            'value' => 2,
                            'label' => 'test.label'
                        ],
                        'field1' => [
                            'value' => 'yes',
                            'label' => 'test.label'
                        ],
                    ],
                ],
                'expectation' => [
                    'field2' => [
                        'value' => 2,
                        'label' => 'test.label'
                    ],
                    'field1' => [
                        'value' => 'yes',
                        'label' => 'test.label'
                    ]
                ],
            ],
            'no one method exists' => [
                'returnResult' => [],
                'expectation' => null,
            ],
        ];
    }
}
