<?php

namespace OroB2B\Bundle\AccountBundle\Tests\Unit\EventListener;

use Oro\Bundle\DataGridBundle\Datagrid\Common\DatagridConfiguration;
use Oro\Bundle\DataGridBundle\Datagrid\DatagridInterface;
use Oro\Bundle\DataGridBundle\Event\BuildBefore;

use OroB2B\Bundle\AccountBundle\EventListener\DatagridListener;

class DatagridListenerTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var DatagridListener
     */
    protected $listener;

    /**
     * @var array
     */
    protected $expectedTemplate = [
        'source' => [
            'query' => [
                'select' => ['accountUserRole.label as account_user_role_label'],
                'join' => [
                    'left' => [
                        [
                            'join' => 'OroB2BAccountBundle:AccountUserRole',
                            'alias' => 'accountUserRole',
                            'conditionType' => 'WITH',
                        ]
                    ]
                ],
            ],
        ],
        'columns' => [
            DatagridListener::ACCOUNT_USER_ROLE_COLUMN => [
                'label' => 'orob2b.account.accountuserrole.entity_label'
            ],
        ],
        'sorters' => [
            'columns' => [
                DatagridListener::ACCOUNT_USER_ROLE_COLUMN => [
                    'data_name' => 'account_user_role_label'
                ],
            ],
        ],
        'filters' => [
            'columns' => [
                DatagridListener::ACCOUNT_USER_ROLE_COLUMN => [
                    'type' => 'entity',
                    'data_name' => 'accountUserRole.id',
                    'options' => [
                        'field_type' => 'entity',
                        'field_options' => [
                            'class' => 'OroB2BAccountBundle:AccountUserRole',
                            'property' => 'label',
                        ]
                    ]
                ]
            ]
        ]
    ];

    protected function setUp()
    {
        $this->listener = new DatagridListener();
    }

    protected function tearDown()
    {
        unset($this->listener);
    }

    public function testOnBuildBeforeWebsites()
    {
        /** @var \PHPUnit_Framework_MockObject_MockObject|DatagridInterface $datagrid */
        $datagrid = $this->getMock('Oro\Bundle\DataGridBundle\Datagrid\DatagridInterface');
        $config = DatagridConfiguration::create([]);

        $event = new BuildBefore($datagrid, $config);
        $this->listener->onBuildBeforeWebsites($event);

        $expected = $this->expectedTemplate;
        $expected['source']['query']['join']['left'][0]['condition']
            = 'website MEMBER OF accountUserRole.websites';
        $this->assertEquals($expected, $config->toArray());
    }
}
