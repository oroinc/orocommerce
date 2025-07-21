<?php

namespace Oro\Bundle\VisibilityBundle\Tests\Unit\EventListener;

use Doctrine\ORM\EntityRepository;
use Doctrine\Persistence\ObjectManager;
use Oro\Bundle\CustomerBundle\Entity\Customer;
use Oro\Bundle\PlatformBundle\Tests\Unit\EventListener\DemoDataFixturesListenerTestCase;
use Oro\Bundle\VisibilityBundle\Driver\CustomerPartialUpdateDriverInterface;
use Oro\Bundle\VisibilityBundle\EventListener\ChangeVisibilityDemoDataFixturesListener;
use PHPUnit\Framework\MockObject\MockObject;

class ChangeVisibilityDemoDataFixturesListenerTest extends DemoDataFixturesListenerTestCase
{
    private CustomerPartialUpdateDriverInterface&MockObject $partialUpdateDriver;
    private ObjectManager&MockObject $objectManager;
    private EntityRepository&MockObject $entityRepository;

    #[\Override]
    protected function setUp(): void
    {
        $this->partialUpdateDriver = $this->createMock(CustomerPartialUpdateDriverInterface::class);
        $this->objectManager = $this->createMock(ObjectManager::class);
        $this->entityRepository = $this->createMock(EntityRepository::class);

        parent::setUp();
    }

    #[\Override]
    protected function getListener()
    {
        return new ChangeVisibilityDemoDataFixturesListener(
            $this->listenerManager,
            $this->partialUpdateDriver
        );
    }

    public function testOnPostLoadForNotDemoFixtures(): void
    {
        $this->event->expects($this->once())
            ->method('isDemoFixtures')
            ->willReturn(false);

        $this->listenerManager->expects($this->never())
            ->method('enableListeners');

        $this->event->expects($this->never())
            ->method('log');

        $this->event->expects($this->never())
            ->method('getObjectManager');

        $this->objectManager->expects($this->never())
            ->method('getRepository');

        $this->entityRepository->expects($this->never())
            ->method('findAll');

        $this->partialUpdateDriver->expects($this->never())
            ->method('updateCustomerVisibility');

        $this->listener->onPostLoad($this->event);
    }

    #[\Override]
    public function testOnPostLoad(): void
    {
        $customer1 = $this->getEntity(Customer::class, ['id' => 1]);
        $customer2 = $this->getEntity(Customer::class, ['id' => 2]);

        $this->event->expects($this->once())
            ->method('isDemoFixtures')
            ->willReturn(true);

        $this->listenerManager->expects($this->once())
            ->method('enableListeners')
            ->with(self::LISTENERS);

        $this->event->expects($this->once())
            ->method('log')
            ->with('updating visibility for all customers');

        $this->event->expects($this->once())
            ->method('getObjectManager')
            ->willReturn($this->objectManager);

        $this->objectManager->expects($this->once())
            ->method('getRepository')
            ->with(Customer::class)
            ->willReturn($this->entityRepository);

        $this->entityRepository->expects($this->once())
            ->method('findAll')
            ->willReturn([$customer1, $customer2]);

        $this->partialUpdateDriver->expects($this->exactly(2))
            ->method('updateCustomerVisibility')
            ->withConsecutive([$customer1], [$customer2]);

        $this->listener->onPostLoad($this->event);
    }
}
