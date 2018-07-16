<?php

namespace Oro\Bundle\OrderBundle\Tests\Unit\EventListener\Order;

use Oro\Bundle\CustomerBundle\Entity\Customer;
use Oro\Bundle\OrderBundle\Entity\Order;
use Oro\Bundle\OrderBundle\Entity\OrderLineItem;
use Oro\Bundle\OrderBundle\Event\OrderEvent;
use Oro\Bundle\OrderBundle\EventListener\Order\TierPriceEventListener;
use Oro\Bundle\PricingBundle\Entity\BasePriceList;
use Oro\Bundle\PricingBundle\Model\PriceListTreeHandler;
use Oro\Bundle\PricingBundle\Provider\ProductPriceProviderInterface;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\WebsiteBundle\Entity\Website;
use Oro\Component\Testing\Unit\EntityTrait;
use Symfony\Component\Form\FormInterface;

class TierPriceEventListenerTest extends \PHPUnit\Framework\TestCase
{
    use EntityTrait;

    /** @var TierPriceEventListener */
    protected $listener;

    /** @var ProductPriceProviderInterface|\PHPUnit\Framework\MockObject\MockObject */
    protected $provider;

    /** @var PriceListTreeHandler|\PHPUnit\Framework\MockObject\MockObject */
    protected $priceListTreeHandler;

    /** @var FormInterface */
    protected $form;

    protected function setUp()
    {
        $this->form = $this->createMock('Symfony\Component\Form\FormInterface');

        $this->provider = $this->getMockBuilder(ProductPriceProviderInterface::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->priceListTreeHandler = $this->getMockBuilder('Oro\Bundle\PricingBundle\Model\PriceListTreeHandler')
            ->disableOriginalConstructor()
            ->getMock();

        $this->listener = new TierPriceEventListener($this->provider, $this->priceListTreeHandler);
    }

    protected function tearDown()
    {
        unset($this->listener, $this->priceListTreeHandler, $this->provider, $this->form);
    }

    public function testOnOrderEvent()
    {
        $customer = new Customer();
        $website = new Website();

        /** @var Product $product */
        $product = $this->getEntity('Oro\Bundle\ProductBundle\Entity\Product', ['id' => 1]);

        $lineItem = new OrderLineItem();
        $lineItem->setProduct($product);

        $lineItem2 = new OrderLineItem();


        $order = new Order();
        $order
            ->setCurrency('EUR')
            ->setCustomer($customer)
            ->setWebsite($website)
            ->addLineItem($lineItem)
            ->addLineItem($lineItem2);

        /** @var BasePriceList $priceList */
        $priceList = $this->getEntity('Oro\Bundle\PricingBundle\Entity\BasePriceList', ['id' => 1]);

        $this->priceListTreeHandler
            ->expects($this->once())
            ->method('getPriceList')
            ->with($customer, $website)
            ->willReturn($priceList);

        $prices = ['prices'];
        $this->provider
            ->expects($this->once())
            ->method('getPriceByPriceListIdAndProductIds')
            ->with($priceList->getId(), [$product->getId()], $order->getCurrency())
            ->willReturn($prices);

        $event = new OrderEvent($this->form, $order);
        $this->listener->onOrderEvent($event);

        $actualResult = $event->getData()->getArrayCopy();
        $this->assertArrayHasKey(TierPriceEventListener::TIER_PRICES_KEY, $actualResult);
        $this->assertEquals([TierPriceEventListener::TIER_PRICES_KEY => $prices], $actualResult);
    }
}
