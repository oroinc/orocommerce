<?php

namespace Oro\Bundle\ProductBundle\Tests\Functional\Api\DataFixtures;

use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;
use Oro\Bundle\CustomerBundle\Tests\Functional\DataFixtures\LoadCustomerUserData;
use Oro\Bundle\OrderBundle\Entity\Order;
use Oro\Bundle\OrderBundle\Entity\OrderLineItem;
use Oro\Bundle\OrderBundle\Tests\Functional\DataFixtures\LoadOrderUsers;
use Oro\Bundle\ProductBundle\Tests\Functional\DataFixtures\LoadProductData;
use Oro\Bundle\TestFrameworkBundle\Test\DataFixtures\AbstractFixture;
use Oro\Bundle\TestFrameworkBundle\Tests\Functional\DataFixtures\LoadOrganization;
use Oro\Bundle\UserBundle\Entity\User;
use Oro\Bundle\WebsiteBundle\Tests\Functional\DataFixtures\LoadWebsiteData;

class LoadProductLatestPurchasesData extends AbstractFixture implements DependentFixtureInterface
{
    private const array ORDERS = [
        'order_us_customer_1.1_product_1_bottle_usd_earlier' => [
            'customer' => 'customer.level_1.1',
            'customerUser' => LoadCustomerUserData::LEVEL_1_1_EMAIL,
            'currency' => 'USD',
            'website' => 'US',
            'createdAt' => '2023-12-01 08:45:00',
        ],
        'order_us_customer_1.1_product_1_bottle_usd_latest' => [
            'customer' => 'customer.level_1.1',
            'customerUser' => LoadCustomerUserData::LEVEL_1_1_EMAIL,
            'currency' => 'USD',
            'website' => 'US',
            'createdAt' => '2023-12-05 10:20:00',
        ],
        'order_us_customer_1.1_product_2_bottle_usd_earlier' => [
            'customer' => 'customer.level_1.1',
            'customerUser' => LoadCustomerUserData::LEVEL_1_1_EMAIL,
            'currency' => 'USD',
            'website' => 'US',
            'createdAt' => '2023-12-03 09:15:00',
        ],
        'order_us_customer_1.1_product_2_bottle_usd_latest' => [
            'customer' => 'customer.level_1.1',
            'customerUser' => LoadCustomerUserData::LEVEL_1_1_EMAIL,
            'currency' => 'USD',
            'website' => 'US',
            'createdAt' => '2023-12-06 11:34:56',
        ],
        'order_us_customer_1.2_product_1_bottle_usd_earlier' => [
            'customer' => 'customer.level_1.2',
            'customerUser' => LoadCustomerUserData::GROUP2_EMAIL,
            'currency' => 'USD',
            'website' => 'US',
            'createdAt' => '2023-12-02 08:23:12',
        ],
        'order_us_customer_1.2_product_1_bottle_usd_latest' => [
            'customer' => 'customer.level_1.2',
            'customerUser' => LoadCustomerUserData::GROUP2_EMAIL,
            'currency' => 'USD',
            'website' => 'US',
            'createdAt' => '2023-12-07 16:45:34',
        ],
        'order_us_customer_1.2_product_2_bottle_usd_earlier' => [
            'customer' => 'customer.level_1.2',
            'customerUser' => LoadCustomerUserData::GROUP2_EMAIL,
            'currency' => 'USD',
            'website' => 'US',
            'createdAt' => '2023-12-03 07:45:21',
        ],
        'order_us_customer_1.2_product_2_bottle_usd_latest' => [
            'customer' => 'customer.level_1.2',
            'customerUser' => LoadCustomerUserData::GROUP2_EMAIL,
            'currency' => 'USD',
            'website' => 'US',
            'createdAt' => '2023-12-08 14:23:45',
        ],
        'order_ca_customer_1.1_product_1_liter_eur_earlier' => [
            'customer' => 'customer.level_1.1',
            'customerUser' => LoadCustomerUserData::LEVEL_1_1_EMAIL,
            'currency' => 'EUR',
            'website' => 'CA',
            'createdAt' => '2023-12-01 07:56:12',
        ],
        'order_ca_customer_1.1_product_1_liter_eur_latest' => [
            'customer' => 'customer.level_1.1',
            'customerUser' => LoadCustomerUserData::LEVEL_1_1_EMAIL,
            'currency' => 'EUR',
            'website' => 'CA',
            'createdAt' => '2023-12-05 13:23:45',
        ],
        'order_ca_customer_1.1_product_2_liter_eur_earlier' => [
            'customer' => 'customer.level_1.1',
            'customerUser' => LoadCustomerUserData::LEVEL_1_1_EMAIL,
            'currency' => 'EUR',
            'website' => 'CA',
            'createdAt' => '2023-12-02 06:45:33',
        ],
        'order_ca_customer_1.1_product_2_liter_eur_latest' => [
            'customer' => 'customer.level_1.1',
            'customerUser' => LoadCustomerUserData::LEVEL_1_1_EMAIL,
            'currency' => 'EUR',
            'website' => 'CA',
            'createdAt' => '2023-12-06 10:34:56',
        ],
        'order_ca_customer_1.2_product_1_liter_eur_earlier' => [
            'customer' => 'customer.level_1.2',
            'customerUser' => LoadCustomerUserData::GROUP2_EMAIL,
            'currency' => 'EUR',
            'website' => 'CA',
            'createdAt' => '2023-12-01 05:23:12',
        ],
        'order_ca_customer_1.2_product_1_liter_eur_latest' => [
            'customer' => 'customer.level_1.2',
            'customerUser' => LoadCustomerUserData::GROUP2_EMAIL,
            'currency' => 'EUR',
            'website' => 'CA',
            'createdAt' => '2023-12-07 09:45:34',
        ],
        'order_ca_customer_1.2_product_2_liter_eur_earlier' => [
            'customer' => 'customer.level_1.2',
            'customerUser' => LoadCustomerUserData::GROUP2_EMAIL,
            'currency' => 'EUR',
            'website' => 'CA',
            'createdAt' => '2023-12-02 06:12:45',
        ],
        'order_ca_customer_1.2_product_2_liter_eur_latest' => [
            'customer' => 'customer.level_1.2',
            'customerUser' => LoadCustomerUserData::GROUP2_EMAIL,
            'currency' => 'EUR',
            'website' => 'CA',
            'createdAt' => '2023-12-08 11:15:00',
        ],
    ];

    private const array LINE_ITEMS = [
        'line_item_us_customer_1.1_product_1_bottle_usd_earlier' => [
            'order' => 'order_us_customer_1.1_product_1_bottle_usd_earlier',
            'product' => 'product-1',
            'quantity' => 5,
            'unit' => 'product_unit.bottle',
            'value' => 15.7,
            'currency' => 'USD',
        ],
        'line_item_us_customer_1.1_product_1_bottle_usd_latest' => [
            'order' => 'order_us_customer_1.1_product_1_bottle_usd_latest',
            'product' => 'product-1',
            'quantity' => 3,
            'unit' => 'product_unit.bottle',
            'value' => 20.0,
            'currency' => 'USD',
        ],
        'line_item_us_customer_1.1_product_2_bottle_usd_earlier' => [
            'order' => 'order_us_customer_1.1_product_2_bottle_usd_earlier',
            'product' => 'product-2',
            'quantity' => 7,
            'unit' => 'product_unit.bottle',
            'value' => 18.5,
            'currency' => 'USD',
        ],
        'line_item_us_customer_1.1_product_2_bottle_usd_latest' => [
            'order' => 'order_us_customer_1.1_product_2_bottle_usd_latest',
            'product' => 'product-2',
            'quantity' => 4,
            'unit' => 'product_unit.bottle',
            'value' => 25.0,
            'currency' => 'USD',
        ],
        'line_item_us_customer_1.2_product_1_bottle_usd_earlier' => [
            'order' => 'order_us_customer_1.2_product_1_bottle_usd_earlier',
            'product' => 'product-1',
            'quantity' => 6,
            'unit' => 'product_unit.bottle',
            'value' => 14.5,
            'currency' => 'USD',
        ],
        'line_item_us_customer_1.2_product_1_bottle_usd_latest' => [
            'order' => 'order_us_customer_1.2_product_1_bottle_usd_latest',
            'product' => 'product-1',
            'quantity' => 8,
            'unit' => 'product_unit.bottle',
            'value' => 22.0,
            'currency' => 'USD',
        ],
        'line_item_us_customer_1.2_product_2_bottle_usd_earlier' => [
            'order' => 'order_us_customer_1.2_product_2_bottle_usd_earlier',
            'product' => 'product-2',
            'quantity' => 7,
            'unit' => 'product_unit.bottle',
            'value' => 19.0,
            'currency' => 'USD',
        ],
        'line_item_us_customer_1.2_product_2_bottle_usd_latest' => [
            'order' => 'order_us_customer_1.2_product_2_bottle_usd_latest',
            'product' => 'product-2',
            'quantity' => 5,
            'unit' => 'product_unit.bottle',
            'value' => 24.0,
            'currency' => 'USD',
        ],
        'line_item_ca_customer_1.1_product_1_liter_eur_earlier' => [
            'order' => 'order_ca_customer_1.1_product_1_liter_eur_earlier',
            'product' => 'product-1',
            'quantity' => 10,
            'unit' => 'product_unit.liter',
            'value' => 30.0,
            'currency' => 'EUR',
        ],
        'line_item_ca_customer_1.1_product_1_liter_eur_latest' => [
            'order' => 'order_ca_customer_1.1_product_1_liter_eur_latest',
            'product' => 'product-1',
            'quantity' => 12,
            'unit' => 'product_unit.liter',
            'value' => 35.0,
            'currency' => 'EUR',
        ],
        'line_item_ca_customer_1.1_product_2_liter_eur_earlier' => [
            'order' => 'order_ca_customer_1.1_product_2_liter_eur_earlier',
            'product' => 'product-2',
            'quantity' => 8,
            'unit' => 'product_unit.liter',
            'value' => 28.0,
            'currency' => 'EUR',
        ],
        'line_item_ca_customer_1.1_product_2_liter_eur_latest' => [
            'order' => 'order_ca_customer_1.1_product_2_liter_eur_latest',
            'product' => 'product-2',
            'quantity' => 10,
            'unit' => 'product_unit.liter',
            'value' => 33.0,
            'currency' => 'EUR',
        ],
        'line_item_ca_customer_1.2_product_1_liter_eur_earlier' => [
            'order' => 'order_ca_customer_1.2_product_1_liter_eur_earlier',
            'product' => 'product-1',
            'quantity' => 9,
            'unit' => 'product_unit.liter',
            'value' => 27.0,
            'currency' => 'EUR',
        ],
        'line_item_ca_customer_1.2_product_1_liter_eur_latest' => [
            'order' => 'order_ca_customer_1.2_product_1_liter_eur_latest',
            'product' => 'product-1',
            'quantity' => 11,
            'unit' => 'product_unit.liter',
            'value' => 32.0,
            'currency' => 'EUR',
        ],
        'line_item_ca_customer_1.2_product_2_liter_eur_earlier' => [
            'order' => 'order_ca_customer_1.2_product_2_liter_eur_earlier',
            'product' => 'product-2',
            'quantity' => 8,
            'unit' => 'product_unit.liter',
            'value' => 29.0,
            'currency' => 'EUR',
        ],
        'line_item_ca_customer_1.2_product_2_liter_eur_latest' => [
            'order' => 'order_ca_customer_1.2_product_2_liter_eur_latest',
            'product' => 'product-2',
            'quantity' => 6,
            'unit' => 'product_unit.liter',
            'value' => 34.0,
            'currency' => 'EUR',
        ],
    ];

    #[\Override]
    public function getDependencies(): array
    {
        return [
            LoadOrganization::class,
            LoadOrderUsers::class,
            LoadProductData::class,
            LoadCustomerUserData::class,
            LoadWebsiteData::class
        ];
    }

    #[\Override]
    public function load(ObjectManager $manager): void
    {
        $this->loadOrders($manager);
        $this->loadOrderLineItems($manager);
    }

    private function loadOrders(ObjectManager $manager): void
    {
        /** @var User $user */
        $user = $this->getReference(LoadOrderUsers::ORDER_USER_1);
        if (!$user->getOrganization()) {
            $user->setOrganization($this->getReference('organization'));
        }
        foreach (self::ORDERS as $reference => $data) {
            $sanitizedUserReferenceName = $this->sanitizeEmailReference($data['customerUser']);
            if (!$this->hasReference($data['customerUser'])) {
                throw new \InvalidArgumentException("Reference {$data['customerUser']} does not exist.");
            }
            $customerUserReference = $this->getReference($data['customerUser']);
            $this->setReference($sanitizedUserReferenceName, $customerUserReference);

            $order = new Order();
            $order->setOwner($user)
                ->setIdentifier($reference)
                ->setCustomer($this->getReference($data['customer']))
                ->setCustomerUser($customerUserReference)
                ->setCurrency($data['currency'])
                ->setWebsite($this->getReference($data['website']));

            $manager->persist($order);
            $this->addReference($reference, $order);
        }

        $manager->flush();

        $connection = $manager->getConnection();
        foreach (self::ORDERS as $reference => $data) {
            $order = $this->getReference($reference);
            $connection->executeStatement(
                'UPDATE oro_order SET created_at = :createdAt WHERE id = :id',
                [
                    'createdAt' => $data['createdAt'],
                    'id' => $order->getId(),
                ]
            );
        }
    }

    private function sanitizeEmailReference(string $referenceName): string
    {
        return str_replace('@', '_at_', $referenceName);
    }

    private function loadOrderLineItems(ObjectManager $manager): void
    {
        foreach (self::LINE_ITEMS as $reference => $data) {
            $lineItem = new OrderLineItem();
            $lineItem->setProduct($this->getReference($data['product']))
                ->setQuantity($data['quantity'])
                ->setProductUnit($this->getReference($data['unit']))
                ->setValue($data['value'])
                ->setCurrency($data['currency'])
                ->addOrder($this->getReference($data['order']));

            $manager->persist($lineItem);
            $this->addReference($reference, $lineItem);
        }

        $manager->flush();

        $connection = $manager->getConnection();
        foreach (self::LINE_ITEMS as $reference => $data) {
            $lineItem = $this->getReference($reference);
            $connection->executeStatement(
                'UPDATE oro_order_line_item SET created_at = :createdAt WHERE id = :id',
                [
                    'createdAt' => $this->getReference($data['order'])->getCreatedAt()->format('Y-m-d H:i:s'),
                    'id' => $lineItem->getId(),
                ]
            );
        }
    }
}
