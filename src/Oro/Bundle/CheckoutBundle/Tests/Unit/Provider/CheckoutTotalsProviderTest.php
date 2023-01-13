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

class CheckoutTotalsProviderTest extends \PHPUnit\Framework\TestCase
{
    /** @var CheckoutToOrderConverter|\PHPUnit\Framework\MockObject\MockObject */
    private $checkoutToOrderConverter;

    /** @var TotalProcessorProvider|\PHPUnit\Framework\MockObject\MockObject */
    private $totalsProvider;

    /** @var CheckoutShippingMethodsProviderInterface|\PHPUnit\Framework\MockObject\MockObject */
    private $checkoutShippingMethodsProvider;

    /** @var CheckoutTotalsProvider */
    private $provider;

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

        $order = new Order();
        $order->setEstimatedShippingCostAmount($price->getValue());
        $order->setCurrency($price->getCurrency());
        $order->setShippingAddress($address);
        $order->setBillingAddress($address);
        $order->setCustomer($customer);
        $order->setWebsite($website);
        $order->setOrganization($organization);
        $order->setLineItems($lineItems);

        $this->checkoutShippingMethodsProvider->expects(self::once())
            ->method('getPrice')
            ->with($checkout)
            ->willReturn($price);

        $this->checkoutToOrderConverter->expects(self::once())
            ->method('getOrder')
            ->with($checkout)
            ->willReturn($order);

        $this->totalsProvider->expects(self::once())
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
        $this->totalsProvider->expects(self::once())
            ->method('getTotalWithSubtotalsAsArray')
            ->with($order)
            ->willReturnCallback(
                function (Order $order) use (
                    $lineItems,
                    $price,
                    $address,
                    $customer,
                    $website,
                    $organization,
                    $totals
                ) {
                    self::assertEquals($lineItems, $order->getLineItems());
                    self::assertEquals($price, $order->getShippingCost());
                    self::assertSame($address, $order->getBillingAddress());
                    self::assertSame($address, $order->getShippingAddress());
                    self::assertSame($customer, $order->getCustomer());
                    self::assertSame($website, $order->getWebsite());
                    self::assertSame($organization, $order->getOrganization());

                    return $totals;
                }
            );

        $this->assertSame($totals, $this->provider->getTotalsArray($checkout));
    }
}
