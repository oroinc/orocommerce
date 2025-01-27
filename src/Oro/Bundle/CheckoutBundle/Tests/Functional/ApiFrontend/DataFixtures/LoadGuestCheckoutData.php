<?php

namespace Oro\Bundle\CheckoutBundle\Tests\Functional\ApiFrontend\DataFixtures;

use Doctrine\Persistence\ObjectManager;
use Oro\Bundle\CurrencyBundle\Entity\Price;
use Oro\Bundle\EntityExtendBundle\Entity\EnumOption;
use Oro\Bundle\EntityExtendBundle\Tools\ExtendHelper;
use Oro\Bundle\PricingBundle\Entity\CombinedPriceList;
use Oro\Bundle\PricingBundle\Entity\CombinedProductPrice;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\ProductBundle\Tests\Functional\DataFixtures\LoadProductData;
use Oro\Bundle\ProductBundle\Tests\Functional\DataFixtures\LoadProductKitData;
use Oro\Bundle\PromotionBundle\Tests\Functional\DataFixtures\LoadCouponData;

class LoadGuestCheckoutData extends AbstractLoadCheckoutData
{
    #[\Override]
    public function load(ObjectManager $manager): void
    {
        $this->updateProducts($manager);
        $this->loadProductPrices($manager);

        $this->loadCheckouts($manager, $this->getData());
    }

    /**
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     */
    private function getData(): array
    {
        return [
            'checkout.in_progress' => [
                'checkout' => ['currency' => 'USD'],
                'user' => 'user',
                'shoppingListLineItems' => [
                    ['product' => LoadProductData::PRODUCT_1],
                    [
                        'product' => LoadProductKitData::PRODUCT_KIT_3,
                        'kitItems' => [
                            'product-kit-3-kit-item-0',
                            'product-kit-3-kit-item-1',
                            'product-kit-3-kit-item-2'
                        ]
                    ],
                    [
                        'product' => LoadProductKitData::PRODUCT_KIT_2,
                        'kitItems' => ['product-kit-2-kit-item-0']
                    ],
                    ['product' => LoadProductData::PRODUCT_4]
                ],
                'billingAddress' => $this->createCheckoutAddress([
                    'type' => 'billing',
                    'country' => 'country_usa',
                    'city' => 'Rochester',
                    'region' => 'region_usa_california',
                    'street' => '1215 Caldwell Road',
                    'postalCode' => '14608',
                    'firstName' => 'John',
                    'lastName' => 'Doe'
                ]),
                'coupons' => [LoadCouponData::COUPON_WITH_PROMO_AND_VALID_FROM_AND_UNTIL]
            ],
            'checkout.unaccessible' => [
                'user' => 'user',
                'shoppingListLineItems' => [
                    ['product' => LoadProductData::PRODUCT_1],
                    [
                        'product' => LoadProductKitData::PRODUCT_KIT_3,
                        'kitItems' => [
                            'product-kit-3-kit-item-0',
                            'product-kit-3-kit-item-1',
                            'product-kit-3-kit-item-2'
                        ]
                    ],
                    [
                        'product' => LoadProductKitData::PRODUCT_KIT_1,
                        'kitItems' => ['product-kit-1-kit-item-0']
                    ],
                    ['product' => LoadProductData::PRODUCT_4]
                ],
                'billingAddress' => $this->createCheckoutAddress([
                    'type' => 'billing',
                    'country' => 'country_usa',
                    'city' => 'Rochester',
                    'region' => 'region_usa_california',
                    'street' => '1215 Caldwell Road',
                    'postalCode' => '14608',
                    'firstName' => 'John',
                    'lastName' => 'Doe'
                ])
            ],
            'checkout.deleted' => [
                'checkout' => ['currency' => 'USD', 'deleted' => true],
                'user' => 'user',
                'shoppingListLineItems' => [
                    [
                        'product' => LoadProductKitData::PRODUCT_KIT_1,
                        'kitItems' => ['product-kit-1-kit-item-0']
                    ],
                    ['product' => LoadProductData::PRODUCT_4]
                ],
                'billingAddress' => $this->createCheckoutAddress([
                    'type' => 'billing',
                    'country' => 'country_usa',
                    'city' => 'Rochester',
                    'region' => 'region_usa_california',
                    'street' => '1215 Caldwell Road',
                    'postalCode' => '14608',
                    'firstName' => 'John',
                    'lastName' => 'Doe'
                ])
            ]
        ];
    }

    private function updateProducts(ObjectManager $manager): void
    {
        /** @var Product $product */
        $product = $this->getReference(LoadProductKitData::PRODUCT_KIT_1);
        $product->setInventoryStatus($manager->find(EnumOption::class, ExtendHelper::buildEnumOptionId(
            Product::INVENTORY_STATUS_ENUM_CODE,
            Product::INVENTORY_STATUS_DISCONTINUED
        )));
    }

    private function loadProductPrices(ObjectManager $manager): void
    {
        $data = [
            [
                'product' => LoadProductData::PRODUCT_1,
                'qty' => 1,
                'unit' => 'product_unit.milliliter',
                'price' => 100.5,
                'currency' => 'USD'
            ],
            [
                'product' => 'product-1',
                'qty' => 1,
                'unit' => 'product_unit.liter',
                'price' => 115.90,
                'currency' => 'USD'
            ],
            [
                'product' => 'product-3',
                'qty' => 1,
                'unit' => 'product_unit.milliliter',
                'price' => 12.59,
                'currency' => 'USD'
            ],
            [
                'product' => LoadProductData::PRODUCT_2,
                'qty' => 1,
                'unit' => 'product_unit.milliliter',
                'price' => 20.9,
                'currency' => 'USD'
            ],
            [
                'product' => LoadProductData::PRODUCT_4,
                'qty' => 1,
                'unit' => 'product_unit.milliliter',
                'price' => 2.5,
                'currency' => 'USD'
            ]
        ];
        /** @var CombinedPriceList $priceList */
        $priceList = $this->getReference('1f');
        $priceList->setPricesCalculated(true);
        foreach ($data as $item) {
            $productPrice = new CombinedProductPrice();
            $productPrice->setPriceList($priceList);
            $productPrice->setUnit($this->getReference($item['unit']));
            $productPrice->setQuantity($item['qty']);
            $productPrice->setPrice(Price::create($item['price'], $item['currency']));
            $productPrice->setProduct($this->getReference($item['product']));
            $manager->persist($productPrice);
        }
    }
}
