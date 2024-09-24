<?php

namespace Oro\Bundle\PaymentBundle\Tests\Unit\Context\Converter\Basic;

use Doctrine\Common\Collections\ArrayCollection;
use Oro\Bundle\CurrencyBundle\Entity\Price;
use Oro\Bundle\CustomerBundle\Entity\Customer;
use Oro\Bundle\CustomerBundle\Entity\CustomerUser;
use Oro\Bundle\LocaleBundle\Model\AddressInterface;
use Oro\Bundle\PaymentBundle\Context\Converter\Basic\BasicPaymentContextToRulesValueConverter;
use Oro\Bundle\PaymentBundle\Context\PaymentContext;
use Oro\Bundle\PaymentBundle\Context\PaymentKitItemLineItem;
use Oro\Bundle\PaymentBundle\ExpressionLanguage\DecoratedProductLineItemFactory;
use Oro\Bundle\PaymentBundle\Tests\Unit\Context\PaymentLineItemTrait;
use Oro\Bundle\ProductBundle\Entity\ProductUnit;
use Oro\Bundle\ProductBundle\Model\ProductHolderInterface;
use Oro\Bundle\ProductBundle\Tests\Unit\Stub\ProductStub;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class BasicPaymentContextToRulesValueConverterTest extends TestCase
{
    use PaymentLineItemTrait;

    private AddressInterface|MockObject $shippingAddress;

    private AddressInterface|MockObject $billingAddress;

    private AddressInterface|MockObject $shippingOrigin;

    private Customer|MockObject $customer;

    private CustomerUser|MockObject $customerUser;

    private Price|MockObject $subtotal;

    #[\Override]
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
        $totalAmount = 10.0;
        $product1 = (new ProductStub())->setId(1);
        $product2 = (new ProductStub())->setId(2);
        $product3 = (new ProductStub())->setId(3);
        $productUnit = $this->createMock(ProductUnit::class);
        $unitCode = 'unit_code';
        $quantity = 1;
        $productHolder = $this->createMock(ProductHolderInterface::class);

        $paymentKitItemLineItem = (new PaymentKitItemLineItem(
            $productUnit,
            $quantity,
            $productHolder
        ))
            ->setProductUnitCode($unitCode)
            ->setProduct($product3);

        $paymentLineItems = new ArrayCollection([
            $this->getPaymentLineItem()
                ->setProduct($product1)
                ->setKitItemLineItems(new ArrayCollection([$paymentKitItemLineItem])),
            $this->getPaymentLineItem()
                ->setProduct($product2),
        ]);

        $productIds = [
            $product1->getId(),
            $product3->getId(),
            $product2->getId(),
        ];

        $factory = $this->createMock(DecoratedProductLineItemFactory::class);
        $factory->expects(self::exactly(2))
            ->method('createPaymentLineItemWithDecoratedProduct')
            ->willReturnMap([
                [$paymentLineItems[0], $productIds, $paymentLineItems[0]],
                [$paymentLineItems[1], $productIds, $paymentLineItems[1]],
            ]);

        $paymentContext = new PaymentContext([
            PaymentContext::FIELD_LINE_ITEMS => $paymentLineItems,
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

        self::assertEquals([
            'lineItems' => $paymentLineItems->toArray(),
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
