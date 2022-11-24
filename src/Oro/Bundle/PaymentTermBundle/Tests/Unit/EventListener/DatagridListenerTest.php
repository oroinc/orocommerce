<?php

namespace Oro\Bundle\PaymentTermBundle\Tests\Unit\EventListener;

use Oro\Bundle\DataGridBundle\Datagrid\Common\DatagridConfiguration;
use Oro\Bundle\DataGridBundle\Datagrid\DatagridInterface;
use Oro\Bundle\DataGridBundle\Datasource\ResultRecord;
use Oro\Bundle\DataGridBundle\Event\BuildBefore;
use Oro\Bundle\DataGridBundle\Event\OrmResultAfter;
use Oro\Bundle\PaymentTermBundle\Entity\PaymentTerm;
use Oro\Bundle\PaymentTermBundle\EventListener\DatagridListener;
use Oro\Bundle\PaymentTermBundle\Provider\PaymentTermAssociationProvider;
use Oro\Bundle\PaymentTermBundle\Provider\PaymentTermProvider;
use Oro\Bundle\PaymentTermBundle\Tests\Unit\PaymentTermAwareStub;

class DatagridListenerTest extends \PHPUnit\Framework\TestCase
{
    /** @var PaymentTermProvider|\PHPUnit\Framework\MockObject\MockObject */
    private $provider;

    /** @var PaymentTermAssociationProvider|\PHPUnit\Framework\MockObject\MockObject */
    private $associationProvider;

    /** @var DatagridListener */
    private $listener;

    protected function setUp(): void
    {
        $this->provider = $this->createMock(PaymentTermProvider::class);
        $this->associationProvider = $this->createMock(PaymentTermAssociationProvider::class);

        $this->listener = new DatagridListener($this->associationProvider, $this->provider);
    }

    public function testOnBuildBeforeWithoutExtendClass()
    {
        $datagrid = $this->createMock(DatagridInterface::class);
        $config = DatagridConfiguration::create([]);

        $this->provider->expects($this->never())
            ->method($this->anything());
        $this->associationProvider->expects($this->never())
            ->method($this->anything());

        $event = new BuildBefore($datagrid, $config);
        $this->listener->onBuildBefore($event);
    }

    public function testOnBuildBeforeWithoutAssociationNames()
    {
        $datagrid = $this->createMock(DatagridInterface::class);
        $config = DatagridConfiguration::create(
            ['extended_entity_name' => \stdClass::class]
        );

        $this->provider->expects($this->never())
            ->method($this->anything());
        $this->associationProvider->expects($this->once())
            ->method('getAssociationNames')
            ->willReturn([]);

        $event = new BuildBefore($datagrid, $config);
        $this->listener->onBuildBefore($event);
    }

    public function testOnBuildBeforeWithAssociationNames()
    {
        $datagrid = $this->createMock(DatagridInterface::class);
        $config = DatagridConfiguration::create(
            ['extended_entity_name' => \stdClass::class]
        );

        $this->provider->expects($this->never())
            ->method($this->anything());
        $this->associationProvider->expects($this->once())
            ->method('getAssociationNames')
            ->willReturn(['paymentTerm']);

        $event = new BuildBefore($datagrid, $config);
        $this->listener->onBuildBefore($event);

        $this->assertEquals(
            [
                'columns' => [
                    'paymentTerm' => [
                        'type' => 'twig',
                        'frontend_type' => 'html',
                        'template' => '@OroPaymentTerm/PaymentTerm/column.html.twig',
                    ],
                ],
                'extended_entity_name' => \stdClass::class,
            ],
            $config->toArray()
        );
    }

    public function testOnResultAfterWithoutExtendClass()
    {
        $datagrid = $this->createMock(DatagridInterface::class);
        $config = DatagridConfiguration::create([]);
        $datagrid->expects($this->once())
            ->method('getConfig')
            ->willReturn($config);

        $this->provider->expects($this->never())
            ->method($this->anything());
        $this->associationProvider->expects($this->never())
            ->method($this->anything());

        $this->provider->expects($this->never())
            ->method($this->anything());
        $this->associationProvider->expects($this->never())
            ->method($this->anything());

        $event = new OrmResultAfter($datagrid, []);
        $this->listener->onResultAfter($event);
    }

    public function testOnResultAfterWithoutAssociationNames()
    {
        $datagrid = $this->createMock(DatagridInterface::class);
        $config = DatagridConfiguration::create(
            ['extended_entity_name' => \stdClass::class]
        );
        $datagrid->expects($this->once())
            ->method('getConfig')
            ->willReturn($config);

        $this->provider->expects($this->never())
            ->method($this->anything());
        $this->associationProvider->expects($this->once())
            ->method('getAssociationNames')
            ->willReturn([]);

        $event = new OrmResultAfter($datagrid, []);
        $this->listener->onResultAfter($event);
    }

    public function testOnResultAfterNotResultRecord()
    {
        $datagrid = $this->createMock(DatagridInterface::class);
        $config = DatagridConfiguration::create(
            ['extended_entity_name' => \stdClass::class]
        );
        $datagrid->expects($this->once())
            ->method('getConfig')
            ->willReturn($config);

        $this->provider->expects($this->never())
            ->method($this->anything());
        $this->associationProvider->expects($this->once())
            ->method('getAssociationNames')
            ->willReturn(['paymentTerm']);

        $event = new OrmResultAfter($datagrid, [new \stdClass()]);
        $this->listener->onResultAfter($event);
    }

    public function testOnResultAfterNotCustomerOwner()
    {
        $datagrid = $this->createMock(DatagridInterface::class);
        $config = DatagridConfiguration::create(
            ['extended_entity_name' => \stdClass::class]
        );
        $datagrid->expects($this->once())
            ->method('getConfig')
            ->willReturn($config);

        $this->provider->expects($this->never())
            ->method($this->anything());
        $this->associationProvider->expects($this->once())
            ->method('getAssociationNames')
            ->willReturn(['paymentTerm']);

        $event = new OrmResultAfter($datagrid, [new ResultRecord([])]);
        $this->listener->onResultAfter($event);
    }

    public function testOnResultWithCustomerOwner()
    {
        $datagrid = $this->createMock(DatagridInterface::class);
        $config = DatagridConfiguration::create(
            ['extended_entity_name' => \stdClass::class]
        );
        $datagrid->expects($this->once())
            ->method('getConfig')
            ->willReturn($config);

        $paymentTerm = new PaymentTerm();
        $paymentTerm->setLabel('label');
        $this->provider->expects($this->once())
            ->method('getCustomerGroupPaymentTermByOwner')
            ->willReturn($paymentTerm);
        $this->associationProvider->expects($this->once())
            ->method('getAssociationNames')
            ->willReturn(['paymentTerm']);

        $resultRecord = new ResultRecord(['data' => 'value', new PaymentTermAwareStub()]);
        $event = new OrmResultAfter($datagrid, [$resultRecord]);
        $this->listener->onResultAfter($event);

        $this->assertEquals('label', $resultRecord->getValue('customer_group_payment_term'));
    }
}
