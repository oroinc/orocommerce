<?php

namespace Oro\Bundle\ShoppingListBundle\Tests\Functional\Entity\Repository;

use Doctrine\Common\Collections\Criteria;
use Oro\Bundle\CustomerBundle\Entity\AccountUser;
use Oro\Bundle\FrontendTestFrameworkBundle\Migrations\Data\ORM\LoadAccountUserData;
use Oro\Bundle\SecurityBundle\ORM\Walker\AclHelper;
use Oro\Bundle\ShoppingListBundle\Entity\Repository\ShoppingListRepository;
use Oro\Bundle\ShoppingListBundle\Entity\ShoppingList;
use Oro\Bundle\ShoppingListBundle\Tests\Functional\DataFixtures\LoadShoppingLists;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;

/**
 * @dbIsolation
 */
class ShoppingListRepositoryTest extends WebTestCase
{
    /** @var AccountUser */
    protected $accountUser;

    /**
     * @var AclHelper
     */
    protected $aclHelper;

    protected function setUp()
    {
        $this->initClient([], $this->generateBasicAuthHeader());
        $this->client->useHashNavigation(true);

        $this->loadFixtures(
            [
                'Oro\Bundle\ShoppingListBundle\Tests\Functional\DataFixtures\LoadShoppingLists',
            ]
        );

        $this->accountUser = $this->getContainer()
            ->get('doctrine')
            ->getRepository('OroCustomerBundle:AccountUser')
            ->findOneBy(['username' => LoadAccountUserData::AUTH_USER]);

        $this->aclHelper = $this->getContainer()->get('oro_security.acl_helper');
    }

    public function testFindAvailableForAccountUser()
    {
        // Isset current shopping list
        $availableShoppingList = $this->getRepository()->findAvailableForAccountUser($this->aclHelper);
        $this->assertInstanceOf('Oro\Bundle\ShoppingListBundle\Entity\ShoppingList', $availableShoppingList);

        // the latest shopping list for current user
        $shoppingList = $this->getReference(LoadShoppingLists::SHOPPING_LIST_6);
        $this->assertEquals($shoppingList, $availableShoppingList);
    }

    public function testFindByUser()
    {
        $shoppingLists = $this->getRepository()->findByUser($this->aclHelper, ['list.updatedAt' => Criteria::ASC]);
        $this->assertTrue(count($shoppingLists) > 0);
        /** @var ShoppingList $secondShoppingList */
        $shoppingList = array_shift($shoppingLists);
        $this->assertInstanceOf('Oro\Bundle\ShoppingListBundle\Entity\ShoppingList', $shoppingList);
        $this->assertEquals($this->accountUser, $shoppingList->getAccountUser());
        /** @var ShoppingList $secondShoppingList */
        $secondShoppingList = array_shift($shoppingLists);
        $this->assertTrue($shoppingList->getUpdatedAt() <= $secondShoppingList->getUpdatedAt());
    }

    public function testFindByUserAndId()
    {
        /** @var ShoppingList $shoppingList */
        $shoppingListReference = $this->getReference(LoadShoppingLists::SHOPPING_LIST_1);
        $shoppingList = $this->getRepository()
            ->findByUserAndId($this->aclHelper, $shoppingListReference->getId());
        $this->assertInstanceOf('Oro\Bundle\ShoppingListBundle\Entity\ShoppingList', $shoppingList);
        $this->assertEquals($this->accountUser, $shoppingList->getAccountUser());
    }

    /**
     * @return AccountUser
     */
    public function getAccountUser()
    {
        return $this->getContainer()->get('doctrine')->getRepository('OroCustomerBundle:AccountUser')
            ->findOneBy(['username' => LoadAccountUserData::AUTH_USER]);
    }

    /**
     * @return ShoppingListRepository
     */
    protected function getRepository()
    {
        return $this->getContainer()->get('doctrine')->getRepository('OroShoppingListBundle:ShoppingList');
    }
}
