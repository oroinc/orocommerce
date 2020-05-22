<?php

namespace Oro\Bundle\PricingBundle\Tests\Unit\Datagrid;

use Oro\Bundle\DataGridBundle\Datagrid\Common\DatagridConfiguration;
use Oro\Bundle\DataGridBundle\Datagrid\DatagridInterface;
use Oro\Bundle\DataGridBundle\Event\BuildBefore;
use Oro\Bundle\PricingBundle\EventListener\DatagridListener;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;

class DatagridListenerTest extends \PHPUnit\Framework\TestCase
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
                        'field_type' => EntityType::class,
                        'field_options' => [
                            'class' => 'Oro\Bundle\PricingBundle\Entity\PriceList',
                            'choice_label' => 'name',
                        ]
                    ]
                ]
            ]
        ]
    ];

    protected function setUp(): void
    {
        $this->listener = new DatagridListener();
    }

    protected function tearDown(): void
    {
        unset($this->listener);
    }

    public function testOnBuildBeforeCustomers()
    {
        /** @var \PHPUnit\Framework\MockObject\MockObject|DatagridInterface $datagrid */
        $datagrid = $this->createMock('Oro\Bundle\DataGridBundle\Datagrid\DatagridInterface');
        $config = DatagridConfiguration::create([]);

        $event = new BuildBefore($datagrid, $config);
        $this->listener->onBuildBeforeCustomers($event);

        $expected = $this->expectedTemplate;
        $expected['source']['query']['join']['left'] = [
            [
                'join' => 'Oro\Bundle\PricingBundle\Entity\PriceListToCustomer',
                'alias' => 'priceListToCustomer',
                'conditionType' => 'WITH',
                'condition' => 'priceListToCustomer.customer = customer',
            ],
            [
                'join' => 'priceListToCustomer.priceList',
                'alias' => 'priceList',
                'conditionType' => 'WITH',
                'condition' => 'priceListToCustomer.priceList = priceList',
            ],
        ];
        $this->assertEquals($expected, $config->toArray());
    }

    public function testOnBuildBeforeCustomerGroups()
    {
        /** @var \PHPUnit\Framework\MockObject\MockObject|DatagridInterface $datagrid */
        $datagrid = $this->createMock('Oro\Bundle\DataGridBundle\Datagrid\DatagridInterface');
        $config = DatagridConfiguration::create([]);

        $event = new BuildBefore($datagrid, $config);
        $this->listener->onBuildBeforeCustomerGroups($event);

        $expected = $this->expectedTemplate;
        $expected['source']['query']['join']['left'] = [
            [
                'join' => 'Oro\Bundle\PricingBundle\Entity\PriceListToCustomerGroup',
                'alias' => 'priceListToCustomerGroup',
                'conditionType' => 'WITH',
                'condition' => 'priceListToCustomerGroup.customerGroup = customer_group',
            ],
            [
                'join' => 'priceListToCustomerGroup.priceList',
                'alias' => 'priceList',
                'conditionType' => 'WITH',
                'condition' => 'priceListToCustomerGroup.priceList = priceList',
            ],
        ];
        $this->assertEquals($expected, $config->toArray());
    }
}
