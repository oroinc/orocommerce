<?php

namespace Oro\Bundle\CheckoutBundle\Tests\Functional\DataFixtures;

use Doctrine\Common\Collections\ArrayCollection;
use Oro\Bundle\CheckoutBundle\Entity\Checkout;
use Oro\Bundle\CheckoutBundle\Entity\CheckoutLineItem;
use Oro\Bundle\CurrencyBundle\Entity\Price;
use Oro\Bundle\PaymentTermBundle\Tests\Functional\DataFixtures\LoadPaymentMethodsConfigsRuleData;
use Oro\Bundle\PaymentTermBundle\Tests\Functional\DataFixtures\LoadPaymentTermData;
use Oro\Bundle\PaymentTermBundle\Tests\Functional\DataFixtures\Traits\EnabledPaymentMethodIdentifierTrait;
use Oro\Bundle\ProductBundle\Entity\ProductUnit;
use Oro\Bundle\ProductBundle\Tests\Functional\DataFixtures\LoadProductData;
use Oro\Bundle\ShoppingListBundle\Tests\Functional\DataFixtures\LoadShoppingLists;

class LoadShoppingListsForCheckoutsData extends AbstractLoadCheckouts
{
    use EnabledPaymentMethodIdentifierTrait;

    public const CHECKOUT_1 = 'checkout.1';
    public const CHECKOUT_2 = 'checkout.2';
    public const CHECKOUT_3 = 'checkout.3';

    public const PAYMENT_METHOD = 'payment_term';

    #[\Override]
    protected function getData(): array
    {
        $paymentTermIdentifier = $this->getPaymentMethodIdentifier($this->container);
        /** @var ProductUnit $productUnit */
        $productUnit = $this->getReference('product_unit.bottle');
        $lineItem1 = (new CheckoutLineItem())
            ->setProductUnit($productUnit)
            ->setProductUnitCode('product_unit.bottle')
            ->setProductSku('PSKU-1')
            ->setFreeFormProduct('PSKU-2')
            ->setFromExternalSource('from_external_source_1')
            ->setQuantity(10)
            ->setPrice(Price::create(100, 'USD'))
            ->setShippingMethod('test_shipping_method_1')
            ->setShippingMethodType('test_shipping_method_type_1')
            ->setShippingEstimateAmount(10.00)
            ->setComment('test_comment_1')
            ->setChecksum(md5('PSKU-1'));
        $lineItem2 = (new CheckoutLineItem())
            ->setProductUnit($productUnit)
            ->setProductUnitCode('product_unit.bottle')
            ->setProductSku('PSKU-2')
            ->setFreeFormProduct('PSKU-1')
            ->setFromExternalSource('from_external_source_2')
            ->setQuantity(20)
            ->setPrice(Price::create(200, 'USD'))
            ->setShippingMethod('test_shipping_method_2')
            ->setShippingMethodType('test_shipping_method_type_2')
            ->setShippingEstimateAmount(20.00)
            ->setComment('test_comment_2')
            ->setChecksum(md5('PSKU-2'));
        $lineItem3 = (new CheckoutLineItem())
            ->setProductSku('PSKU-3')
            ->setProductUnit($productUnit)
            ->setProductUnitCode('product_unit.bottle')
            ->setQuantity(30)
            ->setPrice(Price::create(300, 'USD'));
        $lineItem4 = (new CheckoutLineItem())
            ->setProductUnit($productUnit)
            ->setProductUnitCode('product_unit.bottle')
            ->setProductSku('PSKU-4')
            ->setFreeFormProduct('PSKU-3')
            ->setFromExternalSource('from_external_source_4')
            ->setQuantity(40)
            ->setPrice(Price::create(400, 'USD'))
            ->setShippingMethod('test_shipping_method_4')
            ->setShippingMethodType('test_shipping_method_type_4')
            ->setShippingEstimateAmount(40.00)
            ->setComment('test_comment_4')
            ->setChecksum(md5('PSKU-4'));

        return [
            self::CHECKOUT_1 => [
                'source' => LoadShoppingLists::SHOPPING_LIST_1,
                'checkout' => [
                    'payment_method' => $paymentTermIdentifier,
                    'shippingCostAmount' => 10,
                    'currency' => 'USD',
                ],
                'lineItems' => new ArrayCollection([$lineItem1, $lineItem2]),
            ],
            self::CHECKOUT_2 => [
                'source' => LoadShoppingLists::SHOPPING_LIST_3,
                'checkout' => ['payment_method' => $paymentTermIdentifier, 'currency' => 'USD'],
                'lineItems' => new ArrayCollection([$lineItem3]),
            ],
            self::CHECKOUT_3 => [
                'source' => LoadShoppingLists::SHOPPING_LIST_4,
                'checkout' => ['payment_method' => $paymentTermIdentifier, 'currency' => 'USD'],
                'lineItems' => new ArrayCollection([$lineItem4]),
            ],
        ];
    }

    #[\Override]
    protected function getWorkflowName(): string
    {
        return 'b2b_flow_checkout';
    }

    #[\Override]
    protected function createCheckout(): Checkout
    {
        return new Checkout();
    }

    #[\Override]
    protected function getCheckoutSourceName(): string
    {
        return 'shoppingList';
    }

    #[\Override]
    public function getDependencies(): array
    {
        return array_merge(
            parent::getDependencies(),
            [
                LoadProductData::class,
                LoadShoppingLists::class,
                LoadPaymentTermData::class,
                LoadPaymentMethodsConfigsRuleData::class,
            ]
        );
    }
}
