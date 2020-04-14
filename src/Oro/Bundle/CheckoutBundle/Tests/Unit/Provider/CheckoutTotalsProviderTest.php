<?php

namespace Oro\Bundle\CheckoutBundle\Tests\Unit\Provider;

use Doctrine\Common\Collections\ArrayCollection;
use Oro\Bundle\CheckoutBundle\DataProvider\Converter\CheckoutToOrderConverter;
use Oro\Bundle\CheckoutBundle\Entity\Checkout;
use Oro\Bundle\CheckoutBundle\Provider\CheckoutTotalsProvider;
use Oro\Bundle\CheckoutBundle\Shipping\Method\CheckoutShippingMethodsProviderInterface;
use Oro\Bundle\CurrencyBundle\Entity\Price;
use Oro\Bundle\CustomerBundle\Entity\Customer;
use Oro\Bundle\OrderBundle\Entity\Order;
use Oro\Bundle\OrderBundle\Entity\OrderAddress;
use Oro\Bundle\OrderBundle\Entity\OrderLineItem;
use Oro\Bundle\OrganizationBundle\Entity\Organization;
use Oro\Bundle\PricingBundle\SubtotalProcessor\TotalProcessorProvider;
use Oro\Bundle\WebsiteBundle\Entity\Website;
use Oro\Component\Testing\Unit\EntityTrait;

class CheckoutTotalsProviderTest extends \PHPUnit\Framework\TestCase
{
    use EntityTrait;

    /**
     * @var CheckoutToOrderConverter|\PHPUnit\Framework\MockObject\MockObject
     */
    protected $checkoutToOrderConverter;

    /**
     * @var TotalProcessorProvider|\PHPUnit\Framework\MockObject\MockObject
     */
    protected $totalsProvider;

    /**
     * @var CheckoutTotalsProvider
     */
    protected $provider;

    /**
     * @var CheckoutShippingMethodsProviderInterface|\PHPUnit\Framework\MockObject\MockObject
     */
    protected $checkoutShippingMethodsProvider;

    protected function setUp(): void
    {
        $this->checkoutToOrderConverter = $this->createMock(CheckoutToOrderConverter::class);
        $this->totalsProvider = $this->createMock(TotalProcessorProvider::class);
        $this->checkoutShippingMethodsProvider = $this->createMock(CheckoutShippingMethodsProviderInterface::class);

        $this->provider = new CheckoutTotalsProvider(
            $this->checkoutToOrderConverter,
            $this->totalsProvider,
            $this->checkoutShippingMethodsProvider
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
        $customer = new Customer();
        $customer->setName('order customer');

        $checkout = new Checkout();

        /** @var Order $order */
        $order = $this->getEntity(
            Order::class,
            [
                'estimatedShippingCostAmount' => $price->getValue(),
                'currency' => $price->getCurrency(),
                'shippingAddress' => $address,
                'billingAddress' => $address,
                'customer' => $customer,
                'website' => $website,
                'organization' => $organization,
                'lineItems' => $lineItems,
            ]
        );

        $this->checkoutShippingMethodsProvider
            ->expects(static::once())
            ->method('getPrice')
            ->with($checkout)
            ->willReturn($price);

        $this->checkoutToOrderConverter
            ->expects(static::once())
            ->method('getOrder')
            ->with($checkout)
            ->willReturn($order);

        $this->totalsProvider
            ->expects(static::once())
            ->method('enableRecalculation');

        $totals = [
            'total' => [
                'type' => 'total',
                'label' => 'Total',
                'amount' => 10,
                'currency' => 'USD',
                'visible' => true,
                'data' => null
            ],
            'subtotals' => [
                [
                    'type' => 'subtotal',
                    'label' => 'Subtotal',
                    'amount' => 10,
                    'currency' => 'USD',
                    'visible' => true,
                    'data' => null
                ]
            ]
        ];
        $this->totalsProvider
            ->expects(static::once())
            ->method('getTotalWithSubtotalsAsArray')
            ->with($order)
            ->will(
                $this->returnCallback(
                    function (Order $order) use (
                        $lineItems,
                        $price,
                        $address,
                        $customer,
                        $website,
                        $organization,
                        $totals
                    ) {
                        static::assertEquals($lineItems, $order->getLineItems());
                        static::assertEquals($price, $order->getShippingCost());
                        static::assertSame($address, $order->getBillingAddress());
                        static::assertSame($address, $order->getShippingAddress());
                        static::assertSame($customer, $order->getCustomer());
                        static::assertSame($website, $order->getWebsite());
                        static::assertSame($organization, $order->getOrganization());

                        return $totals;
                    }
                )
            );

        $this->assertSame($totals, $this->provider->getTotalsArray($checkout));
    }
}
