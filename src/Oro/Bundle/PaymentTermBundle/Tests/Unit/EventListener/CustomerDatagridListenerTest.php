<?php

namespace Oro\Bundle\PaymentTermBundle\Tests\Unit\Datagrid;

use Oro\Bundle\CustomerBundle\Entity\Customer;
use Oro\Bundle\CustomerBundle\Entity\CustomerGroup;
use Oro\Bundle\DataGridBundle\Datagrid\Common\DatagridConfiguration;
use Oro\Bundle\DataGridBundle\Datagrid\DatagridInterface;
use Oro\Bundle\DataGridBundle\Event\BuildBefore;
use Oro\Bundle\PaymentTermBundle\EventListener\CustomerDatagridListener;
use Oro\Bundle\PaymentTermBundle\Provider\PaymentTermAssociationProvider;

class CustomerDatagridListenerTest extends \PHPUnit_Framework_TestCase
{
    /** @var CustomerDatagridListener */
    private $listener;

    /** @var PaymentTermAssociationProvider|\PHPUnit_Framework_MockObject_MockObject */
    private $associationProvider;

    protected function setUp()
    {
        $this->associationProvider = $this->getMockBuilder(PaymentTermAssociationProvider::class)
            ->disableOriginalConstructor()->getMock();

        $this->listener = new CustomerDatagridListener($this->associationProvider);
    }

    public function testOnBuildBeforeWithoutExtendClass()
    {
        /** @var \PHPUnit_Framework_MockObject_MockObject|DatagridInterface $datagrid */
        $datagrid = $this->createMock(DatagridInterface::class);
        $config = DatagridConfiguration::create([]);

        $this->associationProvider->expects($this->never())->method($this->anything());

        $event = new BuildBefore($datagrid, $config);
        $this->listener->onBuildBefore($event);

        $this->assertEquals([], $config->toArray());
    }

    public function testOnBuildBeforeWithoutExtendClassNotCustomer()
    {
        /** @var \PHPUnit_Framework_MockObject_MockObject|DatagridInterface $datagrid */
        $datagrid = $this->createMock(DatagridInterface::class);
        $config = DatagridConfiguration::create(
            ['extended_entity_name' => \stdClass::class]
        );

        $this->associationProvider->expects($this->never())->method($this->anything());

        $event = new BuildBefore($datagrid, $config);
        $this->listener->onBuildBefore($event);

        $this->assertEquals(
            ['extended_entity_name' => \stdClass::class],
            $config->toArray()
        );
    }

    public function testOnBuildBeforeWithoutAssociationNames()
    {
        /** @var \PHPUnit_Framework_MockObject_MockObject|DatagridInterface $datagrid */
        $datagrid = $this->createMock(DatagridInterface::class);
        $config = DatagridConfiguration::create(
            ['extended_entity_name' => Customer::class]
        );

        $this->associationProvider->expects($this->once())->method('getAssociationNames')->willReturn([]);

        $event = new BuildBefore($datagrid, $config);
        $this->listener->onBuildBefore($event);

        $this->assertEquals(
            ['extended_entity_name' => Customer::class],
            $config->toArray()
        );
    }

    public function testOnBuildBeforeWithoutGroupAssociationNames()
    {
        /** @var \PHPUnit_Framework_MockObject_MockObject|DatagridInterface $datagrid */
        $datagrid = $this->createMock(DatagridInterface::class);
        $config = DatagridConfiguration::create(
            ['extended_entity_name' => Customer::class]
        );

        $this->associationProvider->expects($this->exactly(2))->method('getAssociationNames')
            ->withConsecutive(
                [Customer::class],
                [CustomerGroup::class]
            )
            ->willReturnOnConsecutiveCalls(['customerPaymentTerm'], []);

        $event = new BuildBefore($datagrid, $config);
        $this->listener->onBuildBefore($event);

        $this->assertEquals(
            ['extended_entity_name' => Customer::class],
            $config->toArray()
        );
    }

    public function testOnBuildBeforeSupportCustomerGroupPaymentTermFallback()
    {
        /** @var \PHPUnit_Framework_MockObject_MockObject|DatagridInterface $datagrid */
        $datagrid = $this->createMock(DatagridInterface::class);
        $config = DatagridConfiguration::create(
            [
                'extended_entity_name' => Customer::class,
                'source' => ['query' => ['from' => [['alias' => 'rootAlias']]]],
            ]
        );

        $this->associationProvider->expects($this->exactly(2))->method('getAssociationNames')
            ->withConsecutive(
                [Customer::class],
                [CustomerGroup::class]
            )
            ->willReturnOnConsecutiveCalls(['customerPaymentTerm'], ['customerGroupPaymentTerm']);

        $this->associationProvider->expects($this->once())->method('getTargetField')->willReturn('label');

        $event = new BuildBefore($datagrid, $config);
        $this->listener->onBuildBefore($event);


        $this->assertEquals(
            [
                'extended_entity_name' => Customer::class,
                'source' => [
                    'query' => [
                        'from' => [['alias' => 'rootAlias']],
                        'join' => [
                            'left' => [
                                [
                                    'join' => 'customer_group.customerGroupPaymentTerm',
                                    'alias' => 'agpt_customerGroupPaymentTerm',
                                ],
                            ],
                        ],
                        'select' => [
                            'COALESCE(agpt_customerGroupPaymentTerm.label) as customer_group_payment_term',
                            'COALESCE(IDENTITY(rootAlias.customerPaymentTerm),'.
                                'agpt_customerGroupPaymentTerm.id) as customerPaymentTerm_resolved_id',
                            'COALESCE(auto_rel_1.label,agpt_customerGroupPaymentTerm.label)'.
                                ' as customerPaymentTerm_resolved_value',
                        ],
                    ],
                ],
                'filters' => [
                    'columns' => ['customerPaymentTerm' => ['data_name' => 'customerPaymentTerm_resolved_id']],
                ],
                'sorters' => [
                    'columns' => ['customerPaymentTerm' => ['data_name' => 'customerPaymentTerm_resolved_value']],
                ],
                'columns' => [
                    'customerPaymentTerm' => [
                        'type' => 'twig',
                        'frontend_type' => 'html',
                        'template' => 'OroPaymentTermBundle:PaymentTerm:column.html.twig',
                    ],
                ],
            ],
            $config->toArray()
        );
    }

    public function testOnBuildBeforeSupportCustomerGroupPaymentTermFallbackWithMultipleGroups()
    {
        /** @var \PHPUnit_Framework_MockObject_MockObject|DatagridInterface $datagrid */
        $datagrid = $this->createMock(DatagridInterface::class);
        $config = DatagridConfiguration::create(
            [
                'extended_entity_name' => Customer::class,
                'source' => ['query' => ['from' => [['alias' => 'rootAlias']]]],
            ]
        );

        $this->associationProvider->expects($this->exactly(2))->method('getAssociationNames')
            ->withConsecutive(
                [Customer::class],
                [CustomerGroup::class]
            )
            ->willReturnOnConsecutiveCalls(
                ['customerPaymentTerm', 'customerPaymentTerm2'],
                ['customerGroupPaymentTerm', 'customerGroupPaymentTerm2']
            );

        $this->associationProvider->expects($this->exactly(2))->method('getTargetField')->willReturn('label');

        $event = new BuildBefore($datagrid, $config);
        $this->listener->onBuildBefore($event);

        $this->assertEquals(
            [
                'extended_entity_name' => Customer::class,
                'source' => [
                    'query' => [
                        'from' => [['alias' => 'rootAlias']],
                        'join' => [
                            'left' => [
                                [
                                    'join' => 'customer_group.customerGroupPaymentTerm',
                                    'alias' => 'agpt_customerGroupPaymentTerm',
                                ],
                                [
                                    'join' => 'customer_group.customerGroupPaymentTerm2',
                                    'alias' => 'agpt_customerGroupPaymentTerm2',
                                ],
                            ],
                        ],
                        'select' => [
                            'COALESCE(agpt_customerGroupPaymentTerm.label,agpt_customerGroupPaymentTerm2.label)'.
                                ' as customer_group_payment_term',
                            'COALESCE(IDENTITY(rootAlias.customerPaymentTerm),agpt_customerGroupPaymentTerm.id,'.
                                'agpt_customerGroupPaymentTerm2.id) as customerPaymentTerm_resolved_id',
                            'COALESCE(auto_rel_1.label,agpt_customerGroupPaymentTerm.label,'.
                                'agpt_customerGroupPaymentTerm2.label) as customerPaymentTerm_resolved_value',
                            'COALESCE(IDENTITY(rootAlias.customerPaymentTerm2),agpt_customerGroupPaymentTerm.id,'.
                                'agpt_customerGroupPaymentTerm2.id) as customerPaymentTerm2_resolved_id',
                            'COALESCE(auto_rel_2.label,agpt_customerGroupPaymentTerm.label,'.
                                'agpt_customerGroupPaymentTerm2.label) as customerPaymentTerm2_resolved_value',
                        ],
                    ],
                ],
                'filters' => [
                    'columns' => [
                        'customerPaymentTerm' => ['data_name' => 'customerPaymentTerm_resolved_id'],
                        'customerPaymentTerm2' => ['data_name' => 'customerPaymentTerm2_resolved_id'],
                    ],
                ],
                'sorters' => [
                    'columns' => [
                        'customerPaymentTerm' => ['data_name' => 'customerPaymentTerm_resolved_value'],
                        'customerPaymentTerm2' => ['data_name' => 'customerPaymentTerm2_resolved_value'],
                    ],
                ],
                'columns' => [
                    'customerPaymentTerm' => [
                        'type' => 'twig',
                        'frontend_type' => 'html',
                        'template' => 'OroPaymentTermBundle:PaymentTerm:column.html.twig',
                    ],
                    'customerPaymentTerm2' => [
                        'type' => 'twig',
                        'frontend_type' => 'html',
                        'template' => 'OroPaymentTermBundle:PaymentTerm:column.html.twig',
                    ],
                ],
            ],
            $config->toArray()
        );
    }
}
