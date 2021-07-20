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

    /**
     * @var AddressInterface|\PHPUnit\Framework\MockObject\MockObject
     */
    private $shippingAddressMock;

    /**
     * @var AddressInterface|\PHPUnit\Framework\MockObject\MockObject
     */
    private $billingAddressMock;

    /**
     * @var AddressInterface|\PHPUnit\Framework\MockObject\MockObject
     */
    private $shippingOriginMock;

    /**
     * @var Customer|\PHPUnit\Framework\MockObject\MockObject
     */
    private $customerMock;

    /**
     * @var CustomerUser|\PHPUnit\Framework\MockObject\MockObject
     */
    private $customerUserMock;

    /**
     * @var Price|\PHPUnit\Framework\MockObject\MockObject
     */
    private $subtotalMock;

    protected function setUp(): void
    {
        $this->shippingAddressMock = $this->createMock(AddressInterface::class);
        $this->billingAddressMock = $this->createMock(AddressInterface::class);
        $this->shippingOriginMock = $this->createMock(AddressInterface::class);
        $this->customerMock = $this->getMockBuilder(Customer::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->customerUserMock = $this->getMockBuilder(CustomerUser::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->subtotalMock = $this->getMockBuilder(Price::class)
            ->disableOriginalConstructor()
            ->getMock();
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
            PaymentContext::FIELD_BILLING_ADDRESS => $this->billingAddressMock,
            PaymentContext::FIELD_SHIPPING_ADDRESS => $this->shippingAddressMock,
            PaymentContext::FIELD_SHIPPING_ORIGIN => $this->shippingOriginMock,
            PaymentContext::FIELD_SHIPPING_METHOD => 'someMethod',
            PaymentContext::FIELD_CURRENCY => 'USD',
            PaymentContext::FIELD_SUBTOTAL => $this->subtotalMock,
            PaymentContext::FIELD_CUSTOMER => $this->customerMock,
            PaymentContext::FIELD_CUSTOMER_USER => $this->customerUserMock,
            PaymentContext::FIELD_TOTAL => $totalAmount
        ]);

        $converter = new BasicPaymentContextToRulesValueConverter($factory);

        $this->assertEquals([
            'lineItems' => array_map(static function (PaymentLineItem $lineItem) use ($factory, $product1, $product2) {
                return $factory
                    ->createPaymentLineItemWithDecoratedProduct($lineItem, [$product1, $product2]);
            }, $paymentContext->getLineItems()->toArray()),
            'billingAddress' =>  $this->billingAddressMock,
            'shippingAddress' => $this->shippingAddressMock,
            'shippingOrigin' => $this->shippingOriginMock,
            'shippingMethod' => 'someMethod',
            'currency' => 'USD',
            'subtotal' => $this->subtotalMock,
            'customer' => $this->customerMock,
            'customerUser' => $this->customerUserMock,
            'total' => $totalAmount
        ], $converter->convert($paymentContext));
    }
}
