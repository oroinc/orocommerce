<?php

namespace OroB2B\Bundle\PricingBundle\Tests\Unit\Datagrid;

use Oro\Bundle\DataGridBundle\Datagrid\Common\DatagridConfiguration;
use Oro\Bundle\DataGridBundle\Datagrid\DatagridInterface;
use Oro\Bundle\DataGridBundle\Event\BuildBefore;

use OroB2B\Bundle\PricingBundle\EventListener\DatagridListener;

class DatagridListenerTest extends \PHPUnit_Framework_TestCase
{
    const PRICE_LIST_CLASS = 'OroB2B\Bundle\PricingBundle\Entity\PriceList';

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
                            'join' => self::PRICE_LIST_CLASS,
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
                            'class' => self::PRICE_LIST_CLASS,
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
        $this->listener->setPriceListClass(self::PRICE_LIST_CLASS);
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
        $expected['source']['query']['join']['left'][0]['condition']
            = 'customer MEMBER OF priceList.accounts';
        $this->assertEquals($expected, $config->toArray());
    }

    public function testOnBuildBeforeCustomerGroups()
    {
        /** @var \PHPUnit_Framework_MockObject_MockObject|DatagridInterface $datagrid */
        $datagrid = $this->getMock('Oro\Bundle\DataGridBundle\Datagrid\DatagridInterface');
        $config = DatagridConfiguration::create([]);

        $event = new BuildBefore($datagrid, $config);
        $this->listener->onBuildBeforeAccountGroups($event);

        $expected = $this->expectedTemplate;
        $expected['source']['query']['join']['left'][0]['condition']
            = 'customer_group MEMBER OF priceList.accountGroups';
        $this->assertEquals($expected, $config->toArray());
    }
}
