<?php

namespace Oro\Bundle\CheckoutBundle\Tests\Unit\EventListener;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\CheckoutBundle\Async\Topics;
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
    /**
     * @var ManagerRegistry|MockObject
     */
    private $registry;

    /**
     * @var MessageProducerInterface|MockObject
     */
    private $messageProducer;

    /**
     * @var FlatPricingCheckoutSubtotalListener
     */
    private $listener;

    protected function setUp(): void
    {
        $this->registry = $this->createMock(ManagerRegistry::class);
        $this->messageProducer = $this->createMock(MessageProducerInterface::class);

        $this->listener = new FlatPricingCheckoutSubtotalListener($this->registry, $this->messageProducer);
    }

    public function testOnPriceListUpdate()
    {
        $priceListIds = [1, 3];
        $event = new MassStorageUpdateEvent($priceListIds);

        $repo = $this->assertRepositoryCall();
        $repo->expects($this->once())
            ->method('invalidateByPriceList')
            ->with($priceListIds);
        $this->assertMqCall();

        $this->listener->onPriceListUpdate($event);
    }

    public function testOnCustomerPriceListUpdate()
    {
        $data = [['customers' => [1, 3], 'websiteId' => 5]];
        $event = new CustomerRelationUpdateEvent($data);

        $repo = $this->assertRepositoryCall();
        $repo->expects($this->once())
            ->method('invalidateByCustomers')
            ->with([1, 3], 5);
        $this->assertMqCall();

        $this->listener->onCustomerPriceListUpdate($event);
    }

    public function testOnCustomerGroupPriceListUpdate()
    {
        $data = [['customerGroups' => [1, 3], 'websiteId' => 5]];
        $event = new CustomerGroupRelationUpdateEvent($data);

        $repo = $this->assertRepositoryCall();
        $repo->expects($this->once())
            ->method('invalidateByCustomerGroups')
            ->with([1, 3], 5);
        $this->assertMqCall();

        $this->listener->onCustomerGroupPriceListUpdate($event);
    }

    public function testOnWebsitePriceListUpdate()
    {
        $websiteIds = [1, 5];
        $event = new WebsiteRelationUpdateEvent($websiteIds);

        $repo = $this->assertRepositoryCall();
        $repo->expects($this->once())
            ->method('invalidateByWebsites')
            ->with($websiteIds);
        $this->assertMqCall();

        $this->listener->onWebsitePriceListUpdate($event);
    }

    /**
     * @return CheckoutSubtotalRepository|MockObject
     */
    private function assertRepositoryCall()
    {
        $repo = $this->createMock(CheckoutSubtotalRepository::class);

        $em = $this->createMock(EntityManagerInterface::class);
        $em->expects($this->once())
            ->method('getRepository')
            ->with(CheckoutSubtotal::class)
            ->willReturn($repo);
        $this->registry->expects($this->once())
            ->method('getManagerForClass')
            ->with(CheckoutSubtotal::class)
            ->willReturn($em);

        return $repo;
    }

    private function assertMqCall()
    {
        $message = new Message();
        $this->messageProducer->expects($this->once())
            ->method('send')
            ->with(Topics::RECALCULATE_CHECKOUT_SUBTOTALS, $message);
    }
}
