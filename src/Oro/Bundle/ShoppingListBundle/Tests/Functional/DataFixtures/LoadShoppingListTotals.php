<?php

namespace Oro\Bundle\ShoppingListBundle\Tests\Functional\DataFixtures;

use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;
use Oro\Bundle\PricingBundle\SubtotalProcessor\Model\Subtotal;
use Oro\Bundle\ShoppingListBundle\Entity\ShoppingList;
use Oro\Bundle\ShoppingListBundle\Entity\ShoppingListTotal;

class LoadShoppingListTotals extends AbstractFixture implements DependentFixtureInterface
{
    protected array $notValidTotals = [
        [
            'shopping_list' => LoadShoppingLists::SHOPPING_LIST_1,
            'subtotal' => 1000
        ],
        [
            'shopping_list' => LoadShoppingLists::SHOPPING_LIST_5,
            'subtotal' => 1000
        ],
        [
            'shopping_list' => LoadShoppingLists::SHOPPING_LIST_7,
            'subtotal' => 1000
        ]
    ];
    protected array $validTotals = [
        [
            'shopping_list' => LoadShoppingLists::SHOPPING_LIST_1,
            'subtotal' => 2000
        ],
        [
            'shopping_list' => LoadShoppingLists::SHOPPING_LIST_2,
            'subtotal' => 2000
        ],
        [
            'shopping_list' => LoadShoppingLists::SHOPPING_LIST_3,
            'subtotal' => 2000
        ],
        [
            'shopping_list' => LoadShoppingLists::SHOPPING_LIST_4,
            'subtotal' => 2000
        ],
        [
            'shopping_list' => LoadShoppingLists::SHOPPING_LIST_5,
            'subtotal' => 2000
        ],
        [
            'shopping_list' => LoadShoppingLists::SHOPPING_LIST_6,
            'subtotal' => 2000
        ],
        [
            'shopping_list' => LoadShoppingLists::SHOPPING_LIST_7,
            'subtotal' => 2000
        ],
        [
            'shopping_list' => LoadShoppingLists::SHOPPING_LIST_8,
            'subtotal' => 2000
        ],
        [
            'shopping_list' => LoadShoppingLists::SHOPPING_LIST_9,
            'subtotal' => 2000
        ],
    ];

    #[\Override]
    public function load(ObjectManager $manager): void
    {
        $this->loadNotValidShoppingListTotals($manager);
        $this->loadValidShoppingListTotals($manager);
    }

    private function loadNotValidShoppingListTotals(ObjectManager $manager): void
    {
        $connection = $manager->getConnection();
        foreach ($this->notValidTotals as $data) {
            /** @var ShoppingList $shoppingList */
            $shoppingList = $this->getReference($data['shopping_list']);
            $connection->executeStatement(
                'INSERT INTO oro_shopping_list_total'
                . ' (currency, subtotal_value, is_valid, shopping_list_id, customer_user_id)'
                . ' VALUES (:currency, :subtotal, false, :shoppingList, NULL)',
                [
                    'currency' => $shoppingList->getCurrency(),
                    'subtotal' => $data['subtotal'],
                    'shoppingList' => $shoppingList->getId(),
                ]
            );
        }
    }

    private function loadValidShoppingListTotals(ObjectManager $manager): void
    {
        foreach ($this->validTotals as $data) {
            /** @var ShoppingList $shoppingList */
            $shoppingList = $this->getReference($data['shopping_list']);
            $shoppingListTotal = new ShoppingListTotal(
                $shoppingList,
                $shoppingList->getCurrency()
            );
            $subtotal = new Subtotal();
            $subtotal
                ->setAmount($data['subtotal'])
                ->setCurrency($shoppingList->getCurrency());
            $shoppingListTotal
                ->setSubtotal($subtotal)
                ->setValid(true)
                ->setCustomerUser($shoppingList->getCustomerUser());

            $manager->persist($shoppingListTotal);
        }
        $manager->flush();
    }

    #[\Override]
    public function getDependencies(): array
    {
        return [
            LoadShoppingLists::class,
            LoadGuestShoppingLists::class
        ];
    }
}
