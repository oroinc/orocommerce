<?php

namespace Oro\Bundle\VisibilityBundle\Tests\Unit\Entity\EntityListener;

use Doctrine\ORM\Event\PreUpdateEventArgs;
use Oro\Bundle\CustomerBundle\Entity\Customer;
use Oro\Bundle\CustomerBundle\Entity\CustomerGroup;
use Oro\Bundle\PlatformBundle\EventListener\OptionalListenerInterface;
use Oro\Bundle\VisibilityBundle\Async\Topic\VisibilityOnChangeCustomerTopic;
use Oro\Bundle\VisibilityBundle\Driver\CustomerPartialUpdateDriverInterface;
use Oro\Bundle\VisibilityBundle\Entity\EntityListener\CustomerListener;
use Oro\Component\MessageQueue\Client\MessageProducerInterface;
use Oro\Component\Testing\Unit\EntityTrait;

class CustomerListenerTest extends \PHPUnit\Framework\TestCase
{
    use EntityTrait;

    private MessageProducerInterface|\PHPUnit\Framework\MockObject\MockObject $messageProducer;

    private CustomerPartialUpdateDriverInterface|\PHPUnit\Framework\MockObject\MockObject $driver;

    private CustomerListener $listener;

    protected function setUp(): void
    {
        $this->messageProducer = $this->createMock(MessageProducerInterface::class);
        $this->driver = $this->createMock(CustomerPartialUpdateDriverInterface::class);

        $this->listener = new CustomerListener($this->messageProducer, $this->driver);
    }

    private function disableListener(): void
    {
        self::assertInstanceOf(OptionalListenerInterface::class, $this->listener);
        $this->listener->setEnabled(false);
    }

    public function testPostPersistWithoutGroup(): void
    {
        /** @var Customer $customer */
        $customer = $this->getEntity(Customer::class, ['id' => 123]);

        $this->messageProducer->expects(self::never())
            ->method('send');
        $this->driver->expects(self::once())
            ->method('createCustomerWithoutCustomerGroupVisibility')
            ->with(self::identicalTo($customer));

        $this->listener->postPersist($customer);
    }

    public function testPostPersistWithGroup(): void
    {
        $customerId = 123;
        /** @var Customer $customer */
        $customer = $this->getEntity(Customer::class, ['id' => $customerId]);

        $this->messageProducer->expects(self::once())
            ->method('send')
            ->with(VisibilityOnChangeCustomerTopic::getName(), ['id' => $customerId]);
        $this->driver->expects(self::never())
            ->method('createCustomerWithoutCustomerGroupVisibility');

        $customer->setGroup(new CustomerGroup());
        $this->listener->postPersist($customer);
    }

    public function testPostPersistWithGroupAndDisabledListener(): void
    {
        /** @var Customer $customer */
        $customer = $this->getEntity(Customer::class, ['id' => 123]);

        $this->messageProducer->expects(self::never())
            ->method('send');

        $this->disableListener();
        $customer->setGroup(new CustomerGroup());
        $this->listener->postPersist($customer);
    }

    public function testPreRemove(): void
    {
        /** @var Customer $customer */
        $customer = $this->getEntity(Customer::class, ['id' => 123]);

        $this->driver->expects(self::once())
            ->method('deleteCustomerVisibility')
            ->with(self::identicalTo($customer));

        $this->listener->preRemove($customer);
    }

    public function testPreUpdate(): void
    {
        $customerId = 123;
        /** @var Customer $customer */
        $customer = $this->getEntity(Customer::class, ['id' => $customerId]);

        $args = $this->createMock(PreUpdateEventArgs::class);
        $args->expects(self::once())
            ->method('hasChangedField')
            ->with('group')
            ->willReturn(true);

        $this->messageProducer->expects(self::once())
            ->method('send')
            ->with(VisibilityOnChangeCustomerTopic::getName(), ['id' => $customerId]);

        $this->listener->preUpdate($customer, $args);
    }

    public function testPreUpdateWithDisabledListener(): void
    {
        /** @var Customer $customer */
        $customer = $this->getEntity(Customer::class, ['id' => 123]);

        $args = $this->createMock(PreUpdateEventArgs::class);
        $args->expects(self::once())
            ->method('hasChangedField')
            ->with('group')
            ->willReturn(true);

        $this->messageProducer->expects(self::never())
            ->method('send');

        $this->disableListener();
        $this->listener->preUpdate($customer, $args);
    }
}
