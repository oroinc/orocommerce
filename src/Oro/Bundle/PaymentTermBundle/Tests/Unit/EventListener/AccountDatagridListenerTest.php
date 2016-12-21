<?php

namespace Oro\Bundle\PaymentTermBundle\Tests\Unit\Datagrid;

use Oro\Bundle\CustomerBundle\Entity\Account;
use Oro\Bundle\CustomerBundle\Entity\AccountGroup;
use Oro\Bundle\DataGridBundle\Datagrid\Common\DatagridConfiguration;
use Oro\Bundle\DataGridBundle\Datagrid\DatagridInterface;
use Oro\Bundle\DataGridBundle\Event\BuildBefore;
use Oro\Bundle\EntityExtendBundle\Grid\DynamicFieldsExtension;
use Oro\Bundle\PaymentTermBundle\EventListener\AccountDatagridListener;
use Oro\Bundle\PaymentTermBundle\Provider\PaymentTermAssociationProvider;

class AccountDatagridListenerTest extends \PHPUnit_Framework_TestCase
{
    /** @var AccountDatagridListener */
    private $listener;

    /** @var PaymentTermAssociationProvider|\PHPUnit_Framework_MockObject_MockObject */
    private $associationProvider;

    protected function setUp()
    {
        $this->associationProvider = $this->getMockBuilder(PaymentTermAssociationProvider::class)
            ->disableOriginalConstructor()->getMock();

        $this->listener = new AccountDatagridListener($this->associationProvider);
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

    public function testOnBuildBeforeWithoutExtendClassNotAccount()
    {
        /** @var \PHPUnit_Framework_MockObject_MockObject|DatagridInterface $datagrid */
        $datagrid = $this->createMock(DatagridInterface::class);
        $config = DatagridConfiguration::create(
            [DynamicFieldsExtension::EXTEND_ENTITY_CONFIG_PATH => \stdClass::class]
        );

        $this->associationProvider->expects($this->never())->method($this->anything());

        $event = new BuildBefore($datagrid, $config);
        $this->listener->onBuildBefore($event);

        $this->assertEquals(
            [DynamicFieldsExtension::EXTEND_ENTITY_CONFIG_PATH => \stdClass::class],
            $config->toArray()
        );
    }

    public function testOnBuildBeforeWithoutAssociationNames()
    {
        /** @var \PHPUnit_Framework_MockObject_MockObject|DatagridInterface $datagrid */
        $datagrid = $this->createMock(DatagridInterface::class);
        $config = DatagridConfiguration::create(
            [DynamicFieldsExtension::EXTEND_ENTITY_CONFIG_PATH => Account::class]
        );

        $this->associationProvider->expects($this->once())->method('getAssociationNames')->willReturn([]);

        $event = new BuildBefore($datagrid, $config);
        $this->listener->onBuildBefore($event);

        $this->assertEquals(
            [DynamicFieldsExtension::EXTEND_ENTITY_CONFIG_PATH => Account::class],
            $config->toArray()
        );
    }

    public function testOnBuildBeforeWithoutGroupAssociationNames()
    {
        /** @var \PHPUnit_Framework_MockObject_MockObject|DatagridInterface $datagrid */
        $datagrid = $this->createMock(DatagridInterface::class);
        $config = DatagridConfiguration::create(
            [DynamicFieldsExtension::EXTEND_ENTITY_CONFIG_PATH => Account::class]
        );

        $this->associationProvider->expects($this->exactly(2))->method('getAssociationNames')
            ->withConsecutive(
                [Account::class],
                [AccountGroup::class]
            )
            ->willReturnOnConsecutiveCalls(['accountPaymentTerm'], []);

        $event = new BuildBefore($datagrid, $config);
        $this->listener->onBuildBefore($event);

        $this->assertEquals(
            [DynamicFieldsExtension::EXTEND_ENTITY_CONFIG_PATH => Account::class],
            $config->toArray()
        );
    }

    public function testOnBuildBeforeSupportAccountGroupPaymentTermFallback()
    {
        /** @var \PHPUnit_Framework_MockObject_MockObject|DatagridInterface $datagrid */
        $datagrid = $this->createMock(DatagridInterface::class);
        $config = DatagridConfiguration::create(
            [
                DynamicFieldsExtension::EXTEND_ENTITY_CONFIG_PATH => Account::class,
                'source' => ['query' => ['from' => [['alias' => 'rootAlias']]]],
            ]
        );

        $this->associationProvider->expects($this->exactly(2))->method('getAssociationNames')
            ->withConsecutive(
                [Account::class],
                [AccountGroup::class]
            )
            ->willReturnOnConsecutiveCalls(['accountPaymentTerm'], ['accountGroupPaymentTerm']);

        $this->associationProvider->expects($this->once())->method('getTargetField')->willReturn('label');

        $event = new BuildBefore($datagrid, $config);
        $this->listener->onBuildBefore($event);


        $this->assertEquals(
            [
                DynamicFieldsExtension::EXTEND_ENTITY_CONFIG_PATH => Account::class,
                'source' => [
                    'query' => [
                        'from' => [['alias' => 'rootAlias']],
                        'join' => [
                            'left' => [
                                [
                                    'join' => 'account_group.accountGroupPaymentTerm',
                                    'alias' => 'agpt_accountGroupPaymentTerm',
                                ],
                            ],
                        ],
                        'select' => [
                            'COALESCE(agpt_accountGroupPaymentTerm.label) as account_group_payment_term',
                            'COALESCE(IDENTITY(rootAlias.accountPaymentTerm),'.
                                'agpt_accountGroupPaymentTerm.id) as accountPaymentTerm_resolved_id',
                            'COALESCE(accountPaymentTerm.label,agpt_accountGroupPaymentTerm.label)'.
                                ' as accountPaymentTerm_resolved_value',
                        ],
                    ],
                ],
                'filters' => [
                    'columns' => ['accountPaymentTerm' => ['data_name' => 'accountPaymentTerm_resolved_id']],
                ],
                'sorters' => [
                    'columns' => ['accountPaymentTerm' => ['data_name' => 'accountPaymentTerm_resolved_value']],
                ],
                'columns' => [
                    'accountPaymentTerm' => [
                        'type' => 'twig',
                        'frontend_type' => 'html',
                        'template' => 'OroPaymentTermBundle:PaymentTerm:column.html.twig',
                    ],
                ],
            ],
            $config->toArray()
        );
    }

    public function testOnBuildBeforeSupportAccountGroupPaymentTermFallbackWithMultipleGroups()
    {
        /** @var \PHPUnit_Framework_MockObject_MockObject|DatagridInterface $datagrid */
        $datagrid = $this->createMock(DatagridInterface::class);
        $config = DatagridConfiguration::create(
            [
                DynamicFieldsExtension::EXTEND_ENTITY_CONFIG_PATH => Account::class,
                'source' => ['query' => ['from' => [['alias' => 'rootAlias']]]],
            ]
        );

        $this->associationProvider->expects($this->exactly(2))->method('getAssociationNames')
            ->withConsecutive(
                [Account::class],
                [AccountGroup::class]
            )
            ->willReturnOnConsecutiveCalls(
                ['accountPaymentTerm', 'accountPaymentTerm2'],
                ['accountGroupPaymentTerm', 'accountGroupPaymentTerm2']
            );

        $this->associationProvider->expects($this->exactly(2))->method('getTargetField')->willReturn('label');

        $event = new BuildBefore($datagrid, $config);
        $this->listener->onBuildBefore($event);

        $this->assertEquals(
            [
                DynamicFieldsExtension::EXTEND_ENTITY_CONFIG_PATH => Account::class,
                'source' => [
                    'query' => [
                        'from' => [['alias' => 'rootAlias']],
                        'join' => [
                            'left' => [
                                [
                                    'join' => 'account_group.accountGroupPaymentTerm',
                                    'alias' => 'agpt_accountGroupPaymentTerm',
                                ],
                                [
                                    'join' => 'account_group.accountGroupPaymentTerm2',
                                    'alias' => 'agpt_accountGroupPaymentTerm2',
                                ],
                            ],
                        ],
                        'select' => [
                            'COALESCE(agpt_accountGroupPaymentTerm.label,agpt_accountGroupPaymentTerm2.label)'.
                                ' as account_group_payment_term',
                            'COALESCE(IDENTITY(rootAlias.accountPaymentTerm),agpt_accountGroupPaymentTerm.id,'.
                                'agpt_accountGroupPaymentTerm2.id) as accountPaymentTerm_resolved_id',
                            'COALESCE(accountPaymentTerm.label,agpt_accountGroupPaymentTerm.label,'.
                                'agpt_accountGroupPaymentTerm2.label) as accountPaymentTerm_resolved_value',
                            'COALESCE(IDENTITY(rootAlias.accountPaymentTerm2),agpt_accountGroupPaymentTerm.id,'.
                                'agpt_accountGroupPaymentTerm2.id) as accountPaymentTerm2_resolved_id',
                            'COALESCE(accountPaymentTerm2.label,agpt_accountGroupPaymentTerm.label,'.
                                'agpt_accountGroupPaymentTerm2.label) as accountPaymentTerm2_resolved_value',
                        ],
                    ],
                ],
                'filters' => [
                    'columns' => [
                        'accountPaymentTerm' => ['data_name' => 'accountPaymentTerm_resolved_id'],
                        'accountPaymentTerm2' => ['data_name' => 'accountPaymentTerm2_resolved_id'],
                    ],
                ],
                'sorters' => [
                    'columns' => [
                        'accountPaymentTerm' => ['data_name' => 'accountPaymentTerm_resolved_value'],
                        'accountPaymentTerm2' => ['data_name' => 'accountPaymentTerm2_resolved_value'],
                    ],
                ],
                'columns' => [
                    'accountPaymentTerm' => [
                        'type' => 'twig',
                        'frontend_type' => 'html',
                        'template' => 'OroPaymentTermBundle:PaymentTerm:column.html.twig',
                    ],
                    'accountPaymentTerm2' => [
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
