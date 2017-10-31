<?php

namespace Oro\Bundle\CheckoutBundle\Tests\Unit\Model\Updater;

use Oro\Bundle\CheckoutBundle\Model\Updater\CheckoutPaymentMethodUpdater;
use Oro\Bundle\OrderBundle\Entity\Order;
use Oro\Bundle\PaymentBundle\Provider\PaymentTransactionProvider;
use Oro\Bundle\WorkflowBundle\Entity\WorkflowDefinition;
use Oro\Bundle\WorkflowBundle\Model\WorkflowData;
use Oro\Component\Testing\Unit\EntityTrait;

class CheckoutPaymentMethodUpdaterTest extends CheckoutUpdaterTestCase
{
    use EntityTrait;

    /** @var PaymentTransactionProvider|\PHPUnit_Framework_MockObject_MockObject */
    protected $paymentTransactionProvider;

    protected function setUp()
    {
        $this->paymentTransactionProvider = $this->createMock(PaymentTransactionProvider::class);

        $this->updater = new CheckoutPaymentMethodUpdater($this->paymentTransactionProvider);
    }

    public function testUpdate()
    {
        /** @var WorkflowDefinition $workflowDefinition */
        $workflowDefinition = $this->getEntity(
            WorkflowDefinition::class,
            ['exclusiveRecordGroups' => ['b2b_checkout_flow']]
        );
        $data = $this->createMock(WorkflowData::class);
        $orderPayments = ['test_payment1'];
        $data->expects($this->once())
            ->method('set')
            ->with(CheckoutPaymentMethodUpdater::PAYMENT_METHOD_ATTRIBUTE, $orderPayments[0]);
        $order = new Order();
        $this->paymentTransactionProvider
            ->expects($this->once())
            ->method('getPaymentMethods')
            ->with($order)
            ->willReturn($orderPayments);

        $this->updater->update($workflowDefinition, $data, $order);
    }

    public function testUpdateWithoutPayment()
    {
        /** @var WorkflowDefinition $workflowDefinition */
        $workflowDefinition = $this->getEntity(
            WorkflowDefinition::class,
            ['exclusiveRecordGroups' => ['b2b_checkout_flow']]
        );
        $data = $this->createMock(WorkflowData::class);
        $data->expects($this->never())
            ->method('set');
        $order = new Order();
        $this->paymentTransactionProvider
            ->expects($this->once())
            ->method('getPaymentMethods')
            ->with($order)
            ->willReturn([]);

        $this->updater->update($workflowDefinition, $data, $order);
    }
}
