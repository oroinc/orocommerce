<?php

namespace OroB2B\Bundle\PaymentBundle\Tests\Unit\Datagrid;

use Oro\Bundle\DataGridBundle\Datagrid\Common\DatagridConfiguration;
use Oro\Bundle\DataGridBundle\Datagrid\DatagridInterface;
use Oro\Bundle\DataGridBundle\Event\BuildBefore;

use OroB2B\Bundle\PaymentBundle\EventListener\DatagridListener;

class DatagridListenerTest extends \PHPUnit_Framework_TestCase
{
    const PAYMENT_TERM_CLASS = 'OroB2B\Bundle\PaymentBundle\Entity\PaymentTerm';

    /**
     * @var DatagridListener
     */
    protected $listener;

    /**
     * @var array
     */
    protected $expectedTemplateForCustomer = [
        'source' => [
            'query' => [
                'select' => [
                    "payment_term.label as payment_term_label",
                    "payment_term_group.label as payment_term_group_label",
                    "CONCAT(CASE WHEN payment_term.id IS NOT NULL THEN payment_term.id ELSE CASE WHEN payment_term_group.id IS NOT NULL THEN payment_term_group.id ELSE '' END END, '') as payment_term_for_filter"
                ],
                'join' => [
                    'left' => [
                        [
                            'join' => self::PAYMENT_TERM_CLASS,
                            'alias' => 'payment_term',
                            'conditionType' => 'WITH',
                            'condition' => 'customer MEMBER OF payment_term.customers'
                        ],
                        [
                            'join' => self::PAYMENT_TERM_CLASS,
                            'alias' => 'payment_term_group',
                            'conditionType' => 'WITH',
                            'condition' => 'customer.group MEMBER OF payment_term_group.customerGroups'
                        ]
                    ]
                ],
            ],
        ],
        'columns' => [
            DatagridListener::PAYMENT_TERM_LABEL_ALIAS => [
                'type' => 'twig',
                'label' => 'orob2b.payment.paymentterm.entity_label',
                'frontend_type' => 'html',
                'template' => 'OroB2BPaymentBundle:Customer:Datagrid/Property/paymentTerm.html.twig'
            ],
        ],
        'sorters' => [
            'columns' => [
                DatagridListener::PAYMENT_TERM_LABEL_ALIAS => [
                    'data_name' => 'payment_term_for_filter'
                ],
            ],
        ],
        'filters' => [
            'columns' => [
                DatagridListener::PAYMENT_TERM_LABEL_ALIAS=> [
                    'type' => 'entity',
                    'data_name' => 'payment_term_for_filter',
                    'options' => [
                        'field_type' => 'entity',
                        'field_options' => [
                            'class' => self::PAYMENT_TERM_CLASS,
                            'property' => 'label',
                        ]
                    ]
                ]
            ]
        ]
    ];

    /**
     * @var array
     */
    protected $expectedTemplateForCustomerGroup = [
        'source' => [
            'query' => [
                'select' => [
                    "payment_term.label as payment_term_label"
                ],
                'join' => [
                    'left' => [
                        [
                            'join' => self::PAYMENT_TERM_CLASS,
                            'alias' => 'payment_term',
                            'conditionType' => 'WITH',
                            'condition' => 'customer_group MEMBER OF payment_term.customerGroups'
                        ],
                    ]
                ],
            ],
        ],
        'columns' => [
            DatagridListener::PAYMENT_TERM_LABEL_ALIAS => [
                'label' => 'orob2b.payment.paymentterm.entity_label'
            ],
        ],
        'sorters' => [
            'columns' => [
                DatagridListener::PAYMENT_TERM_LABEL_ALIAS => [
                    'data_name' => 'payment_term_label'
                ],
            ],
        ],
        'filters' => [
            'columns' => [
                DatagridListener::PAYMENT_TERM_LABEL_ALIAS=> [
                    'type' => 'entity',
                    'data_name' => 'payment_term.id',
                    'options' => [
                        'field_type' => 'entity',
                        'field_options' => [
                            'class' => self::PAYMENT_TERM_CLASS,
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
        $this->listener->setPaymentTermClass(static::PAYMENT_TERM_CLASS);
    }

    protected function tearDown()
    {
        unset($this->listener);

    }

    public function testOnBuildBeforeCustomers()
    {
        /** @var \PHPUnit_Framework_MockObject_MockObject|DatagridInterface $datagrid */
        $datagrid = $this->getMock('Oro\Bundle\DataGridBundle\Datagrid\DatagridInterface');
        $config = DatagridConfiguration::create([]);

        $event = new BuildBefore($datagrid, $config);
        $this->listener->onBuildBeforeCustomers($event);

        $expected = $this->expectedTemplateForCustomer;
        $this->assertEquals($expected, $config->toArray());
    }

    public function testOnBuildBeforeCustomerGroups()
    {
        /** @var \PHPUnit_Framework_MockObject_MockObject|DatagridInterface $datagrid */
        $datagrid = $this->getMock('Oro\Bundle\DataGridBundle\Datagrid\DatagridInterface');
        $config = DatagridConfiguration::create([]);

        $event = new BuildBefore($datagrid, $config);
        $this->listener->onBuildBeforeCustomerGroups($event);

        $expected = $this->expectedTemplateForCustomerGroup;
        $this->assertEquals($expected, $config->toArray());
    }
}
