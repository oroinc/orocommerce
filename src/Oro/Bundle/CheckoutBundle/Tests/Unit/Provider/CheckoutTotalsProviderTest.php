<?php

namespace Oro\Bundle\CheckoutBundle\Tests\Unit\Provider;

use Doctrine\Common\Collections\ArrayCollection;

use Oro\Bundle\CustomerBundle\Entity\Account;
use Oro\Bundle\CheckoutBundle\Mapper\MapperInterface;
use Oro\Bundle\CurrencyBundle\Entity\Price;
use Oro\Bundle\OrderBundle\Entity\OrderAddress;
use Oro\Bundle\OrderBundle\Entity\OrderLineItem;
use Oro\Bundle\OrganizationBundle\Entity\Organization;
use Oro\Bundle\WebsiteBundle\Entity\Website;
use Oro\Component\Testing\Unit\EntityTrait;
use Oro\Bundle\CheckoutBundle\DataProvider\Manager\CheckoutLineItemsManager;
use Oro\Bundle\CheckoutBundle\Entity\Checkout;
use Oro\Bundle\CheckoutBundle\Provider\CheckoutTotalsProvider;
use Oro\Bundle\OrderBundle\Entity\Order;
use Oro\Bundle\PricingBundle\SubtotalProcessor\TotalProcessorProvider;

class CheckoutTotalsProviderTest extends \PHPUnit_Framework_TestCase
{
    use EntityTrait;

    /**
     * @var CheckoutLineItemsManager|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $checkoutLineItemsManager;

    /**
     * @var TotalProcessorProvider|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $totalsProvider;

    /**
     * @var CheckoutTotalsProvider
     */
    protected $provider;

    /**
     * @var MapperInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $mapper;

    public function setUp()
    {
        $this->checkoutLineItemsManager = $this->getMockBuilder(CheckoutLineItemsManager::class)
            ->disableOriginalConstructor()->getMock();
        $this->totalsProvider = $this->getMockBuilder(TotalProcessorProvider::class)
            ->disableOriginalConstructor()->getMock();
        $this->mapper = $this->getMock(MapperInterface::class);

        $this->provider = new CheckoutTotalsProvider(
            $this->checkoutLineItemsManager,
            $this->totalsProvider,
            $this->mapper
        );
    }

    public function testGetTotalsArray()
    {
        $lineItems = new ArrayCollection([new OrderLineItem()]);
        $website = new Website();
        $organization = new Organization();
        $price = Price::create(10, 'USD');
        $address = new OrderAddress();
        $address->setLabel('order address');
        $account = new Account();
        $account->setName('order account');

        $checkout = new Checkout();

        /** @var Order $order */
        $order = $this->getEntity(
            Order::class,
            [
                'estimatedShippingCost' => $price,
                'shippingAddress' => $address,
                'billingAddress' => $address,
                'account' => $account,
                'website' => $website,
                'organization' => $organization,
                'lineItems' => $lineItems,
            ]
        );

        $this->mapper->expects($this->once())->method('map')->willReturn($order);

        $this->checkoutLineItemsManager->expects($this->once())
            ->method('getData')
            ->with($checkout)
            ->willReturn($lineItems);

        $this->totalsProvider->expects($this->once())
            ->method('enableRecalculation');

        $this->totalsProvider->expects($this->once())
            ->method('getTotalWithSubtotalsAsArray')
            ->will(
                $this->returnCallback(
                    function (Order $order) use ($lineItems, $price, $address, $account, $website, $organization) {
                        $this->assertEquals($lineItems, $order->getLineItems());
                        $this->assertSame($price, $order->getShippingCost());
                        $this->assertSame($address, $order->getBillingAddress());
                        $this->assertSame($address, $order->getShippingAddress());
                        $this->assertSame($account, $order->getAccount());
                        $this->assertSame($website, $order->getWebsite());
                        $this->assertSame($organization, $order->getOrganization());
                    }
                )
            );

        $this->provider->getTotalsArray($checkout);
    }
}
