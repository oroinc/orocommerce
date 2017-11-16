<?php

namespace Oro\Bundle\VisibilityBundle\Tests\Unit\EventListener;

use Doctrine\Common\Persistence\ObjectManager;
use Doctrine\ORM\EntityRepository;
use Oro\Component\Testing\Unit\EntityTrait;
use Oro\Bundle\CustomerBundle\Entity\Customer;
use Oro\Bundle\MigrationBundle\Event\MigrationDataFixturesEvent;
use Oro\Bundle\PlatformBundle\Manager\OptionalListenerManager;
use Oro\Bundle\VisibilityBundle\Driver\CustomerPartialUpdateDriverInterface;
use Oro\Bundle\VisibilityBundle\EventListener\ChangeVisibilityDemoDataFixturesListener;

class ChangeVisibilityDemoDataFixturesListenerTest extends \PHPUnit_Framework_TestCase
{
    use EntityTrait;

    /** @var OptionalListenerManager|\PHPUnit_Framework_MockObject_MockObject */
    protected $listenerManager;

    /** @var CustomerPartialUpdateDriverInterface|\PHPUnit_Framework_MockObject_MockObject */
    protected $partialUpdateDriver;

    /** @var ObjectManager|\PHPUnit_Framework_MockObject_MockObject */
    protected $objectManager;

    /** @var EntityRepository|\PHPUnit_Framework_MockObject_MockObject */
    protected $entityRepository;

    /** @var ChangeVisibilityDemoDataFixturesListener */
    protected $listener;

    /**
     * {@inheritDoc}
     */
    protected function setUp()
    {
        $this->listenerManager = $this->createMock(OptionalListenerManager::class);
        $this->partialUpdateDriver = $this->createMock(CustomerPartialUpdateDriverInterface::class);

        $this->objectManager = $this->createMock(ObjectManager::class);
        $this->entityRepository = $this->createMock(EntityRepository::class);

        $this->listener = new ChangeVisibilityDemoDataFixturesListener(
            $this->listenerManager,
            $this->partialUpdateDriver
        );
    }

    public function testOnPreLoadForNotDemoFixtures()
    {
        $event = $this->createMock(MigrationDataFixturesEvent::class);
        $event->expects($this->once())
            ->method('isDemoFixtures')
            ->willReturn(false);

        $this->listenerManager->expects($this->never())
            ->method('disableListeners');

        $this->listener->onPreLoad($event);
    }

    public function testOnPreLoadForDemoFixtures()
    {
        $event = $this->createMock(MigrationDataFixturesEvent::class);
        $event->expects($this->once())
            ->method('isDemoFixtures')
            ->willReturn(true);

        $this->listenerManager->expects($this->once())
            ->method('disableListeners')
            ->with(ChangeVisibilityDemoDataFixturesListener::LISTENERS);

        $this->listener->onPreLoad($event);
    }

    public function testOnPostLoadForNotDemoFixtures()
    {
        $event = $this->createMock(MigrationDataFixturesEvent::class);
        $event->expects($this->once())
            ->method('isDemoFixtures')
            ->willReturn(false);

        $this->listenerManager->expects($this->never())
            ->method('enableListeners');

        $event->expects($this->never())
            ->method('log');

        $event->expects($this->never())
            ->method('getObjectManager');

        $this->objectManager->expects($this->never())
            ->method('getRepository');

        $this->entityRepository->expects($this->never())
            ->method('findAll');

        $this->partialUpdateDriver->expects($this->never())
            ->method('updateCustomerVisibility');

        $this->listener->onPostLoad($event);
    }

    public function testOnPostLoadForDemoFixtures()
    {
        $customer1 = $this->getEntity(Customer::class, ['id' => 1]);
        $customer2 = $this->getEntity(Customer::class, ['id' => 2]);

        $event = $this->createMock(MigrationDataFixturesEvent::class);
        $event->expects($this->once())
            ->method('isDemoFixtures')
            ->willReturn(true);

        $this->listenerManager->expects($this->once())
            ->method('enableListeners')
            ->with(ChangeVisibilityDemoDataFixturesListener::LISTENERS);

        $event->expects($this->once())
            ->method('log')
            ->with('running changing visibility for all customers');

        $event->expects($this->once())
            ->method('getObjectManager')
            ->willReturn($this->objectManager);

        $this->objectManager->expects($this->once())
            ->method('getRepository')
            ->with(Customer::class)
            ->willReturn($this->entityRepository);

        $this->entityRepository->expects($this->once())
            ->method('findAll')
            ->willReturn([$customer1, $customer2]);

        $this->partialUpdateDriver->expects($this->at(0))
            ->method('updateCustomerVisibility')
            ->with($customer1);

        $this->partialUpdateDriver->expects($this->at(1))
            ->method('updateCustomerVisibility')
            ->with($customer2);

        $this->listener->onPostLoad($event);
    }
}
