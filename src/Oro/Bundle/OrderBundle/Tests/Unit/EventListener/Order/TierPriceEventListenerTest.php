<?php

namespace Oro\Bundle\OrderBundle\Tests\Unit\EventListener\Order;

use Symfony\Component\Form\FormInterface;

use Oro\Component\Testing\Unit\EntityTrait;
use Oro\Bundle\CustomerBundle\Entity\Account;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\WebsiteBundle\Entity\Website;
use Oro\Bundle\PricingBundle\Entity\BasePriceList;
use Oro\Bundle\PricingBundle\Model\PriceListTreeHandler;
use Oro\Bundle\OrderBundle\Entity\Order;
use Oro\Bundle\OrderBundle\Entity\OrderLineItem;
use Oro\Bundle\OrderBundle\Event\OrderEvent;
use Oro\Bundle\OrderBundle\EventListener\Order\TierPriceEventListener;
use Oro\Bundle\PricingBundle\Provider\ProductPriceProvider;

class TierPriceEventListenerTest extends \PHPUnit_Framework_TestCase
{
    use EntityTrait;

    /** @var TierPriceEventListener */
    protected $listener;

    /** @var ProductPriceProvider|\PHPUnit_Framework_MockObject_MockObject */
    protected $provider;

    /** @var PriceListTreeHandler|\PHPUnit_Framework_MockObject_MockObject */
    protected $priceListTreeHandler;

    /** @var FormInterface */
    protected $form;

    protected function setUp()
    {
        $this->form = $this->getMock('Symfony\Component\Form\FormInterface');

        $this->provider = $this->getMockBuilder('Oro\Bundle\PricingBundle\Provider\ProductPriceProvider')
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
        $account = new Account();
        $website = new Website();

        /** @var Product $product */
        $product = $this->getEntity('Oro\Bundle\ProductBundle\Entity\Product', ['id' => 1]);

        $lineItem = new OrderLineItem();
        $lineItem->setProduct($product);

        $lineItem2 = new OrderLineItem();


        $order = new Order();
        $order
            ->setCurrency('EUR')
            ->setAccount($account)
            ->setWebsite($website)
            ->addLineItem($lineItem)
            ->addLineItem($lineItem2);

        /** @var BasePriceList $priceList */
        $priceList = $this->getEntity('Oro\Bundle\PricingBundle\Entity\BasePriceList', ['id' => 1]);

        $this->priceListTreeHandler
            ->expects($this->once())
            ->method('getPriceList')
            ->with($account, $website)
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
