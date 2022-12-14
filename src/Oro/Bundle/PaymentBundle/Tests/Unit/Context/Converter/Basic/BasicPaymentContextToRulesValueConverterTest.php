<?php

namespace Oro\Bundle\PaymentBundle\Tests\Unit\Context\Converter\Basic;

use Oro\Bundle\CurrencyBundle\Entity\Price;
use Oro\Bundle\CustomerBundle\Entity\Customer;
use Oro\Bundle\CustomerBundle\Entity\CustomerUser;
use Oro\Bundle\LocaleBundle\Model\AddressInterface;
use Oro\Bundle\PaymentBundle\Context\Converter\Basic\BasicPaymentContextToRulesValueConverter;
use Oro\Bundle\PaymentBundle\Context\LineItem\Collection\Doctrine\DoctrinePaymentLineItemCollection;
use Oro\Bundle\PaymentBundle\Context\PaymentContext;
use Oro\Bundle\PaymentBundle\Context\PaymentLineItem;
use Oro\Bundle\PaymentBundle\ExpressionLanguage\DecoratedProductLineItemFactory;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\ProductBundle\VirtualFields\VirtualFieldsProductDecoratorFactory;
use Oro\Component\Testing\Unit\EntityTrait;

class BasicPaymentContextToRulesValueConverterTest extends \PHPUnit\Framework\TestCase
{
    use EntityTrait;

    /** @var AddressInterface|\PHPUnit\Framework\MockObject\MockObject */
    private $shippingAddress;

    /** @var AddressInterface|\PHPUnit\Framework\MockObject\MockObject */
    private $billingAddress;

    /** @var AddressInterface|\PHPUnit\Framework\MockObject\MockObject */
    private $shippingOrigin;

    /** @var Customer|\PHPUnit\Framework\MockObject\MockObject */
    private $customer;

    /** @var CustomerUser|\PHPUnit\Framework\MockObject\MockObject */
    private $customerUser;

    /** @var Price|\PHPUnit\Framework\MockObject\MockObject */
    private $subtotal;

    protected function setUp(): void
    {
        $this->shippingAddress = $this->createMock(AddressInterface::class);
        $this->billingAddress = $this->createMock(AddressInterface::class);
        $this->shippingOrigin = $this->createMock(AddressInterface::class);
        $this->customer = $this->createMock(Customer::class);
        $this->customerUser = $this->createMock(CustomerUser::class);
        $this->subtotal = $this->createMock(Price::class);
    }

    public function testConvert(): void
    {
        $factory = new DecoratedProductLineItemFactory(
            $this->createMock(VirtualFieldsProductDecoratorFactory::class)
        );

        $totalAmount = 10.0;
        $product1 = $this->getEntity(Product::class, ['id' => 1]);
        $product2 = $this->getEntity(Product::class, ['id' => 2]);

        $paymentContext = new PaymentContext([
            PaymentContext::FIELD_LINE_ITEMS => new DoctrinePaymentLineItemCollection([
                new PaymentLineItem([PaymentLineItem::FIELD_PRODUCT => $product1]),
                new PaymentLineItem([PaymentLineItem::FIELD_PRODUCT => $product2])
            ]),
            PaymentContext::FIELD_BILLING_ADDRESS => $this->billingAddress,
            PaymentContext::FIELD_SHIPPING_ADDRESS => $this->shippingAddress,
            PaymentContext::FIELD_SHIPPING_ORIGIN => $this->shippingOrigin,
            PaymentContext::FIELD_SHIPPING_METHOD => 'someMethod',
            PaymentContext::FIELD_CURRENCY => 'USD',
            PaymentContext::FIELD_SUBTOTAL => $this->subtotal,
            PaymentContext::FIELD_CUSTOMER => $this->customer,
            PaymentContext::FIELD_CUSTOMER_USER => $this->customerUser,
            PaymentContext::FIELD_TOTAL => $totalAmount
        ]);

        $converter = new BasicPaymentContextToRulesValueConverter($factory);

        $this->assertEquals([
            'lineItems' => array_map(static function (PaymentLineItem $lineItem) use ($factory, $product1, $product2) {
                return $factory
                    ->createPaymentLineItemWithDecoratedProduct($lineItem, [$product1, $product2]);
            }, $paymentContext->getLineItems()->toArray()),
            'billingAddress' =>  $this->billingAddress,
            'shippingAddress' => $this->shippingAddress,
            'shippingOrigin' => $this->shippingOrigin,
            'shippingMethod' => 'someMethod',
            'currency' => 'USD',
            'subtotal' => $this->subtotal,
            'customer' => $this->customer,
            'customerUser' => $this->customerUser,
            'total' => $totalAmount
        ], $converter->convert($paymentContext));
    }
}
