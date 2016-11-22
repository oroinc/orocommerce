<?php

namespace Oro\Bundle\PaymentTermBundle\Tests\Unit\Datagrid;

use Oro\Bundle\DataGridBundle\Datagrid\Common\DatagridConfiguration;
use Oro\Bundle\DataGridBundle\Datagrid\DatagridInterface;
use Oro\Bundle\DataGridBundle\Datasource\ResultRecord;
use Oro\Bundle\DataGridBundle\Event\BuildBefore;
use Oro\Bundle\DataGridBundle\Event\OrmResultAfter;
use Oro\Bundle\EntityExtendBundle\Grid\DynamicFieldsExtension;
use Oro\Bundle\PaymentTermBundle\EventListener\DatagridListener;
use Oro\Bundle\PaymentTermBundle\Entity\PaymentTerm;
use Oro\Bundle\PaymentTermBundle\Provider\PaymentTermAssociationProvider;
use Oro\Bundle\PaymentTermBundle\Provider\PaymentTermProvider;
use Oro\Bundle\PaymentTermBundle\Tests\Unit\PaymentTermAwareStub;

class DatagridListenerTest extends \PHPUnit_Framework_TestCase
{
    /** @var DatagridListener */
    private $listener;

    /** @var PaymentTermProvider|\PHPUnit_Framework_MockObject_MockObject */
    private $provider;

    /** @var PaymentTermAssociationProvider|\PHPUnit_Framework_MockObject_MockObject */
    private $associationProvider;

    protected function setUp()
    {
        $this->provider = $this->getMockBuilder(PaymentTermProvider::class)
            ->disableOriginalConstructor()->getMock();
        $this->associationProvider = $this->getMockBuilder(PaymentTermAssociationProvider::class)
            ->disableOriginalConstructor()->getMock();

        $this->listener = new DatagridListener($this->associationProvider, $this->provider);
    }

    public function testOnBuildBeforeWithoutExtendClass()
    {
        /** @var \PHPUnit_Framework_MockObject_MockObject|DatagridInterface $datagrid */
        $datagrid = $this->getMock(DatagridInterface::class);
        $config = DatagridConfiguration::create([]);

        $this->provider->expects($this->never())->method($this->anything());
        $this->associationProvider->expects($this->never())->method($this->anything());

        $event = new BuildBefore($datagrid, $config);
        $this->listener->onBuildBefore($event);
    }

    public function testOnBuildBeforeWithoutAssociationNames()
    {
        /** @var \PHPUnit_Framework_MockObject_MockObject|DatagridInterface $datagrid */
        $datagrid = $this->getMock(DatagridInterface::class);
        $config = DatagridConfiguration::create(
            [DynamicFieldsExtension::EXTEND_ENTITY_CONFIG_PATH => \stdClass::class]
        );

        $this->provider->expects($this->never())->method($this->anything());
        $this->associationProvider->expects($this->once())->method('getAssociationNames')->willReturn([]);

        $event = new BuildBefore($datagrid, $config);
        $this->listener->onBuildBefore($event);
    }

    public function testOnBuildBeforeWithAssociationNames()
    {
        /** @var \PHPUnit_Framework_MockObject_MockObject|DatagridInterface $datagrid */
        $datagrid = $this->getMock(DatagridInterface::class);
        $config = DatagridConfiguration::create(
            [DynamicFieldsExtension::EXTEND_ENTITY_CONFIG_PATH => \stdClass::class]
        );

        $this->provider->expects($this->never())->method($this->anything());
        $this->associationProvider->expects($this->once())->method('getAssociationNames')->willReturn(['paymentTerm']);

        $event = new BuildBefore($datagrid, $config);
        $this->listener->onBuildBefore($event);

        $this->assertEquals(
            [
                'columns' => [
                    'paymentTerm' => [
                        'type' => 'twig',
                        'frontend_type' => 'html',
                        'template' => 'OroPaymentTermBundle:PaymentTerm:column.html.twig',
                    ],
                ],
                DynamicFieldsExtension::EXTEND_ENTITY_CONFIG_PATH => \stdClass::class,
            ],
            $config->toArray()
        );
    }

    public function testOnResultAfterWithoutExtendClass()
    {
        /** @var \PHPUnit_Framework_MockObject_MockObject|DatagridInterface $datagrid */
        $datagrid = $this->getMock(DatagridInterface::class);
        $config = DatagridConfiguration::create([]);
        $datagrid->expects($this->once())->method('getConfig')->willReturn($config);

        $this->provider->expects($this->never())->method($this->anything());
        $this->associationProvider->expects($this->never())->method($this->anything());

        $this->provider->expects($this->never())->method($this->anything());
        $this->associationProvider->expects($this->never())->method($this->anything());

        $event = new OrmResultAfter($datagrid, []);
        $this->listener->onResultAfter($event);
    }

    public function testOnResultAfterWithoutAssociationNames()
    {
        /** @var \PHPUnit_Framework_MockObject_MockObject|DatagridInterface $datagrid */
        $datagrid = $this->getMock(DatagridInterface::class);
        $config = DatagridConfiguration::create(
            [DynamicFieldsExtension::EXTEND_ENTITY_CONFIG_PATH => \stdClass::class]
        );
        $datagrid->expects($this->once())->method('getConfig')->willReturn($config);

        $this->provider->expects($this->never())->method($this->anything());
        $this->associationProvider->expects($this->once())->method('getAssociationNames')->willReturn([]);

        $event = new OrmResultAfter($datagrid, []);
        $this->listener->onResultAfter($event);
    }

    public function testOnResultAfterNotResultRecord()
    {
        /** @var \PHPUnit_Framework_MockObject_MockObject|DatagridInterface $datagrid */
        $datagrid = $this->getMock(DatagridInterface::class);
        $config = DatagridConfiguration::create(
            [DynamicFieldsExtension::EXTEND_ENTITY_CONFIG_PATH => \stdClass::class]
        );
        $datagrid->expects($this->once())->method('getConfig')->willReturn($config);

        $this->provider->expects($this->never())->method($this->anything());
        $this->associationProvider->expects($this->once())->method('getAssociationNames')->willReturn(['paymentTerm']);

        $event = new OrmResultAfter($datagrid, [new \stdClass()]);
        $this->listener->onResultAfter($event);
    }

    public function testOnResultAfterNotAccountOwner()
    {
        /** @var \PHPUnit_Framework_MockObject_MockObject|DatagridInterface $datagrid */
        $datagrid = $this->getMock(DatagridInterface::class);
        $config = DatagridConfiguration::create(
            [DynamicFieldsExtension::EXTEND_ENTITY_CONFIG_PATH => \stdClass::class]
        );
        $datagrid->expects($this->once())->method('getConfig')->willReturn($config);

        $this->provider->expects($this->never())->method($this->anything());
        $this->associationProvider->expects($this->once())->method('getAssociationNames')->willReturn(['paymentTerm']);

        $event = new OrmResultAfter($datagrid, [new ResultRecord([])]);
        $this->listener->onResultAfter($event);
    }

    public function testOnResultWithAccountOwner()
    {
        /** @var \PHPUnit_Framework_MockObject_MockObject|DatagridInterface $datagrid */
        $datagrid = $this->getMock(DatagridInterface::class);
        $config = DatagridConfiguration::create(
            [DynamicFieldsExtension::EXTEND_ENTITY_CONFIG_PATH => \stdClass::class]
        );
        $datagrid->expects($this->once())->method('getConfig')->willReturn($config);

        $paymentTerm = new PaymentTerm();
        $paymentTerm->setLabel('label');
        $this->provider->expects($this->once())->method('getAccountGroupPaymentTermByOwner')
            ->willReturn($paymentTerm);
        $this->associationProvider->expects($this->once())->method('getAssociationNames')->willReturn(['paymentTerm']);

        $resultRecord = new ResultRecord(['data' => 'value', new PaymentTermAwareStub()]);
        $event = new OrmResultAfter($datagrid, [$resultRecord]);
        $this->listener->onResultAfter($event);

        $this->assertEquals('label', $resultRecord->getValue('account_group_payment_term'));
    }
}
