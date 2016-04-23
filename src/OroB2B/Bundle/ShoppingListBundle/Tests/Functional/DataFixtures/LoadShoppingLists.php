<?php

namespace OroB2B\Bundle\ShoppingListBundle\Tests\Functional\DataFixtures;

use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Common\Persistence\ObjectManager;

use Oro\Component\Testing\Fixtures\LoadAccountUserData;

use OroB2B\Bundle\AccountBundle\Entity\AccountUser;
use OroB2B\Bundle\ShoppingListBundle\Entity\ShoppingList;

class LoadShoppingLists extends AbstractFixture implements DependentFixtureInterface
{
    const SHOPPING_LIST_1 = 'shopping_list_1';
    const SHOPPING_LIST_2 = 'shopping_list_2';
    const SHOPPING_LIST_3 = 'shopping_list_3';
    const SHOPPING_LIST_4 = 'shopping_list_4';
    const SHOPPING_LIST_5 = 'shopping_list_5';

    /**
     * {@inheritdoc}
     */
    public function getDependencies()
    {
        return [
            'Oro\Component\Testing\Fixtures\LoadAccountUserData'
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function load(ObjectManager $manager)
    {
        $accountUser = $this->getAccountUser($manager);
        $lists = $this->getData();
        foreach ($lists as $listLabel => $data) {
            $isCurrent = $listLabel === self::SHOPPING_LIST_2;
            $this->createShoppingList(
                $manager,
                $accountUser,
                $listLabel,
                $data['total'],
                $data['subtotal'],
                $isCurrent
            );
        }

        $manager->flush();
    }

    /**
     * @param ObjectManager $manager
     * @param AccountUser $accountUser
     * @param string $name
     * @param float $total
     * @param float $subtotal
     * @param bool $isCurrent
     * @return ShoppingList
     */
    protected function createShoppingList(
        ObjectManager $manager,
        AccountUser $accountUser,
        $name,
        $total,
        $subtotal,
        $isCurrent = false
    ) {
        $shoppingList = new ShoppingList();
        $shoppingList->setOrganization($accountUser->getOrganization());
        $shoppingList->setAccountUser($accountUser);
        $shoppingList->setAccount($accountUser->getAccount());
        $shoppingList->setLabel($name . '_label');
        $shoppingList->setNotes($name . '_notes');
        $shoppingList->setCurrent($isCurrent);
        $shoppingList->setCurrency('EUR');
        $shoppingList->setTotal($total);
        $shoppingList->setSubtotal($subtotal);
        $manager->persist($shoppingList);
        $this->addReference($name, $shoppingList);

        return $shoppingList;
    }

    /**
     * @param ObjectManager $manager
     *
     * @return AccountUser
     * @throws \LogicException
     */
    protected function getAccountUser(ObjectManager $manager)
    {
        $accountUser = $manager->getRepository('OroB2BAccountBundle:AccountUser')
            ->findOneBy(['username' => LoadAccountUserData::AUTH_USER]);

        if (!$accountUser) {
            throw new \LogicException('Test account user not loaded');
        }

        return $accountUser;
    }

    /**
     * @return array
     */
    protected function getData()
    {
        return [
            self::SHOPPING_LIST_1 => ['total' => 2312, 'subtotal' => 91222],
            self::SHOPPING_LIST_2 => ['total' => 321, 'subtotal' => 5555],
            self::SHOPPING_LIST_3 => ['total' => 83, 'subtotal' => 422],
            self::SHOPPING_LIST_4 => ['total' => 32, 'subtotal' => 2464],
            self::SHOPPING_LIST_5 => ['total' => 466, 'subtotal' => 45354]
        ];
    }
}
