<?php

namespace Oro\Bundle\ShoppingListBundle\Tests\Functional\DataFixtures;

use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;
use Oro\Bundle\CustomerBundle\Entity\CustomerUser;
use Oro\Bundle\CustomerBundle\Tests\Functional\DataFixtures\LoadCustomerUserData;
use Oro\Bundle\FrontendTestFrameworkBundle\Migrations\Data\ORM\LoadCustomerUserData as LoadBaseCustomerUserData;
use Oro\Bundle\ShoppingListBundle\Entity\ShoppingList;
use Oro\Bundle\WebsiteBundle\Entity\Website;
use Oro\Bundle\WebsiteBundle\Tests\Functional\DataFixtures\LoadWebsiteData;

class LoadShoppingLists extends AbstractFixture implements DependentFixtureInterface
{
    public const SHOPPING_LIST_1 = 'shopping_list_1';
    public const SHOPPING_LIST_2 = 'shopping_list_2';
    public const SHOPPING_LIST_3 = 'shopping_list_3';
    public const SHOPPING_LIST_4 = 'shopping_list_4';
    public const SHOPPING_LIST_5 = 'shopping_list_5';
    public const SHOPPING_LIST_6 = 'shopping_list_6';
    public const SHOPPING_LIST_7 = 'shopping_list_7';
    public const SHOPPING_LIST_8 = 'shopping_list_8';
    public const SHOPPING_LIST_9 = 'shopping_list_9';
    public const SHOPPING_LIST_10 = 'shopping_list_10';

    private static $currency = 'USD';

    private array $shoppingListWebsites = [
        self::SHOPPING_LIST_9 => LoadWebsiteData::WEBSITE3,
    ];

    #[\Override]
    public function getDependencies(): array
    {
        return [
            LoadWebsiteData::class,
            LoadCustomerUserData::class
        ];
    }

    #[\Override]
    public function load(ObjectManager $manager): void
    {
        foreach ($this->getData() as $listLabel => $definition) {
            $isCurrent = $listLabel === self::SHOPPING_LIST_2;
            $this->createShoppingList(
                $manager,
                $this->getCustomerUser($manager, $definition['customerUser']),
                $listLabel,
                $isCurrent
            );
        }
        $manager->flush();
    }

    private function createShoppingList(
        ObjectManager $manager,
        CustomerUser $customerUser,
        string $name,
        bool $isCurrent = false
    ): void {
        $shoppingList = new ShoppingList();
        $shoppingList->setOrganization($customerUser->getOrganization());
        $shoppingList->setCustomerUser($customerUser);
        $shoppingList->setCustomer($customerUser->getCustomer());
        $shoppingList->setLabel($name . '_label');
        $shoppingList->setNotes($name . '_notes');
        $shoppingList->setCurrency(static::$currency);
        $shoppingList->setCurrent($isCurrent);
        if (\array_key_exists($name, $this->shoppingListWebsites)) {
            $shoppingList->setWebsite($this->getReference($this->shoppingListWebsites[$name]));
        } else {
            $shoppingList->setWebsite($this->getDefaultWebsite($manager));
        }
        $manager->persist($shoppingList);
        $this->addReference($name, $shoppingList);
    }

    private function getCustomerUser(ObjectManager $manager, string $email): CustomerUser
    {
        $customerUser = $manager->getRepository(CustomerUser::class)->findOneBy(['email' => $email]);
        if (!$customerUser) {
            throw new \LogicException('Test customer user not loaded');
        }

        return $customerUser;
    }

    private function getData(): array
    {
        return [
            self::SHOPPING_LIST_1 => [
                'customerUser' => LoadBaseCustomerUserData::AUTH_USER,
            ],
            self::SHOPPING_LIST_2 => [
                'customerUser' => LoadBaseCustomerUserData::AUTH_USER,
            ],
            self::SHOPPING_LIST_3 => [
                'customerUser' => LoadBaseCustomerUserData::AUTH_USER,
            ],
            self::SHOPPING_LIST_4 => [
                'customerUser' => LoadBaseCustomerUserData::AUTH_USER,
            ],
            self::SHOPPING_LIST_5 => [
                'customerUser' => LoadBaseCustomerUserData::AUTH_USER,
            ],
            self::SHOPPING_LIST_6 => [
                'customerUser' => LoadCustomerUserData::LEVEL_1_1_EMAIL,
            ],
            self::SHOPPING_LIST_7 => [
                'customerUser' => LoadCustomerUserData::LEVEL_1_EMAIL,
            ],
            self::SHOPPING_LIST_8 => [
                'customerUser' => LoadBaseCustomerUserData::AUTH_USER,
            ],
            self::SHOPPING_LIST_9 => [
                'customerUser' => LoadBaseCustomerUserData::AUTH_USER,
            ],
            self::SHOPPING_LIST_10 => [
                'customerUser' => LoadCustomerUserData::LEVEL_1_1_EMAIL,
            ],
        ];
    }

    private function getDefaultWebsite(ObjectManager $manager): Website
    {
        return $manager->getRepository(Website::class)->getDefaultWebsite();
    }

    public static function setCurrency(string $currency): void
    {
        self::$currency = $currency;
    }
}
