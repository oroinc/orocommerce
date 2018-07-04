<?php

namespace Oro\Bundle\VisibilityBundle\Tests\Unit\Entity\EntityListener;

use Doctrine\ORM\Event\PreUpdateEventArgs;
use Oro\Bundle\CustomerBundle\Entity\Customer;
use Oro\Bundle\CustomerBundle\Entity\CustomerGroup;
use Oro\Bundle\PlatformBundle\EventListener\OptionalListenerInterface;
use Oro\Bundle\VisibilityBundle\Driver\CustomerPartialUpdateDriverInterface;
use Oro\Bundle\VisibilityBundle\Entity\EntityListener\CustomerListener;
use Oro\Bundle\VisibilityBundle\Model\MessageFactoryInterface;
use Oro\Component\MessageQueue\Client\Message;
use Oro\Component\MessageQueue\Client\MessageProducerInterface;

class CustomerListenerTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var MessageFactoryInterface|\PHPUnit\Framework\MockObject\MockObject
     */
    protected $factory;

    /**
     * @var MessageProducerInterface|\PHPUnit\Framework\MockObject\MockObject
     */
    protected $producer;

    /**
     * @var CustomerPartialUpdateDriverInterface|\PHPUnit\Framework\MockObject\MockObject
     */
    protected $driver;

    /**
     * @var Customer
     */
    protected $customer;

    /**
     * @var CustomerListener
     */
    protected $listener;

    protected function setUp()
    {
        $this->factory = $this->getMockBuilder(MessageFactoryInterface::class)
            ->getMock();
        $this->producer = $this->getMockBuilder(MessageProducerInterface::class)
            ->getMock();
        $this->driver = $this->getMockBuilder(CustomerPartialUpdateDriverInterface::class)
            ->getMock();

        $this->customer = new Customer();
        $this->listener = new CustomerListener($this->factory, $this->producer, $this->driver);
    }

    public function testPostPersistWithoutGroup()
    {
        $this->producer->expects($this->never())
            ->method('send');
        $this->driver->expects($this->once())
            ->method('createCustomerWithoutCustomerGroupVisibility')
            ->with($this->customer);

        $this->listener->postPersist($this->customer);
    }

    public function testPostPersistWithGroup()
    {
        $message = new Message();
        $this->factory->expects($this->once())
            ->method('createMessage')
            ->with($this->customer)
            ->willReturn($message);
        $this->producer->expects($this->once())
            ->method('send')
            ->with('', $message);
        $this->driver->expects($this->never())
            ->method('createCustomerWithoutCustomerGroupVisibility');

        $this->customer->setGroup(new CustomerGroup());
        $this->listener->postPersist($this->customer);
    }

    public function testPostPersistWithGroupAndDisabledListener()
    {
        $this->producer->expects($this->never())
            ->method('send');

        $this->disableListener();
        $this->customer->setGroup(new CustomerGroup());
        $this->listener->postPersist($this->customer);
    }

    public function testPreRemove()
    {
        $this->driver->expects($this->once())
            ->method('deleteCustomerVisibility')
            ->with($this->customer);

        $this->listener->preRemove($this->customer);
    }

    public function testPreUpdate()
    {
        /** @var PreUpdateEventArgs|\PHPUnit\Framework\MockObject\MockObject $args */
        $args = $this->getMockBuilder(PreUpdateEventArgs::class)
            ->disableOriginalConstructor()
            ->getMock();
        $args->expects($this->once())
            ->method('hasChangedField')
            ->with('group')
            ->willReturn(true);

        $message = new Message();
        $this->factory->expects($this->once())
            ->method('createMessage')
            ->with($this->customer)
            ->willReturn($message);
        $this->producer->expects($this->once())
            ->method('send')
            ->with('', $message);

        $this->listener->preUpdate($this->customer, $args);
    }

    public function testPreUpdateWithDisabledListener()
    {
        /* @var $args PreUpdateEventArgs|\PHPUnit\Framework\MockObject\MockObject */
        $args = $this->createMock(PreUpdateEventArgs::class);
        $args->expects($this->once())
            ->method('hasChangedField')
            ->with('group')
            ->willReturn(true);

        $this->producer->expects($this->never())
            ->method('send');

        $this->disableListener();
        $this->listener->preUpdate($this->customer, $args);
    }

    protected function disableListener()
    {
        $this->assertInstanceOf(OptionalListenerInterface::class, $this->listener);
        $this->listener->setEnabled(false);
    }
}
