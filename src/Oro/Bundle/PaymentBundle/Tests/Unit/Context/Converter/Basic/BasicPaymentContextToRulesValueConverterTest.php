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

class BasicPaymentContextToRulesValueConverterTest extends \PHPUnit_Framework_TestCase
{
    use EntityTrait;

    /**
     * @var AddressInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    private $shippingAddressMock;

    /**
     * @var AddressInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    private $billingAddressMock;

    /**
     * @var AddressInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    private $shippingOriginMock;

    /**
     * @var Customer|\PHPUnit_Framework_MockObject_MockObject
     */
    private $customerMock;

    /**
     * @var CustomerUser|\PHPUnit_Framework_MockObject_MockObject
     */
    private $customerUserMock;

    /**
     * @var Price|\PHPUnit_Framework_MockObject_MockObject
     */
    private $subtotalMock;

    public function setUp()
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

    public function testConvert()
    {
        $factory = new DecoratedProductLineItemFactory(
            $this->createMock(VirtualFieldsProductDecoratorFactory::class)
        );

        $paymentContext = new PaymentContext([
            PaymentContext::FIELD_LINE_ITEMS => new DoctrinePaymentLineItemCollection([
                new PaymentLineItem([PaymentLineItem::FIELD_PRODUCT => $this->getEntity(Product::class, ['id' => 1])]),
                new PaymentLineItem([PaymentLineItem::FIELD_PRODUCT => $this->getEntity(Product::class, ['id' => 2])])
            ]),
            PaymentContext::FIELD_BILLING_ADDRESS => $this->billingAddressMock,
            PaymentContext::FIELD_SHIPPING_ADDRESS => $this->shippingAddressMock,
            PaymentContext::FIELD_SHIPPING_ORIGIN => $this->shippingOriginMock,
            PaymentContext::FIELD_SHIPPING_METHOD => 'someMethod',
            PaymentContext::FIELD_CURRENCY => 'USD',
            PaymentContext::FIELD_SUBTOTAL => $this->subtotalMock,
            PaymentContext::FIELD_CUSTOMER => $this->customerMock,
            PaymentContext::FIELD_CUSTOMER_USER => $this->customerUserMock
        ]);


        $converter = new BasicPaymentContextToRulesValueConverter($factory);

        $this->assertEquals([
            'lineItems' => array_map(function (PaymentLineItem $lineItem) use ($paymentContext, $factory) {
                return $factory
                    ->createLineItemWithDecoratedProductByLineItem(
                        $paymentContext->getLineItems()->toArray(),
                        $lineItem
                    );
            }, $paymentContext->getLineItems()->toArray()),
            'billingAddress' =>  $this->billingAddressMock,
            'shippingAddress' => $this->shippingAddressMock,
            'shippingOrigin' => $this->shippingOriginMock,
            'shippingMethod' => 'someMethod',
            'currency' => 'USD',
            'subtotal' => $this->subtotalMock,
            'customer' => $this->customerMock,
            'customerUser' => $this->customerUserMock,
        ], $converter->convert($paymentContext));
    }
}
