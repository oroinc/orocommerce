<?php

namespace Oro\Bundle\ShippingBundle\Tests\Unit\Converter\Basic;

use Doctrine\Common\Collections\ArrayCollection;
use Oro\Bundle\AddressBundle\Entity\Country;
use Oro\Bundle\AddressBundle\Entity\Region;
use Oro\Bundle\CurrencyBundle\Entity\Price;
use Oro\Bundle\CustomerBundle\Entity\Customer;
use Oro\Bundle\CustomerBundle\Entity\CustomerUser;
use Oro\Bundle\ProductBundle\Entity\ProductUnit;
use Oro\Bundle\ProductBundle\Model\ProductHolderInterface;
use Oro\Bundle\ProductBundle\Tests\Unit\Stub\ProductStub;
use Oro\Bundle\ShippingBundle\Context\ShippingContext;
use Oro\Bundle\ShippingBundle\Context\ShippingKitItemLineItem;
use Oro\Bundle\ShippingBundle\Context\ShippingLineItem;
use Oro\Bundle\ShippingBundle\Converter\Basic\ShippingContextToRulesValuesConverter;
use Oro\Bundle\ShippingBundle\ExpressionLanguage\DecoratedProductLineItemFactory;
use Oro\Bundle\ShippingBundle\Tests\Unit\Context\ShippingLineItemTrait;
use Oro\Bundle\ShippingBundle\Tests\Unit\Provider\Stub\ShippingAddressStub;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class ShippingContextToRulesValuesConverterTest extends TestCase
{
    use ShippingLineItemTrait;

    private DecoratedProductLineItemFactory|MockObject $decoratedProductLineItemFactory;

    private ShippingContextToRulesValuesConverter $shippingContextToRuleValuesConverter;

    #[\Override]
    protected function setUp(): void
    {
        $this->decoratedProductLineItemFactory = $this->createMock(DecoratedProductLineItemFactory::class);

        $this->shippingContextToRuleValuesConverter = new ShippingContextToRulesValuesConverter(
            $this->decoratedProductLineItemFactory
        );
    }

    /**
     * @dataProvider convertDataProvider
     */
    public function testConvert(ShippingContext $context): void
    {
        $shippingKitItemLineItems = [];
        $shippingLineItems = $context->getLineItems()->toArray();
        /** @var ShippingLineItem[] $shippingLineItems */
        foreach ($shippingLineItems as $shippingLineItem) {
            $shippingKitItemLineItems = array_merge(
                $shippingKitItemLineItems,
                $shippingLineItem->getKitItemLineItems()->toArray()
            );
        }

        $lineItems = array_merge($shippingLineItems, $shippingKitItemLineItems);

        $productIds = array_map(
            static function (ProductHolderInterface $lineItem) {
                return $lineItem->getProduct()?->getId();
            },
            $lineItems
        );

        $this->decoratedProductLineItemFactory->expects(self::once())
            ->method('createShippingLineItemWithDecoratedProduct')
            ->with($shippingLineItems[0], $productIds)
            ->willReturn($shippingLineItems[0]);

        $expectedValues = [
            'lineItems' => $shippingLineItems,
            'shippingOrigin' => $context->getShippingOrigin(),
            'billingAddress' => $context->getBillingAddress(),
            'shippingAddress' => $context->getShippingAddress(),
            'paymentMethod' => $context->getPaymentMethod(),
            'currency' => $context->getCurrency(),
            'subtotal' => $context->getSubtotal(),
            'customer' => $context->getCustomer(),
            'customerUser' => $context->getCustomerUser(),
        ];

        self::assertEquals($expectedValues, $this->shippingContextToRuleValuesConverter->convert($context));
    }

    public function convertDataProvider(): array
    {
        $productUnit = $this->createMock(ProductUnit::class);
        $unitCode = 'unit_code';
        $quantity = 1;
        $productHolder = $this->createMock(ProductHolderInterface::class);
        $product = (new ProductStub())->setId(2);

        $shippingKitItemLineItem = (new ShippingKitItemLineItem($productHolder))
            ->setQuantity($quantity)
            ->setProductUnit($productUnit)
            ->setProductUnitCode($unitCode)
            ->setProduct($product);

        return [
            [
                'context' => new ShippingContext([
                    ShippingContext::FIELD_LINE_ITEMS => new ArrayCollection([
                        $this->getShippingLineItem()
                            ->setProduct((new ProductStub())->setId(1))
                            ->setKitItemLineItems(new ArrayCollection([
                                $shippingKitItemLineItem,
                            ])),
                    ]),
                    ShippingContext::FIELD_SHIPPING_ORIGIN => (new ShippingAddressStub())
                        ->setRegion((new Region('US-CA'))->setCode('CA')),
                    ShippingContext::FIELD_BILLING_ADDRESS => (new ShippingAddressStub())
                        ->setCountry(new Country('US')),
                    ShippingContext::FIELD_SHIPPING_ADDRESS => (new ShippingAddressStub())
                        ->setCountry(new Country('US'))
                        ->setRegion((new Region('US-CA'))->setCode('CA'))
                        ->setPostalCode('90401'),
                    ShippingContext::FIELD_PAYMENT_METHOD => 'integration_payment_method',
                    ShippingContext::FIELD_CURRENCY => 'USD',
                    ShippingContext::FIELD_SUBTOTAL => Price::create(10.0, 'USD'),
                    ShippingContext::FIELD_CUSTOMER => (new Customer())->setName('Customer Name'),
                    ShippingContext::FIELD_CUSTOMER_USER => (new CustomerUser())->setFirstName('First Name'),
                ]),
            ],
        ];
    }
}
