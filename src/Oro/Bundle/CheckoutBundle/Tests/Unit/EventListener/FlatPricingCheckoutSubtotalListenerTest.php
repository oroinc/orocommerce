<?php

namespace Oro\Bundle\CheckoutBundle\Tests\Unit\EventListener;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\CheckoutBundle\Async\Topic\RecalculateCheckoutSubtotalsTopic;
use Oro\Bundle\CheckoutBundle\Entity\CheckoutSubtotal;
use Oro\Bundle\CheckoutBundle\Entity\Repository\CheckoutSubtotalRepository;
use Oro\Bundle\CheckoutBundle\EventListener\FlatPricingCheckoutSubtotalListener;
use Oro\Bundle\PricingBundle\Event\PricingStorage\CustomerGroupRelationUpdateEvent;
use Oro\Bundle\PricingBundle\Event\PricingStorage\CustomerRelationUpdateEvent;
use Oro\Bundle\PricingBundle\Event\PricingStorage\MassStorageUpdateEvent;
use Oro\Bundle\PricingBundle\Event\PricingStorage\WebsiteRelationUpdateEvent;
use Oro\Component\MessageQueue\Client\Message;
use Oro\Component\MessageQueue\Client\MessageProducerInterface;
use PHPUnit\Framework\MockObject\MockObject;

class FlatPricingCheckoutSubtotalListenerTest extends \PHPUnit\Framework\TestCase
{
    private ManagerRegistry|MockObject $registry;

    private MessageProducerInterface|MockObject $messageProducer;

    private FlatPricingCheckoutSubtotalListener $listener;

    protected function setUp(): void
    {
        $this->registry = $this->createMock(ManagerRegistry::class);
        $this->messageProducer = $this->createMock(MessageProducerInterface::class);

        $this->listener = new FlatPricingCheckoutSubtotalListener($this->registry, $this->messageProducer);
    }

    public function testOnPriceListUpdate(): void
    {
        $priceListIds = [1, 3];
        $event = new MassStorageUpdateEvent($priceListIds);

        $repo = $this->assertRepositoryCall();
        $repo->expects(self::once())
            ->method('invalidateByPriceList')
            ->with($priceListIds);
        $this->assertMqCall();

        $this->listener->onPriceListUpdate($event);
    }

    public function testOnCustomerPriceListUpdate(): void
    {
        $data = [['customers' => [1, 3], 'websiteId' => 5]];
        $event = new CustomerRelationUpdateEvent($data);

        $repo = $this->assertRepositoryCall();
        $repo->expects(self::once())
            ->method('invalidateByCustomers')
            ->with([1, 3], 5);
        $this->assertMqCall();

        $this->listener->onCustomerPriceListUpdate($event);
    }

    public function testOnCustomerGroupPriceListUpdate(): void
    {
        $data = [['customerGroups' => [1, 3], 'websiteId' => 5]];
        $event = new CustomerGroupRelationUpdateEvent($data);

        $repo = $this->assertRepositoryCall();
        $repo->expects(self::once())
            ->method('invalidateByCustomerGroups')
            ->with([1, 3], 5);
        $this->assertMqCall();

        $this->listener->onCustomerGroupPriceListUpdate($event);
    }

    public function testOnWebsitePriceListUpdate(): void
    {
        $websiteIds = [1, 5];
        $event = new WebsiteRelationUpdateEvent($websiteIds);

        $repo = $this->assertRepositoryCall();
        $repo->expects(self::once())
            ->method('invalidateByWebsites')
            ->with($websiteIds);
        $this->assertMqCall();

        $this->listener->onWebsitePriceListUpdate($event);
    }

    private function assertRepositoryCall(): CheckoutSubtotalRepository|MockObject
    {
        $repo = $this->createMock(CheckoutSubtotalRepository::class);

        $em = $this->createMock(EntityManagerInterface::class);
        $em->expects(self::once())
            ->method('getRepository')
            ->with(CheckoutSubtotal::class)
            ->willReturn($repo);
        $this->registry->expects(self::once())
            ->method('getManagerForClass')
            ->with(CheckoutSubtotal::class)
            ->willReturn($em);

        return $repo;
    }

    private function assertMqCall(): void
    {
        $message = new Message();
        $this->messageProducer->expects(self::once())
            ->method('send')
            ->with(RecalculateCheckoutSubtotalsTopic::getName(), $message);
    }
}
