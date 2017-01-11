<?php

namespace Oro\Bundle\ShoppingListBundle\Tests\Functional\Entity\Repository;

use Doctrine\Common\Collections\Criteria;

use Oro\Bundle\CustomerBundle\Entity\CustomerUser;
use Oro\Bundle\FrontendTestFrameworkBundle\Migrations\Data\ORM\LoadCustomerUserData;
use Oro\Bundle\SecurityBundle\Authentication\Token\UsernamePasswordOrganizationToken;
use Oro\Bundle\ShoppingListBundle\Entity\Repository\ShoppingListRepository;
use Oro\Bundle\ShoppingListBundle\Entity\ShoppingList;
use Oro\Bundle\ShoppingListBundle\Tests\Functional\DataFixtures\LoadShoppingLists;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;
use Oro\Bundle\SecurityBundle\ORM\Walker\AclHelper;

/**
 * @dbIsolation
 */
class ShoppingListRepositoryTest extends WebTestCase
{
    /**
     * @var CustomerUser
     */
    protected $customerUser;

    /**
     * @var AclHelper
     */
    protected $aclHelper;

    /**
     * {@inheritdoc}
     */
    protected function setUp()
    {
        $this->initClient([], $this->generateBasicAuthHeader());
        $this->client->useHashNavigation(true);

        $this->loadFixtures([LoadShoppingLists::class]);

        $this->customerUser = $this->getCustomerUser();

        $token = $this->createToken($this->customerUser);

        $this->client->getContainer()->get('security.token_storage')->setToken($token);

        $this->aclHelper = $this->getContainer()->get('oro_security.acl_helper');
    }

    public function testFindAvailableForCustomerUser()
    {
        // Isset current shopping list
        $availableShoppingList = $this->getRepository()->findAvailableForCustomerUser($this->aclHelper);
        $this->assertInstanceOf(ShoppingList::class, $availableShoppingList);

        // the latest shopping list for current user
        $shoppingList = $this->getReference(LoadShoppingLists::SHOPPING_LIST_5);
        $this->assertSame($shoppingList, $availableShoppingList);
    }

    public function testFindByUser()
    {
        /** @var ShoppingList[] $shoppingLists */
        $shoppingLists = $this->getRepository()->findByUser($this->aclHelper, ['list.updatedAt' => Criteria::ASC]);
        $this->assertTrue(count($shoppingLists) > 0);

        $updatedAt = null;

        foreach ($shoppingLists as $shoppingList) {
            $this->assertInstanceOf(ShoppingList::class, $shoppingList);
            $this->assertSame($this->customerUser, $shoppingList->getCustomerUser());

            if ($updatedAt) {
                $this->assertTrue($updatedAt <= $shoppingList->getUpdatedAt());
            }

            $updatedAt = $shoppingList->getUpdatedAt();
        }
    }

    public function testFindByUserAndId()
    {
        /** @var ShoppingList $shoppingList */
        $shoppingListReference = $this->getReference(LoadShoppingLists::SHOPPING_LIST_1);
        $shoppingList = $this->getRepository()->findByUserAndId($this->aclHelper, $shoppingListReference->getId());

        $this->assertInstanceOf(ShoppingList::class, $shoppingList);
        $this->assertSame($this->customerUser, $shoppingList->getCustomerUser());
    }

    /**
     * @return CustomerUser
     */
    public function getCustomerUser()
    {
        return $this->getContainer()->get('doctrine')->getRepository(CustomerUser::class)
            ->findOneBy(['username' => LoadCustomerUserData::AUTH_USER]);
    }

    /**
     * @return ShoppingListRepository
     */
    protected function getRepository()
    {
        return $this->getContainer()->get('doctrine')->getRepository(ShoppingList::class);
    }

    /**
     * @param CustomerUser $customerUser
     * @return UsernamePasswordOrganizationToken
     */
    protected function createToken(CustomerUser $customerUser)
    {
        return new UsernamePasswordOrganizationToken(
            $customerUser,
            false,
            'k',
            $customerUser->getOrganization(),
            $customerUser->getRoles()
        );
    }
}
