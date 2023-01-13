<?php

namespace Oro\Bundle\CheckoutBundle\Tests\Functional\DataFixtures;

use Doctrine\Common\Collections\ArrayCollection;
use Oro\Bundle\CheckoutBundle\Entity\Checkout;
use Oro\Bundle\CheckoutBundle\Entity\CheckoutLineItem;
use Oro\Bundle\CurrencyBundle\Entity\Price;
use Oro\Bundle\CustomerBundle\Tests\Functional\DataFixtures\LoadCustomerUserData;
use Oro\Bundle\PaymentTermBundle\Tests\Functional\DataFixtures\LoadPaymentMethodsConfigsRuleData;
use Oro\Bundle\PaymentTermBundle\Tests\Functional\DataFixtures\LoadPaymentTermData;
use Oro\Bundle\PaymentTermBundle\Tests\Functional\DataFixtures\Traits\EnabledPaymentMethodIdentifierTrait;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\ProductBundle\Entity\ProductUnit;
use Oro\Bundle\ProductBundle\Tests\Functional\DataFixtures\LoadProductData;
use Oro\Bundle\ShoppingListBundle\Tests\Functional\DataFixtures\LoadShoppingLists;

class LoadShoppingListsCheckoutsData extends AbstractLoadCheckouts
{
    use EnabledPaymentMethodIdentifierTrait;

    public const CHECKOUT_1 = 'checkout.1';
    public const CHECKOUT_2 = 'checkout.2';
    public const CHECKOUT_3 = 'checkout.3';
    public const CHECKOUT_4 = 'checkout.4';
    public const CHECKOUT_7 = 'checkout.7';
    public const CHECKOUT_8 = 'checkout.8';
    public const CHECKOUT_9 = 'checkout.9';
    public const CHECKOUT_10 = 'checkout.10';

    public const PAYMENT_METHOD = 'payment_term';

    /**
     * {@inheritDoc}
     */
    protected function getData(): array
    {
        $paymentTermIdentifier = $this->getPaymentMethodIdentifier($this->container);
        /** @var Product $product */
        $product = $this->getReference(LoadProductData::PRODUCT_5);
        /** @var ProductUnit $productUnit */
        $productUnit = $this->getReference('product_unit.bottle');
        $lineItem1 = (new CheckoutLineItem())
            ->setQuantity(10)
            ->setPrice(Price::create(100, 'USD'));
        $lineItem2 = (new CheckoutLineItem())
            ->setQuantity(20)
            ->setPrice(Price::create(200, 'USD'));
        $lineItem3 = (new CheckoutLineItem())
            ->setQuantity(30)
            ->setProduct($product)
            ->setProductUnit($productUnit);
        $lineItem4 = (new CheckoutLineItem())
            ->setQuantity(40)
            ->setProduct($product)
            ->setProductUnit($productUnit);
        $lineItem5 = (new CheckoutLineItem())
            ->setQuantity(50)
            ->setProduct($product)
            ->setProductUnit($productUnit);
        $lineItem6 = (new CheckoutLineItem())
            ->setQuantity(50)
            ->setProduct($product)
            ->setProductUnit($productUnit);
        $lineItem7 = (new CheckoutLineItem())
            ->setQuantity(40)
            ->setProduct($product)
            ->setProductUnit($productUnit);

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
                'source' => LoadShoppingLists::SHOPPING_LIST_2,
                'checkout' => ['payment_method' => $paymentTermIdentifier, 'currency' => 'USD'],
            ],
            self::CHECKOUT_3 => [
                'source' => LoadShoppingLists::SHOPPING_LIST_3,
                'checkout' => ['payment_method' => $paymentTermIdentifier, 'currency' => 'USD'],
                'lineItems' => new ArrayCollection([$lineItem3]),
            ],
            self::CHECKOUT_4 => [
                'source' => LoadShoppingLists::SHOPPING_LIST_4,
                'checkout' => ['payment_method' => $paymentTermIdentifier, 'currency' => 'USD'],
                'lineItems' => new ArrayCollection([$lineItem5]),
            ],
            self::CHECKOUT_7 => [
                'source' => LoadShoppingLists::SHOPPING_LIST_7,
                'checkout' => ['payment_method' => self::PAYMENT_METHOD, 'currency' => 'USD'],
                'customerUser' => LoadCustomerUserData::LEVEL_1_EMAIL,
                'lineItems' => new ArrayCollection([$lineItem4]),
            ],
            self::CHECKOUT_8 => [
                'source' => LoadShoppingLists::SHOPPING_LIST_5,
                'customerUser' => LoadCustomerUserData::LEVEL_1_EMAIL,
                'checkout' => ['payment_method' => $paymentTermIdentifier, 'currency' => 'USD'],
                'completed' => true,
                'lineItems' => new ArrayCollection([$lineItem6]),
            ],
            self::CHECKOUT_9 => [
                'source' => LoadShoppingLists::SHOPPING_LIST_6,
                'checkout' => ['payment_method' => $paymentTermIdentifier, 'currency' => 'USD'],
                'completed' => true,
            ],
            self::CHECKOUT_10 => [
                'source' => LoadShoppingLists::SHOPPING_LIST_8,
                'checkout' => ['payment_method' => self::PAYMENT_METHOD, 'currency' => 'EUR'],
                'customerUser' => LoadCustomerUserData::LEVEL_1_EMAIL,
                'lineItems' => new ArrayCollection([$lineItem7]),
            ],
        ];
    }

    /**
     * {@inheritDoc}
     */
    protected function getWorkflowName(): string
    {
        return 'b2b_flow_checkout';
    }

    /**
     * {@inheritDoc}
     */
    protected function createCheckout(): Checkout
    {
        return new Checkout();
    }

    /**
     * {@inheritDoc}
     */
    protected function getCheckoutSourceName(): string
    {
        return 'shoppingList';
    }

    /**
     * {@inheritDoc}
     */
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
