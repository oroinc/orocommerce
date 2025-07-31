<?php

namespace Oro\Bundle\CheckoutBundle\Tests\Functional\ApiFrontend\DataFixtures;

use Doctrine\Persistence\ObjectManager;
use Oro\Bundle\CurrencyBundle\Entity\Price;
use Oro\Bundle\CustomerBundle\Entity\Customer;
use Oro\Bundle\CustomerBundle\Entity\CustomerAddress;
use Oro\Bundle\CustomerBundle\Entity\CustomerUser;
use Oro\Bundle\CustomerBundle\Entity\CustomerUserAddress;
use Oro\Bundle\CustomerBundle\Entity\CustomerUserRole;
use Oro\Bundle\EntityExtendBundle\Entity\EnumOption;
use Oro\Bundle\EntityExtendBundle\Tools\ExtendHelper;
use Oro\Bundle\OrderBundle\Entity\Order;
use Oro\Bundle\OrderBundle\Entity\OrderAddress;
use Oro\Bundle\PaymentTermBundle\Entity\PaymentTerm;
use Oro\Bundle\PricingBundle\Entity\CombinedPriceList;
use Oro\Bundle\PricingBundle\Entity\CombinedProductPrice;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\ProductBundle\Tests\Functional\DataFixtures\LoadProductData;
use Oro\Bundle\ProductBundle\Tests\Functional\DataFixtures\LoadProductKitData;
use Oro\Bundle\PromotionBundle\Tests\Functional\DataFixtures\LoadCouponData;
use Oro\Bundle\TestFrameworkBundle\Tests\Functional\DataFixtures\LoadOrganization;
use Oro\Bundle\TestFrameworkBundle\Tests\Functional\DataFixtures\LoadUser;
use Oro\Bundle\UserBundle\Entity\User;
use Oro\Bundle\WebsiteBundle\Tests\Functional\DataFixtures\LoadWebsite;

class LoadCheckoutData extends AbstractLoadCheckoutData
{
    #[\Override]
    public function load(ObjectManager $manager): void
    {
        /** @var Customer $customer */
        $customer = $this->getReference('customer');

        $childCustomer = $this->createCustomer($manager, 'child_customer', $this->getReference(LoadUser::USER));
        $childCustomer->setParent($customer);

        $anotherCustomerUser = $this->createCustomerUser(
            $manager,
            'another_customer_user',
            $customer,
            $this->getReference(LoadUser::USER),
            'ryan.range@test.com',
            'Brenda',
            'Bradley'
        );
        $anotherDepartmentCustomerUser = $this->createCustomerUser(
            $manager,
            'another_department_customer_user',
            $this->createCustomer($manager, 'another_customer', $this->getReference(LoadUser::USER)),
            $this->getReference(LoadUser::USER),
            'ryan.range@test.com',
            'Ryan',
            'Range'
        );

        $this->getPropertyAccessor()->setValue(
            $customer,
            ExtendHelper::buildAssociationName(PaymentTerm::class),
            $this->getReference('payment_term_test_data_net 10')
        );

        $this->updateProducts($manager);
        $this->loadProductPrices($manager);

        $this->createOrder($manager, $anotherCustomerUser, 'another_user_order');
        $this->createOrder($manager, $anotherDepartmentCustomerUser, 'another_department_order');

        $this->createCustomerAddress($manager, 'customer');
        $this->createCustomerUserAddress($manager, 'customer_user');

        $this->loadCheckouts($manager, $this->getData());
    }

    /**
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     */
    private function getData(): array
    {
        return [
            'checkout.ready_for_completion' => [
                'checkout' => ['currency' => 'USD'],
                'customerUser' => 'customer_user',
                'shoppingListLineItems' => [
                    ['product' => LoadProductData::PRODUCT_1],
                    ['product' => LoadProductData::PRODUCT_6]
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
                'shippingAddress' => $this->createCheckoutAddress([
                    'type' => 'shipping',
                    'country' => 'country_usa',
                    'city' => 'Romney',
                    'region' => 'region_usa_florida',
                    'street' => '2413 Capitol Avenue',
                    'postalCode' => '47981',
                    'firstName' => 'John',
                    'lastName' => 'Doe'
                ])
            ],
            'checkout.empty' => [
                'customerUser' => 'customer_user'
            ],
            'checkout.open' => [
                'checkout' => ['currency' => 'USD'],
                'customerUser' => 'customer_user',
                'shoppingListLineItems' => [
                    ['product' => LoadProductData::PRODUCT_2]
                ]
            ],
            'checkout.in_progress' => [
                'checkout' => ['currency' => 'USD'],
                'customerUser' => 'customer_user',
                'shoppingListLineItems' => [
                    ['product' => LoadProductData::PRODUCT_1, 'quantity' => 10],
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
            'checkout.deleted' => [
                'checkout' => ['currency' => 'USD', 'deleted' => true],
                'customerUser' => 'customer_user',
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
            ],
            'checkout.another_customer_user' => [
                'checkout' => ['currency' => 'USD'],
                'customerUser' => 'another_customer_user',
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
            'checkout.another_department_customer_user' => [
                'checkout' => ['currency' => 'USD'],
                'customerUser' => 'another_department_customer_user',
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

    private function createCustomer(ObjectManager $manager, string $name, User $owner): Customer
    {
        $customer = new Customer();
        $customer->setName($name);
        $customer->setOwner($owner);
        $customer->setOrganization($owner->getOrganization());
        $manager->persist($customer);
        $this->addReference($name, $customer);

        return $customer;
    }

    private function createCustomerUser(
        ObjectManager $manager,
        string $name,
        Customer $customer,
        User $owner,
        string $email,
        string $firstName,
        string $lastName,
    ): CustomerUser {
        $customerUser = new CustomerUser();
        $customerUser->setCustomer($customer);
        $customerUser->setOwner($owner);
        $customerUser->setEmail($email);
        $customerUser->setFirstName($firstName);
        $customerUser->setLastName($lastName);
        $customerUser->setOrganization($customer->getOrganization());
        $customerUser->addUserRole($manager->getRepository(CustomerUserRole::class)->findOneBy([
            'role' => 'ROLE_FRONTEND_ADMINISTRATOR'
        ]));
        $customerUser->setPlainPassword($email);
        $manager->persist($customerUser);
        $this->addReference($name, $customerUser);

        $this->container->get('oro_customer_user.manager')->updateUser($customerUser);

        return $customerUser;
    }

    private function createOrder(ObjectManager $manager, CustomerUser $customerUser, string $name): void
    {
        $order = new Order();
        $order->setIdentifier($name);
        $order->setOrganization($this->getReference(LoadOrganization::ORGANIZATION));
        $order->setOwner($this->getReference(LoadUser::USER));
        $order->setWebsite($this->getReference(LoadWebsite::WEBSITE));
        $order->setCustomer($customerUser->getCustomer());
        $order->setCustomerUser($customerUser);
        $order->setCurrency('USD');
        $order->setSubtotal(10.0);
        $order->setTotal(10.0);
        $order->setBillingAddress($this->createOrderAddress([
            'type' => 'billing',
            'country' => 'country_usa',
            'city' => 'Rochester',
            'region' => 'region_usa_california',
            'street' => '1215 Caldwell Road',
            'postalCode' => '14608',
            'firstName' => 'John',
            'lastName' => 'Doe'
        ]));
        $manager->persist($order);
        $this->setReference('order', $order);
        $this->setReference('order.billing_address', $order->getBillingAddress());
    }

    private function createCustomerAddress(ObjectManager $manager, string $customerReference): void
    {
        $address = new CustomerAddress();
        $address->setSystemOrganization($this->getReference(LoadOrganization::ORGANIZATION));
        $address->setOwner($this->getReference(LoadUser::USER));
        $address->setFrontendOwner($this->getReference($customerReference));
        $address->setCountry($this->getReference('country_usa'));
        $address->setRegion($this->getReference('region_usa_california'));
        $address->setLabel('Customer Address');
        $address->setCity('Los Angeles');
        $address->setStreet('Customer Address Street');
        $address->setPostalCode('90001');
        $address->setFirstName('John');
        $address->setLastName('Doe');
        $address->setOrganization('Acme');
        $address->setPhone('123-456');
        $address->setPrimary(true);
        $address->addType($this->getReference('billing'));
        $address->addType($this->getReference('shipping'));
        $manager->persist($address);
        $this->setReference($customerReference . '.address', $address);
    }

    private function createCustomerUserAddress(ObjectManager $manager, string $customerUserReference): void
    {
        $address = new CustomerUserAddress();
        $address->setSystemOrganization($this->getReference(LoadOrganization::ORGANIZATION));
        $address->setOwner($this->getReference(LoadUser::USER));
        $address->setFrontendOwner($this->getReference($customerUserReference));
        $address->setCountry($this->getReference('country_usa'));
        $address->setRegion($this->getReference('region_usa_california'));
        $address->setLabel('Customer User Address');
        $address->setCity('Los Angeles');
        $address->setStreet('Customer User Address Street');
        $address->setPostalCode('90001');
        $address->setFirstName('John');
        $address->setLastName('Doe');
        $address->setOrganization('Acme');
        $address->setPhone('123-456');
        $address->setPrimary(true);
        $address->addType($this->getReference('billing'));
        $address->addType($this->getReference('shipping'));
        $manager->persist($address);
        $this->setReference($customerUserReference . '.address', $address);
    }

    private function createOrderAddress(array $data): OrderAddress
    {
        $address = new OrderAddress();
        $address->setCountry($this->getReference($data['country']));
        $address->setRegion($this->getReference($data['region']));
        $address->setCity($data['city']);
        $address->setStreet($data['street']);
        $address->setPostalCode($data['postalCode']);
        $address->setFirstName($data['firstName']);
        $address->setLastName($data['lastName']);

        return $address;
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
