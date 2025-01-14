<?php

namespace Oro\Bundle\OrderBundle\Tests\Functional\ApiForOrderLineItem\DataFixtures;

use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;
use Oro\Bundle\CustomerBundle\Tests\Functional\DataFixtures\LoadCustomers;
use Oro\Bundle\CustomerBundle\Tests\Functional\DataFixtures\LoadCustomerUserData;
use Oro\Bundle\OrderBundle\Entity\Order;
use Oro\Bundle\OrderBundle\Entity\OrderLineItem;
use Oro\Bundle\OrderBundle\Tests\Functional\DataFixtures\LoadOrderUsers;
use Oro\Bundle\ProductBundle\Tests\Functional\DataFixtures\LoadProductData;
use Oro\Bundle\TestFrameworkBundle\Test\DataFixtures\AbstractFixture;
use Oro\Bundle\TestFrameworkBundle\Tests\Functional\DataFixtures\LoadOrganization;
use Oro\Bundle\UserBundle\Entity\User;

class LoadOrderLineItemCustomerFiltersData extends AbstractFixture implements DependentFixtureInterface
{
    private const array ORDERS = [
        'order_customer_1' => [
            'customer' => LoadCustomers::CUSTOMER_LEVEL_1_DOT_1,
            'customerUser' => LoadCustomerUserData::LEVEL_1_1_EMAIL,
        ],
        'order_customer_2' => [
            'customer' => LoadCustomers::CUSTOMER_LEVEL_1_DOT_2,
            'customerUser' => LoadCustomerUserData::GROUP2_EMAIL,
        ]
    ];

    private const array LINE_ITEMS = [
        'line_item_customer_1_product_1' => [
            'order' => 'order_customer_1',
            'product' => 'product-1',
            'quantity' => 5,
            'unit' => 'product_unit.bottle',
            'value' => 15.7,
            'currency' => 'USD',
        ],
        'line_item_customer_1_product_2' => [
            'order' => 'order_customer_1',
            'product' => 'product-2',
            'quantity' => 7,
            'unit' => 'product_unit.bottle',
            'value' => 18.5,
            'currency' => 'USD',
        ],
        'line_item_customer_2_product_1' => [
            'order' => 'order_customer_2',
            'product' => 'product-1',
            'quantity' => 6,
            'unit' => 'product_unit.bottle',
            'value' => 14.5,
            'currency' => 'USD',
        ],
        'line_item_customer_2_product_2' => [
            'order' => 'order_customer_2',
            'product' => 'product-2',
            'quantity' => 7,
            'unit' => 'product_unit.bottle',
            'value' => 19.0,
            'currency' => 'USD',
        ],
    ];

    #[\Override]
    public function getDependencies(): array
    {
        return [
            LoadOrganization::class,
            LoadProductData::class,
            LoadCustomerUserData::class,
            LoadOrderUsers::class
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
            $order = new Order();
            $order->setIdentifier($reference);
            $order->setOwner($user);
            $order->setOrganization($user->getOrganization());
            $order->setCustomer($this->getReference($data['customer']))
                ->setCustomerUser($this->getReference($data['customerUser']));

            $manager->persist($order);
            $this->addReference($reference, $order);
        }

        $manager->flush();
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
    }
}
