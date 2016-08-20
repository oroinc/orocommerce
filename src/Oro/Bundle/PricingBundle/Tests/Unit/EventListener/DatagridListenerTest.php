<?php

namespace Oro\Bundle\PricingBundle\Tests\Unit\Datagrid;

use Oro\Bundle\DataGridBundle\Datagrid\Common\DatagridConfiguration;
use Oro\Bundle\DataGridBundle\Datagrid\DatagridInterface;
use Oro\Bundle\DataGridBundle\Event\BuildBefore;
use Oro\Bundle\PricingBundle\EventListener\DatagridListener;

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
                'select' => ['priceList.name as price_list_name'],
                'join' => [
                    'left' => []
                ],
            ],
        ],
        'columns' => [
            DatagridListener::PRICE_COLUMN => [
                'label' => 'oro.pricing.pricelist.entity_label'
            ],
        ],
        'sorters' => [
            'columns' => [
                DatagridListener::PRICE_COLUMN => [
                    'data_name' => 'price_list_name'
                ],
            ],
        ],
        'filters' => [
            'columns' => [
                DatagridListener::PRICE_COLUMN => [
                    'type' => 'entity',
                    'data_name' => 'priceList.id',
                    'options' => [
                        'field_type' => 'entity',
                        'field_options' => [
                            'class' => 'Oro\Bundle\PricingBundle\Entity\PriceList',
                            'property' => 'name',
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

    public function testOnBuildBeforeAccounts()
    {
        /** @var \PHPUnit_Framework_MockObject_MockObject|DatagridInterface $datagrid */
        $datagrid = $this->getMock('Oro\Bundle\DataGridBundle\Datagrid\DatagridInterface');
        $config = DatagridConfiguration::create([]);

        $event = new BuildBefore($datagrid, $config);
        $this->listener->onBuildBeforeAccounts($event);

        $expected = $this->expectedTemplate;
        $expected['source']['query']['join']['left'] = [
            [
                'join' => 'Oro\Bundle\PricingBundle\Entity\PriceListToAccount',
                'alias' => 'priceListToAccount',
                'conditionType' => 'WITH',
                'condition' => 'priceListToAccount.account = account',
            ],
            [
                'join' => 'priceListToAccount.priceList',
                'alias' => 'priceList',
                'conditionType' => 'WITH',
                'condition' => 'priceListToAccount.priceList = priceList',
            ],
        ];
        $this->assertEquals($expected, $config->toArray());
    }

    public function testOnBuildBeforeAccountGroups()
    {
        /** @var \PHPUnit_Framework_MockObject_MockObject|DatagridInterface $datagrid */
        $datagrid = $this->getMock('Oro\Bundle\DataGridBundle\Datagrid\DatagridInterface');
        $config = DatagridConfiguration::create([]);

        $event = new BuildBefore($datagrid, $config);
        $this->listener->onBuildBeforeAccountGroups($event);

        $expected = $this->expectedTemplate;
        $expected['source']['query']['join']['left'] = [
            [
                'join' => 'Oro\Bundle\PricingBundle\Entity\PriceListToAccountGroup',
                'alias' => 'priceListToAccountGroup',
                'conditionType' => 'WITH',
                'condition' => 'priceListToAccountGroup.accountGroup = account_group',
            ],
            [
                'join' => 'priceListToAccountGroup.priceList',
                'alias' => 'priceList',
                'conditionType' => 'WITH',
                'condition' => 'priceListToAccountGroup.priceList = priceList',
            ],
        ];
        $this->assertEquals($expected, $config->toArray());
    }
}
