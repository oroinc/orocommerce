<?php

namespace Oro\Bundle\ShoppingListBundle\Tests\Functional\Entity\Repository;

use Doctrine\Common\Collections\Criteria;
use Oro\Bundle\CustomerBundle\Entity\CustomerUser;
use Oro\Bundle\CustomerBundle\Tests\Functional\DataFixtures\LoadCustomerUserData;
use Oro\Bundle\FrontendTestFrameworkBundle\Migrations\Data\ORM\LoadCustomerUserData as LoadAuthCustomerUserData;
use Oro\Bundle\SecurityBundle\Authentication\Token\UsernamePasswordOrganizationToken;
use Oro\Bundle\SecurityBundle\ORM\Walker\AclHelper;
use Oro\Bundle\ShoppingListBundle\Entity\Repository\ShoppingListRepository;
use Oro\Bundle\ShoppingListBundle\Entity\ShoppingList;
use Oro\Bundle\ShoppingListBundle\Tests\Functional\DataFixtures\LoadShoppingLists;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;
use Oro\Bundle\WebsiteBundle\Entity\Website;
use Oro\Bundle\WebsiteBundle\Tests\Functional\DataFixtures\LoadWebsiteData;

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
        $shoppingList = $this->getReference(LoadShoppingLists::SHOPPING_LIST_9);
        $this->assertSame($shoppingList, $availableShoppingList);

        /** @var Website $website */
        $website = $this->getReference(LoadWebsiteData::WEBSITE2);
        $this->assertNull($this->getRepository()->findAvailableForCustomerUser(
            $this->aclHelper,
            null,
            $website->getId()
        ));
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

        /** @var Website $website */
        $website = $this->getReference(LoadWebsiteData::WEBSITE3);
        $shoppingLists = $this->getRepository()->findByUser($this->aclHelper, [], [], $website->getId());
        $this->assertCount(1, $shoppingLists);
        $list = reset($shoppingLists);
        $this->assertEquals(LoadShoppingLists::SHOPPING_LIST_9 . '_label', $list->getLabel());
    }

    public function testFindByUserAndId()
    {
        /** @var ShoppingList $shoppingListReference */
        $shoppingListReference = $this->getReference(LoadShoppingLists::SHOPPING_LIST_1);
        $shoppingList = $this->getRepository()->findByUserAndId($this->aclHelper, $shoppingListReference->getId());

        $this->assertInstanceOf(ShoppingList::class, $shoppingList);
        $this->assertSame($this->customerUser, $shoppingList->getCustomerUser());
    }

    public function testFindByUserAndNonNumericalId()
    {
        $shoppingList = $this->getRepository()->findByUserAndId($this->aclHelper, 'abc');

        $this->assertNull($shoppingList);
    }

    /**
     * @return CustomerUser
     */
    public function getCustomerUser()
    {
        return $this->getContainer()
            ->get('doctrine')
            ->getRepository(CustomerUser::class)
            ->findOneBy(['username' => LoadAuthCustomerUserData::AUTH_USER]);
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

    public function testCountUserShoppingListsForDefaultWebsite()
    {
        $user = $this->getCustomerUser();

        $doctrineHelper = $this->getContainer()->get('oro_entity.doctrine_helper');
        $website = $doctrineHelper->getEntityRepositoryForClass(Website::class)->getDefaultWebsite();
        $count = $this->getRepository()->countUserShoppingLists(
            $user->getId(),
            $user->getOrganization()->getId(),
            $website
        );

        $this->assertEquals(6, $count);
    }

    public function testCountUserShoppingListsForCertainWebsite()
    {
        $user = $this->getCustomerUser();

        /** @var Website $website */
        $website = $this->getReference(LoadWebsiteData::WEBSITE3);
        $count = $this->getRepository()->countUserShoppingLists(
            $user->getId(),
            $user->getOrganization()->getId(),
            $website
        );

        $this->assertEquals(1, $count);
    }

    public function testGetRelatedEntitiesCount()
    {
        $customerUser = $this->getReference(LoadCustomerUserData::LEVEL_1_EMAIL);

        self::assertSame(1, $this->getRepository()->getRelatedEntitiesCount($customerUser));
    }

    public function testGetRelatedEntitiesCountZero()
    {
        $customerUserWithoutRelatedEntities = $this->getReference(LoadCustomerUserData::EMAIL);

        self::assertSame(0, $this->getRepository()->getRelatedEntitiesCount($customerUserWithoutRelatedEntities));
    }

    public function testResetCustomerUserForSomeEntities()
    {
        $customerUser = $this->getCustomerUser();
        $this->getRepository()->resetCustomerUser($customerUser, [
            $this->getReference(LoadShoppingLists::SHOPPING_LIST_1),
            $this->getReference(LoadShoppingLists::SHOPPING_LIST_2),
        ]);

        $shoppingLists = $this->getRepository()->findBy(['customerUser' => null]);
        $this->assertCount(2, $shoppingLists);
    }

    public function testResetCustomerUser()
    {
        $customerUser = $this->getCustomerUser();
        $this->getRepository()->resetCustomerUser($customerUser);

        $shoppingLists = $this->getRepository()->findBy(['customerUser' => null]);
        $this->assertCount(7, $shoppingLists);
    }
}
