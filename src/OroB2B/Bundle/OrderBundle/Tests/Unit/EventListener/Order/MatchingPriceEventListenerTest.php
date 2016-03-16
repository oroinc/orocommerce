<?php

namespace OroB2B\Bundle\OrderBundle\Tests\Unit\EventListener\Order;

use Symfony\Component\Form\FormInterface;

use Oro\Component\Testing\Unit\EntityTrait;

use OroB2B\Bundle\AccountBundle\Entity\Account;
use OroB2B\Bundle\ProductBundle\Entity\Product;
use OroB2B\Bundle\ProductBundle\Entity\ProductUnit;
use OroB2B\Bundle\WebsiteBundle\Entity\Website;
use OroB2B\Bundle\PricingBundle\Entity\BasePriceList;
use OroB2B\Bundle\PricingBundle\Model\PriceListTreeHandler;
use OroB2B\Bundle\PricingBundle\Provider\MatchingPriceProvider;
use OroB2B\Bundle\OrderBundle\Entity\Order;
use OroB2B\Bundle\OrderBundle\Entity\OrderLineItem;
use OroB2B\Bundle\OrderBundle\Event\OrderEvent;
use OroB2B\Bundle\OrderBundle\EventListener\Order\MatchingPriceEventListener;

class MatchingPriceEventListenerTest extends \PHPUnit_Framework_TestCase
{
    use EntityTrait;

    /** @var MatchingPriceEventListener */
    protected $listener;

    /** @var MatchingPriceProvider|\PHPUnit_Framework_MockObject_MockObject */
    protected $provider;

    /** @var PriceListTreeHandler|\PHPUnit_Framework_MockObject_MockObject */
    protected $priceListTreeHandler;

    /** @var FormInterface */
    protected $form;

    protected function setUp()
    {
        $this->form = $this->getMock('Symfony\Component\Form\FormInterface');

        $this->provider = $this->getMockBuilder('OroB2B\Bundle\PricingBundle\Provider\MatchingPriceProvider')
            ->disableOriginalConstructor()
            ->getMock();

        $this->priceListTreeHandler = $this->getMockBuilder('OroB2B\Bundle\PricingBundle\Model\PriceListTreeHandler')
            ->disableOriginalConstructor()
            ->getMock();

        $this->listener = new MatchingPriceEventListener($this->provider, $this->priceListTreeHandler);
    }

    protected function tearDown()
    {
        unset($this->listener, $this->priceListTreeHandler, $this->provider, $this->form);
    }

    public function testOnOrderEvent()
    {
        $lineItemQuantity = 5;
        $productUnitCode = 'code';
        $lineItemCurrency = 'USD';
        $orderCurrency = 'EUR';

        $account = new Account();
        $website = new Website();

        /** @var Product $product */
        $product = $this->getEntity('OroB2B\Bundle\ProductBundle\Entity\Product', ['id' => 1]);

        $lineItem = new OrderLineItem();
        $lineItem->setProduct($product);
        $lineItem->setQuantity($lineItemQuantity);
        $lineItem->setCurrency($lineItemCurrency);

        $productUnit = new ProductUnit();
        $productUnit->setCode($productUnitCode);
        $lineItem->setProductUnit($productUnit);

        $product2 = new Product();
        $lineItem2 = new OrderLineItem();
        $lineItem2->setQuantity($lineItemQuantity);
        $lineItem2->setProduct($product2);

        $order = new Order();
        $order
            ->setCurrency($orderCurrency)
            ->setAccount($account)
            ->setWebsite($website)
            ->addLineItem($lineItem)
            ->addLineItem($lineItem2);

        $priceList = new BasePriceList();
        $this->priceListTreeHandler
            ->expects($this->once())
            ->method('getPriceList')
            ->with($account, $website)
            ->willReturn($priceList);

        $expectedLineItemsArray = [
            [
                'product' => $product->getId(),
                'unit' => $productUnitCode,
                'qty' => $lineItemQuantity,
                'currency' => $lineItemCurrency
            ],
            [
                'product' => null,
                'unit' => null,
                'qty' => $lineItemQuantity,
                'currency' => $orderCurrency
            ],
        ];

        $matchedPrices = ['matched', 'prices'];
        $this->provider
            ->expects($this->once())
            ->method('getMatchingPrices')
            ->with($expectedLineItemsArray, $priceList)
            ->willReturn($matchedPrices);

        $event = new OrderEvent($this->form, $order);
        $this->listener->onOrderEvent($event);

        $actualResult = $event->getData()->getArrayCopy();
        $this->assertArrayHasKey(MatchingPriceEventListener::MATCHED_PRICES_KEY, $actualResult);
        $this->assertEquals([MatchingPriceEventListener::MATCHED_PRICES_KEY => $matchedPrices], $actualResult);
    }
}
