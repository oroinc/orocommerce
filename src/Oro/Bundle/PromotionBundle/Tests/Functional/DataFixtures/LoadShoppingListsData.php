<?php

namespace Oro\Bundle\PromotionBundle\Tests\Functional\DataFixtures;

use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;
use Oro\Bundle\CustomerBundle\Entity\CustomerUser;
use Oro\Bundle\CustomerBundle\Tests\Functional\DataFixtures\LoadCustomerUserData;
use Oro\Bundle\FrontendTestFrameworkBundle\Migrations\Data\ORM\LoadCustomerUserData as LoadBaseCustomerUserData;
use Oro\Bundle\ShoppingListBundle\Entity\ShoppingList;
use Oro\Bundle\WebsiteBundle\Entity\Website;
use Oro\Bundle\WebsiteBundle\Tests\Functional\DataFixtures\LoadWebsiteData;

class LoadShoppingListsData extends AbstractFixture implements DependentFixtureInterface
{
    const PROMOTION_SHOPPING_LIST = 'promo_shopping_list';

    /**
     * @var array
     */
    protected $shoppingListsWithDefaultWebsite = [
        self::PROMOTION_SHOPPING_LIST,
    ];

    /**
     * {@inheritdoc}
     */
    public function getDependencies()
    {
        return [
            LoadWebsiteData::class,
            LoadCustomerUserData::class
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function load(ObjectManager $manager)
    {
        foreach ($this->getData() as $listLabel => $definition) {
            $isCurrent = $listLabel === self::PROMOTION_SHOPPING_LIST;
            $this->createShoppingList(
                $manager,
                $this->getCustomerUser($manager, $definition['customerUser']),
                $listLabel,
                $isCurrent
            );
        }
        $manager->flush();
    }

    /**
     * @param ObjectManager $manager
     * @param CustomerUser $customerUser
     * @param string $name
     * @param bool $isCurrent
     * @return ShoppingList
     */
    protected function createShoppingList(
        ObjectManager $manager,
        CustomerUser $customerUser,
        $name,
        $isCurrent = false
    ) {
        $shoppingList = new ShoppingList();
        $shoppingList->setOrganization($customerUser->getOrganization());
        $shoppingList->setCustomerUser($customerUser);
        $shoppingList->setCustomer($customerUser->getCustomer());
        $shoppingList->setLabel($name . '_label');
        $shoppingList->setNotes($name . '_notes');
        $shoppingList->setCurrent($isCurrent);
        if (in_array($name, $this->shoppingListsWithDefaultWebsite, true)) {
            $shoppingList->setWebsite($this->getDefaultWebsite($manager));
        } else {
            /** @var Website $website */
            $website = $this->getReference(LoadWebsiteData::WEBSITE1);
            $shoppingList->setWebsite($website);
        }
        $manager->persist($shoppingList);
        $this->addReference($name, $shoppingList);

        return $shoppingList;
    }

    /**
     * @param ObjectManager $manager
     * @param string $email
     *
     * @return CustomerUser
     * @throws \LogicException
     */
    protected function getCustomerUser(ObjectManager $manager, $email)
    {
        $customerUser = $manager->getRepository(CustomerUser::class)
            ->findOneBy(['email' => $email]);
        if (!$customerUser) {
            throw new \LogicException('Test customer user not loaded');
        }

        return $customerUser;
    }

    /**
     * @return array
     */
    protected function getData()
    {
        return [
            self::PROMOTION_SHOPPING_LIST => [
                'customerUser' => LoadBaseCustomerUserData::AUTH_USER,
            ]
        ];
    }

    /**
     * @param ObjectManager $manager
     * @return Website
     */
    protected function getDefaultWebsite(ObjectManager $manager)
    {
        return $manager->getRepository(Website::class)->getDefaultWebsite();
    }
}
