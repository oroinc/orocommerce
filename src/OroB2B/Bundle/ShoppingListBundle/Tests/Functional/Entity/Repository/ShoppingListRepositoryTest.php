<?php

namespace OroB2B\Bundle\ShoppingListBundle\Tests\Functional\Entity\Repository;

use Doctrine\Common\Persistence\ObjectManager;

use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;
use Oro\Component\Testing\Fixtures\LoadAccountUserData;

use OroB2B\Bundle\CustomerBundle\Entity\AccountUser;
use OroB2B\Bundle\CustomerBundle\Entity\Customer;
use OroB2B\Bundle\ShoppingListBundle\Entity\Repository\ShoppingListRepository;
use OroB2B\Bundle\ShoppingListBundle\Entity\ShoppingList;

class ShoppingListRepositoryTest extends WebTestCase
{
    /**
     * {@inheritdoc}
     */
    protected function setUp()
    {
        $this->initClient([], array_merge($this->generateBasicAuthHeader(), ['HTTP_X-CSRF-Header' => 1]));

        $this->loadFixtures(
            [
                'OroB2B\Bundle\ShoppingListBundle\Tests\Functional\DataFixtures\LoadShoppingLists',
            ]
        );
    }

    public function testCreateFindForAccountUserQueryBuilder()
    {
        $repository = $this->getRepository();
        $account = $this->getAccountUser();

        $qb = $repository->createFindForAccountUserQueryBuilder($account);
        $this->assertInstanceOf('\Doctrine\ORM\QueryBuilder', $qb);

        /** @var ShoppingList[] $accountShoppingLists */
        $accountShoppingLists = $qb->getQuery()->execute();
        foreach ($accountShoppingLists as $shoppingList) {
            if ($shoppingList->getAccount() instanceof Customer) {
                $this->assertEquals($account->getCustomer()->getId(), $shoppingList->getAccount()->getId());
            } else {
                $this->assertEquals($account->getId(), $shoppingList->getAccountUser()->getId());
            }
        }
    }

    /**
     * @return AccountUser
     */
    public function getAccountUser()
    {
        return $this->getContainer()->get('doctrine')->getRepository('OroB2BCustomerBundle:AccountUser')
            ->findOneBy(['username' => LoadAccountUserData::AUTH_USER]);
    }

    /**
     * @return ShoppingListRepository
     */
    protected function getRepository()
    {
        return $this->getContainer()->get('doctrine')->getRepository('OroB2BShoppingListBundle:ShoppingList');
    }
}
