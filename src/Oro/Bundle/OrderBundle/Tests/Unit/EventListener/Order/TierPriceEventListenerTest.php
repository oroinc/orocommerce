<?php

namespace Oro\Bundle\OrderBundle\Tests\Unit\EventListener\Order;

use Oro\Bundle\CustomerBundle\Entity\Customer;
use Oro\Bundle\OrderBundle\Entity\Order;
use Oro\Bundle\OrderBundle\Entity\OrderLineItem;
use Oro\Bundle\OrderBundle\Event\OrderEvent;
use Oro\Bundle\OrderBundle\EventListener\Order\TierPriceEventListener;
use Oro\Bundle\PricingBundle\Model\ProductPriceScopeCriteria;
use Oro\Bundle\PricingBundle\Model\ProductPriceScopeCriteriaFactory;
use Oro\Bundle\PricingBundle\Model\ProductPriceScopeCriteriaFactoryInterface;
use Oro\Bundle\PricingBundle\Provider\ProductPriceProviderInterface;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\WebsiteBundle\Entity\Website;
use Oro\Component\Testing\Unit\EntityTrait;
use Symfony\Component\Form\FormInterface;

class TierPriceEventListenerTest extends \PHPUnit\Framework\TestCase
{
    use EntityTrait;

    /** @var FormInterface|\PHPUnit\Framework\MockObject\MockObject */
    private $form;

    /** @var ProductPriceProviderInterface|\PHPUnit\Framework\MockObject\MockObject */
    private $provider;

    /** @var ProductPriceScopeCriteriaFactoryInterface */
    private $priceScopeCriteriaFactory;

    /** @var TierPriceEventListener */
    private $listener;

    protected function setUp(): void
    {
        $this->form = $this->createMock(FormInterface::class);
        $this->provider = $this->createMock(ProductPriceProviderInterface::class);
        $this->priceScopeCriteriaFactory = new ProductPriceScopeCriteriaFactory();

        $this->listener = new TierPriceEventListener($this->provider, $this->priceScopeCriteriaFactory);
    }

    public function testOnOrderEvent()
    {
        $customer = new Customer();
        $website = new Website();

        $product = $this->getEntity(Product::class, ['id' => 1]);

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

        $prices = ['prices'];

        $productPriceScopeCriteria = $this->getScopeCriteriaByOrder($order);

        $this->provider->expects($this->once())
            ->method('getPricesByScopeCriteriaAndProducts')
            ->with($productPriceScopeCriteria, [$product], [$order->getCurrency()])
            ->willReturn($prices);

        $event = new OrderEvent($this->form, $order);
        $this->listener->onOrderEvent($event);

        $actualResult = $event->getData()->getArrayCopy();
        $this->assertArrayHasKey(TierPriceEventListener::TIER_PRICES_KEY, $actualResult);
        $this->assertEquals([TierPriceEventListener::TIER_PRICES_KEY => $prices], $actualResult);
    }

    private function getScopeCriteriaByOrder(Order $order) : ProductPriceScopeCriteria
    {
        $criteria = new ProductPriceScopeCriteria();
        $criteria->setWebsite($order->getWebsite());
        $criteria->setCustomer($order->getCustomer());
        $criteria->setContext($order);

        return $criteria;
    }
}
