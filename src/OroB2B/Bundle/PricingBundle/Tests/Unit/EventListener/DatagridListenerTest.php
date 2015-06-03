<?php

namespace OroB2B\Bundle\PricingBundle\Tests\Unit\Datagrid;

use Oro\Bundle\DataGridBundle\Datagrid\Common\DatagridConfiguration;
use Oro\Bundle\DataGridBundle\Event\BuildBefore;

use OroB2B\Bundle\PricingBundle\EventListener\DatagridListener;

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
                    'left' => [
                        [
                            'join' => 'OroB2BPricingBundle:PriceList',
                            'alias' => 'priceList',
                            'conditionType' => 'WITH',
                        ]
                    ]
                ],
            ],
        ],
        'columns' => [
            DatagridListener::PRICE_COLUMN => [
                'label' => 'orob2b.pricing.pricelist.entity_label'
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
                            'class' => 'OroB2BPricingBundle:PriceList',
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

    public function testOnBuildBeforeCustomers()
    {
        $datagrid = $this->getMock('Oro\Bundle\DataGridBundle\Datagrid\DatagridInterface');
        $config = DatagridConfiguration::create([]);

        $event = new BuildBefore($datagrid, $config);
        $this->listener->onBuildBeforeCustomers($event);

        $expected = $this->expectedTemplate;
        $expected['source']['query']['join']['left'][0]['condition']
            = 'customer MEMBER OF priceList.customers';
        $this->assertEquals($expected, $config->toArray());
    }

    public function testOnBuildBeforeCustomerGroups()
    {
        $datagrid = $this->getMock('Oro\Bundle\DataGridBundle\Datagrid\DatagridInterface');
        $config = DatagridConfiguration::create([]);

        $event = new BuildBefore($datagrid, $config);
        $this->listener->onBuildBeforeCustomerGroups($event);

        $expected = $this->expectedTemplate;
        $expected['source']['query']['join']['left'][0]['condition']
            = 'customer_group MEMBER OF priceList.customerGroups';
        $this->assertEquals($expected, $config->toArray());
    }
}
