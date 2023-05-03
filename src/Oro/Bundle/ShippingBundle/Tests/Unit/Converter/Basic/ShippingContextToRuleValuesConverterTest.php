<?php

namespace Oro\Bundle\ShippingBundle\Tests\Unit\Converter\Basic;

use Oro\Bundle\AddressBundle\Entity\Country;
use Oro\Bundle\AddressBundle\Entity\Region;
use Oro\Bundle\CurrencyBundle\Entity\Price;
use Oro\Bundle\CustomerBundle\Entity\Customer;
use Oro\Bundle\CustomerBundle\Entity\CustomerUser;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\ProductBundle\Model\ProductHolderInterface;
use Oro\Bundle\ProductBundle\VirtualFields\VirtualFieldsProductDecoratorFactory;
use Oro\Bundle\ShippingBundle\Context\LineItem\Collection\Doctrine\DoctrineShippingLineItemCollection;
use Oro\Bundle\ShippingBundle\Context\ShippingContext;
use Oro\Bundle\ShippingBundle\Context\ShippingLineItem;
use Oro\Bundle\ShippingBundle\Converter\Basic\ShippingContextToRulesValuesConverter;
use Oro\Bundle\ShippingBundle\ExpressionLanguage\DecoratedProductLineItemFactory;
use Oro\Bundle\ShippingBundle\Tests\Unit\Provider\Stub\ShippingAddressStub;
use Oro\Component\Testing\Unit\EntityTrait;

class ShippingContextToRuleValuesConverterTest extends \PHPUnit\Framework\TestCase
{
    use EntityTrait;

    /** @var DecoratedProductLineItemFactory */
    private $factory;

    /** @var ShippingContextToRulesValuesConverter */
    private $shippingContextToRuleValuesConverter;

    protected function setUp(): void
    {
        $this->factory = new DecoratedProductLineItemFactory(
            $this->createMock(VirtualFieldsProductDecoratorFactory::class)
        );

        $this->shippingContextToRuleValuesConverter = new ShippingContextToRulesValuesConverter(
            $this->factory
        );
    }

    /**
     * @dataProvider convertDataProvider
     */
    public function testConvert(ShippingContext $context): void
    {
        $products = array_map(
            static function (ProductHolderInterface $lineItem) {
                return $lineItem->getProduct();
            },
            $context->getLineItems()->toArray()
        );

        $expectedValues = [
            'lineItems' => array_map(function (ShippingLineItem $lineItem) use ($products) {
                return $this->factory->createShippingLineItemWithDecoratedProduct($lineItem, $products);
            }, $context->getLineItems()->toArray()),
            'shippingOrigin' => $context->getShippingOrigin(),
            'billingAddress' => $context->getBillingAddress(),
            'shippingAddress' => $context->getShippingAddress(),
            'paymentMethod' => $context->getPaymentMethod(),
            'currency' => $context->getCurrency(),
            'subtotal' => $context->getSubtotal(),
            'customer' => $context->getCustomer(),
            'customerUser' => $context->getCustomerUser(),
        ];
        $this->assertEquals($expectedValues, $this->shippingContextToRuleValuesConverter->convert($context));
    }

    public function convertDataProvider(): array
    {
        return [
            [
                'context' => new ShippingContext([
                    ShippingContext::FIELD_LINE_ITEMS => new DoctrineShippingLineItemCollection([
                        new ShippingLineItem([
                            ShippingLineItem::FIELD_PRODUCT => $this->getEntity(Product::class, ['id' => 1]),
                        ]),
                    ]),
                    ShippingContext::FIELD_SHIPPING_ORIGIN => $this->getEntity(ShippingAddressStub::class, [
                        'region' => $this->getEntity(Region::class, [
                            'code' => 'CA',
                        ], ['US-CA']),
                    ]),
                    ShippingContext::FIELD_BILLING_ADDRESS => $this->getEntity(ShippingAddressStub::class, [
                        'country' => new Country('US'),
                    ]),
                    ShippingContext::FIELD_SHIPPING_ADDRESS => $this->getEntity(ShippingAddressStub::class, [
                        'country' => new Country('US'),
                        'region' => $this->getEntity(Region::class, [
                            'code' => 'CA',
                        ], ['US-CA']),
                        'postalCode' => '90401',
                    ]),
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
