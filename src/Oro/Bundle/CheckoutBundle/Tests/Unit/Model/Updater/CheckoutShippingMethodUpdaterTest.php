<?php

namespace Oro\Bundle\CheckoutBundle\Tests\Unit\Model\Updater;

use Oro\Bundle\CheckoutBundle\Model\Updater\CheckoutShippingMethodUpdater;
use Oro\Bundle\OrderBundle\Entity\Order;
use Oro\Bundle\WorkflowBundle\Entity\WorkflowDefinition;
use Oro\Bundle\WorkflowBundle\Model\WorkflowData;
use Oro\Component\Testing\Unit\EntityTrait;

class CheckoutShippingMethodUpdaterTest extends CheckoutUpdaterTestCase
{
    use EntityTrait;

    /**
     * {@inheritDoc}
     */
    protected function setUp()
    {
        $this->updater = new CheckoutShippingMethodUpdater();
    }

    public function testUpdate()
    {
        /* @var $order Order */
        $order = $this->getEntity(
            Order::class,
            [
                'shippingMethod' => 'flat_rate',
                'shippingMethodType' => 'primary',
            ]
        );

        $data = new WorkflowData();

        $this->updater->update(new WorkflowDefinition(), $data, $order);

        $expectedData = new WorkflowData();
        $expectedData->set(CheckoutShippingMethodUpdater::SHIPPING_METHOD_ATTRIBUTE, 'flat_rate');
        $expectedData->set(CheckoutShippingMethodUpdater::SHIPPING_METHOD_TYPE_ATTRIBUTE, 'primary');

        $this->assertEquals($expectedData, $data);
    }

    public function testUpdateWithoutShippingMethod()
    {
        /* @var $order Order */
        $order = $this->getEntity(
            Order::class,
            [
                'shippingMethodType' => 'primary',
            ]
        );

        $data = new WorkflowData();

        $this->updater->update(new WorkflowDefinition(), $data, $order);

        $this->assertEquals(new WorkflowData(), $data);
    }

    public function testUpdateWithoutShippingMethodType()
    {
        /* @var $order Order */
        $order = $this->getEntity(
            Order::class,
            [
                'shippingMethod' => 'flat_rate',
            ]
        );

        $data = new WorkflowData();

        $this->updater->update(new WorkflowDefinition(), $data, $order);

        $this->assertEquals(new WorkflowData(), $data);
    }
}
