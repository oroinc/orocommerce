<?php

namespace OroB2B\Bundle\AccountBundle\Tests\Unit\Audit;

use Oro\Bundle\DataGridBundle\Datagrid\Common\DatagridConfiguration;
use Oro\Bundle\DataGridBundle\Datagrid\DatagridInterface;
use Oro\Bundle\DataGridBundle\Event\BuildBefore;
use Oro\Bundle\DataGridBundle\Provider\ConfigurationProviderInterface;

use OroB2B\Bundle\AccountBundle\Audit\AuditDatagridListener;

class AuditDatagridListenerTest extends \PHPUnit_Framework_TestCase
{
    /** @var AuditDatagridListener */
    protected $listener;

    /** @var \PHPUnit_Framework_MockObject_MockObject|ConfigurationProviderInterface */
    protected $configurationProvider;

    protected function setUp()
    {
        $this->configurationProvider = $this->getMock(
            'Oro\Bundle\DataGridBundle\Provider\ConfigurationProviderInterface'
        );

        $this->listener = new AuditDatagridListener($this->configurationProvider);
    }

    protected function tearDown()
    {
        unset($this->configurationProvider, $this->listener);
    }

    /**
     * @dataProvider buildBeforeProvider
     * @param string $method
     * @param string $expectedDatagridName
     */
    public function testBuildBefore($method, $expectedDatagridName)
    {
        /** @var \PHPUnit_Framework_MockObject_MockObject|DatagridInterface $eventDatagrid */
        $eventDatagrid = $this->getMock('Oro\Bundle\DataGridBundle\Datagrid\DatagridInterface');
        $eventConfig = DatagridConfiguration::create(
            [
                'name' => 'original-datagrid-name',
                'query' => [
                    'select' => [
                        'id',
                    ],
                    'from' => [
                        'value',
                    ],
                ],
            ]
        );

        $gridConfiguration = DatagridConfiguration::create(
            [
                'name' => 'new-datagrid-name',
                'query' => [
                    'select' => [
                        'new-id',
                    ],
                    'from' => [
                        'new-value',
                    ],
                ],
            ]
        );

        $this->configurationProvider->expects($this->once())
            ->method('getConfiguration')
            ->with($expectedDatagridName)
            ->willReturn($gridConfiguration);

        $event = new BuildBefore($eventDatagrid, $eventConfig);
        $this->listener->$method($event);

        $this->assertEquals(
            [
                'name' => 'original-datagrid-name',
                'query' => [
                    'select' => [
                        'new-id',
                    ],
                    'from' => [
                        'new-value',
                    ],
                ],
            ],
            $event->getConfig()->toArray()
        );
    }

    /**
     * @return array
     */
    public function buildBeforeProvider()
    {
        return [
            'audit grid' => [
                'method' => 'onAuditBuildBefore',
                'expectedDatagridName' => AuditDatagridListener::CUSTOM_AUDIT_GRID,
            ],
            'history audit grid' => [
                'method' => 'onHistoryBuildBefore',
                'expectedDatagridName' => AuditDatagridListener::CUSTOM_HISTORY_AUDIT_GRID,
            ],
        ];
    }
}
