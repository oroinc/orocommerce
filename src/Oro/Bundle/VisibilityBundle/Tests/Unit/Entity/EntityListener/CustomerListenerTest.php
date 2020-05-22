<?php

namespace Oro\Bundle\VisibilityBundle\Tests\Unit\Entity\EntityListener;

use Doctrine\ORM\Event\PreUpdateEventArgs;
use Oro\Bundle\CustomerBundle\Entity\Customer;
use Oro\Bundle\CustomerBundle\Entity\CustomerGroup;
use Oro\Bundle\PlatformBundle\EventListener\OptionalListenerInterface;
use Oro\Bundle\VisibilityBundle\Async\Topics;
use Oro\Bundle\VisibilityBundle\Driver\CustomerPartialUpdateDriverInterface;
use Oro\Bundle\VisibilityBundle\Entity\EntityListener\CustomerListener;
use Oro\Component\MessageQueue\Client\MessageProducerInterface;
use Oro\Component\Testing\Unit\EntityTrait;

class CustomerListenerTest extends \PHPUnit\Framework\TestCase
{
    use EntityTrait;

    /** @var MessageProducerInterface|\PHPUnit\Framework\MockObject\MockObject */
    private $messageProducer;

    /** @var CustomerPartialUpdateDriverInterface|\PHPUnit\Framework\MockObject\MockObject */
    private $driver;

    /** @var CustomerListener */
    private $listener;

    protected function setUp(): void
    {
        $this->messageProducer = $this->createMock(MessageProducerInterface::class);
        $this->driver = $this->createMock(CustomerPartialUpdateDriverInterface::class);

        $this->listener = new CustomerListener($this->messageProducer, $this->driver);
    }

    private function disableListener()
    {
        $this->assertInstanceOf(OptionalListenerInterface::class, $this->listener);
        $this->listener->setEnabled(false);
    }

    public function testPostPersistWithoutGroup()
    {
        /** @var Customer $customer */
        $customer = $this->getEntity(Customer::class, ['id' => 123]);

        $this->messageProducer->expects($this->never())
            ->method('send');
        $this->driver->expects($this->once())
            ->method('createCustomerWithoutCustomerGroupVisibility')
            ->with($this->identicalTo($customer));

        $this->listener->postPersist($customer);
    }

    public function testPostPersistWithGroup()
    {
        $customerId = 123;
        /** @var Customer $customer */
        $customer = $this->getEntity(Customer::class, ['id' => $customerId]);

        $this->messageProducer->expects($this->once())
            ->method('send')
            ->with(Topics::CHANGE_CUSTOMER, ['id' => $customerId]);
        $this->driver->expects($this->never())
            ->method('createCustomerWithoutCustomerGroupVisibility');

        $customer->setGroup(new CustomerGroup());
        $this->listener->postPersist($customer);
    }

    public function testPostPersistWithGroupAndDisabledListener()
    {
        /** @var Customer $customer */
        $customer = $this->getEntity(Customer::class, ['id' => 123]);

        $this->messageProducer->expects($this->never())
            ->method('send');

        $this->disableListener();
        $customer->setGroup(new CustomerGroup());
        $this->listener->postPersist($customer);
    }

    public function testPreRemove()
    {
        /** @var Customer $customer */
        $customer = $this->getEntity(Customer::class, ['id' => 123]);

        $this->driver->expects($this->once())
            ->method('deleteCustomerVisibility')
            ->with($this->identicalTo($customer));

        $this->listener->preRemove($customer);
    }

    public function testPreUpdate()
    {
        $customerId = 123;
        /** @var Customer $customer */
        $customer = $this->getEntity(Customer::class, ['id' => $customerId]);

        $args = $this->createMock(PreUpdateEventArgs::class);
        $args->expects($this->once())
            ->method('hasChangedField')
            ->with('group')
            ->willReturn(true);

        $this->messageProducer->expects($this->once())
            ->method('send')
            ->with(Topics::CHANGE_CUSTOMER, ['id' => $customerId]);

        $this->listener->preUpdate($customer, $args);
    }

    public function testPreUpdateWithDisabledListener()
    {
        /** @var Customer $customer */
        $customer = $this->getEntity(Customer::class, ['id' => 123]);

        $args = $this->createMock(PreUpdateEventArgs::class);
        $args->expects($this->once())
            ->method('hasChangedField')
            ->with('group')
            ->willReturn(true);

        $this->messageProducer->expects($this->never())
            ->method('send');

        $this->disableListener();
        $this->listener->preUpdate($customer, $args);
    }
}
