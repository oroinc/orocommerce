<?php

namespace Oro\Bundle\CheckoutBundle\Tests\Unit\Model\Updater;

use Doctrine\Common\Persistence\ManagerRegistry;
use Doctrine\Common\Persistence\ObjectManager;
use Oro\Bundle\CheckoutBundle\Model\Updater\CheckoutAddressUpdater;
use Oro\Bundle\OrderBundle\Entity\Order;
use Oro\Bundle\OrderBundle\Entity\OrderAddress;
use Oro\Bundle\WorkflowBundle\Entity\WorkflowDefinition;
use Oro\Bundle\WorkflowBundle\Model\WorkflowData;
use Oro\Component\Duplicator\DuplicatorFactory;
use Oro\Component\Duplicator\DuplicatorInterface;
use Oro\Component\Testing\Unit\EntityTrait;

class CheckoutAddressUpdaterTest extends \PHPUnit_Framework_TestCase
{
    use EntityTrait;

    /** @var ObjectManager|\PHPUnit_Framework_MockObject_MockObject */
    protected $manager;

    /** @var ManagerRegistry|\PHPUnit_Framework_MockObject_MockObject */
    protected $registry;

    /** @var DuplicatorInterface|\PHPUnit_Framework_MockObject_MockObject */
    protected $duplicator;

    /** @var array */
    protected static $duplicatorSettings = [
        [['setNull'], ['propertyName', ['label']]]
    ];

    /** @var CheckoutAddressUpdater */
    protected $updater;

    protected function setUp()
    {
        $this->manager = $this->createMock(ObjectManager::class);
        $this->registry = $this->createMock(ManagerRegistry::class);
        $this->duplicator = $this->createMock(DuplicatorInterface::class);

        /** @var DuplicatorFactory|\PHPUnit_Framework_MockObject_MockObject $duplicatorFactory */
        $duplicatorFactory = $this->createMock(DuplicatorFactory::class);
        $duplicatorFactory->expects($this->any())
            ->method('create')
            ->willReturn($this->duplicator);

        $this->updater = new CheckoutAddressUpdater(
            $this->registry,
            $duplicatorFactory,
            self::$duplicatorSettings
        );
    }

    public function testUpdateWithoutManager()
    {
        $this->registry->expects($this->any())
            ->method('getManagerForClass')
            ->with(OrderAddress::class)
            ->willReturn(null);

        $this->duplicator->expects($this->never())
            ->method($this->anything());

        $data = new WorkflowData();

        $this->updater->update(new WorkflowDefinition(), $data, new Order());

        $this->assertEquals(new WorkflowData(), $data);
    }

    public function testUpdateWithoutAddresses()
    {
        $this->registry->expects($this->any())
            ->method('getManagerForClass')
            ->with(OrderAddress::class)
            ->willReturn($this->manager);

        $this->manager->expects($this->never())
            ->method($this->anything());

        $this->duplicator->expects($this->never())
            ->method($this->anything());

        $data = new WorkflowData();

        $this->updater->update(new WorkflowDefinition(), $data, new Order());

        $this->assertEquals(new WorkflowData(), $data);
    }

    public function testUpdate()
    {
        $billingAddress = $this->getEntity(OrderAddress::class, ['id' => 1001]);
        $shippingAddress = $this->getEntity(OrderAddress::class, ['id' => 3003]);

        $newBillingAddress = $this->getEntity(OrderAddress::class, ['id' => 2002]);
        $newShippingAddress = $this->getEntity(OrderAddress::class, ['id' => 4004]);

        $this->duplicator->expects($this->exactly(2))
            ->method('duplicate')
            ->withConsecutive(
                [$billingAddress, self::$duplicatorSettings],
                [$shippingAddress, self::$duplicatorSettings]
            )
            ->willReturnOnConsecutiveCalls(
                $newBillingAddress,
                $newShippingAddress
            );

        $this->manager->expects($this->exactly(2))
            ->method('persist')
            ->withConsecutive(
                [$newBillingAddress],
                [$newShippingAddress]
            );

        $this->registry->expects($this->any())
            ->method('getManagerForClass')
            ->with(OrderAddress::class)
            ->willReturn($this->manager);

        $data = new WorkflowData();

        /** @var Order $order */
        $order = $this->getEntity(
            Order::class,
            [
                'id' => 42,
                'billingAddress' => $billingAddress,
                'shippingAddress' => $shippingAddress,
            ]
        );

        $this->updater->update(new WorkflowDefinition(), $data, $order);

        $expectedData = new WorkflowData();
        $expectedData->set(CheckoutAddressUpdater::BILLING_ADDRESS_ATTRIBUTE, $newBillingAddress);
        $expectedData->set(CheckoutAddressUpdater::SHIPPING_ADDRESS_ATTRIBUTE, $newShippingAddress);

        $this->assertEquals($expectedData, $data);
    }

    public function testIsApplicableUnsupportedWorkflow()
    {
        $this->assertFalse($this->updater->isApplicable(new WorkflowDefinition(), new Order()));
    }

    public function testIsApplicableUnsupportedSource()
    {
        $workflow = new WorkflowDefinition();
        $workflow->setExclusiveRecordGroups(['b2b_checkout_flow']);

        $this->assertFalse($this->updater->isApplicable($workflow, new \stdClass()));
    }

    public function testIsApplicable()
    {
        $workflow = new WorkflowDefinition();
        $workflow->setExclusiveRecordGroups(['b2b_checkout_flow']);

        $this->assertTrue($this->updater->isApplicable($workflow, new Order()));
    }
}
