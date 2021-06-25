<?php

namespace Oro\Bundle\ShoppingListBundle\Tests\Functional\Entity\Repository;

use Doctrine\Common\Collections\Criteria;
use Oro\Bundle\CatalogBundle\Tests\Functional\DataFixtures\LoadCategoryProductData;
use Oro\Bundle\CatalogBundle\Tests\Functional\DataFixtures\LoadCategoryWithEntityFallbackValuesData;
use Oro\Bundle\CustomerBundle\Entity\CustomerUser;
use Oro\Bundle\CustomerBundle\Tests\Functional\DataFixtures\LoadCustomerUserData;
use Oro\Bundle\FrontendTestFrameworkBundle\Migrations\Data\ORM\LoadCustomerUserData as LoadAuthCustomerUserData;
use Oro\Bundle\FrontendTestFrameworkBundle\Test\WebsiteManagerTrait;
use Oro\Bundle\SecurityBundle\Authentication\Token\UsernamePasswordOrganizationToken;
use Oro\Bundle\SecurityBundle\ORM\Walker\AclHelper;
use Oro\Bundle\ShoppingListBundle\Entity\Repository\ShoppingListRepository;
use Oro\Bundle\ShoppingListBundle\Entity\ShoppingList;
use Oro\Bundle\ShoppingListBundle\Tests\Functional\DataFixtures\LoadShoppingListConfigurableLineItems;
use Oro\Bundle\ShoppingListBundle\Tests\Functional\DataFixtures\LoadShoppingListLineItems;
use Oro\Bundle\ShoppingListBundle\Tests\Functional\DataFixtures\LoadShoppingLists;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;
use Oro\Bundle\WebsiteBundle\Entity\Website;
use Oro\Bundle\WebsiteBundle\Tests\Functional\DataFixtures\LoadWebsiteData;

/**
 * @SuppressWarnings(PHPMD.TooManyMethods)
 * @SuppressWarnings(PHPMD.TooManyPublicMethods)
 */
class ShoppingListRepositoryTest extends WebTestCase
{
    use WebsiteManagerTrait;

    /** @var CustomerUser */
    private $customerUser;

    /** @var AclHelper */
    private $aclHelper;

    /**
     * {@inheritdoc}
     */
    protected function setUp(): void
    {
        $this->initClient([], $this->generateBasicAuthHeader());
        $this->client->useHashNavigation(true);

        $this->setCurrentWebsite('default');

        $this->loadFixtures(
            [
                LoadShoppingListLineItems::class,
                LoadCategoryProductData::class,
                LoadCategoryWithEntityFallbackValuesData::class,
                LoadShoppingListConfigurableLineItems::class,
            ]
        );

        $this->customerUser = $this->getCustomerUser();
        $this->aclHelper = self::getContainer()->get('oro_security.acl_helper');

        $this->client->getContainer()->get('security.token_storage')
            ->setToken($this->createToken($this->customerUser));
    }

    public function testFindAvailableForCustomerUser()
    {
        // Isset current shopping list
        $availableShoppingList = $this->getRepository()->findAvailableForCustomerUser($this->aclHelper);
        self::assertInstanceOf(ShoppingList::class, $availableShoppingList);

        // the latest shopping list for current user
        $shoppingList = $this->getReference(LoadShoppingLists::SHOPPING_LIST_8);
        self::assertSame($shoppingList, $availableShoppingList);
    }

    public function testFindByUser()
    {
        /** @var ShoppingList[] $shoppingLists */
        $shoppingLists = $this->getRepository()->findByUser($this->aclHelper, ['list.updatedAt' => Criteria::ASC]);
        self::assertCount(6, $shoppingLists);

        $updatedAt = null;

        foreach ($shoppingLists as $shoppingList) {
            self::assertInstanceOf(ShoppingList::class, $shoppingList);
            self::assertSame($this->customerUser, $shoppingList->getCustomerUser());

            if ($updatedAt) {
                self::assertTrue($updatedAt <= $shoppingList->getUpdatedAt());
            }

            $updatedAt = $shoppingList->getUpdatedAt();
        }
    }

    public function testFindByCustomerUserId(): void
    {
        $customerUser = $this->getCustomerUser();
        /** @var ShoppingList[] $shoppingLists */
        $shoppingLists = $this->getRepository()->findByCustomerUserId(
            $customerUser->getId(),
            $this->aclHelper,
            ['list.updatedAt' => Criteria::ASC]
        );
        self::assertCount(6, $shoppingLists);

        $updatedAt = null;

        foreach ($shoppingLists as $shoppingList) {
            self::assertInstanceOf(ShoppingList::class, $shoppingList);
            self::assertSame($this->customerUser, $shoppingList->getCustomerUser());

            if ($updatedAt) {
                self::assertTrue($updatedAt <= $shoppingList->getUpdatedAt());
            }

            $updatedAt = $shoppingList->getUpdatedAt();
        }
    }

    public function testFindByUserAndId()
    {
        /** @var ShoppingList $shoppingListReference */
        $shoppingListReference = $this->getReference(LoadShoppingLists::SHOPPING_LIST_1);
        $shoppingList = $this->getRepository()->findByUserAndId($this->aclHelper, $shoppingListReference->getId());

        self::assertInstanceOf(ShoppingList::class, $shoppingList);
        self::assertSame($this->customerUser, $shoppingList->getCustomerUser());
    }

    public function testFindByUserAndNonNumericalId()
    {
        $shoppingList = $this->getRepository()->findByUserAndId($this->aclHelper, 'abc');

        self::assertNull($shoppingList);
    }

    /**
     * @return CustomerUser
     */
    public function getCustomerUser()
    {
        return self::getContainer()
            ->get('doctrine')
            ->getRepository(CustomerUser::class)
            ->findOneBy(['username' => LoadAuthCustomerUserData::AUTH_USER]);
    }

    /**
     * @return ShoppingListRepository
     */
    private function getRepository()
    {
        return self::getContainer()->get('doctrine')->getRepository(ShoppingList::class);
    }

    /**
     * @param CustomerUser $customerUser
     * @return UsernamePasswordOrganizationToken
     */
    private function createToken(CustomerUser $customerUser)
    {
        return new UsernamePasswordOrganizationToken(
            $customerUser,
            false,
            'k',
            $customerUser->getOrganization(),
            $customerUser->getUserRoles()
        );
    }

    public function testCountUserShoppingListsForDefaultWebsite()
    {
        $user = $this->getCustomerUser();

        $doctrineHelper = self::getContainer()->get('oro_entity.doctrine_helper');
        $website = $doctrineHelper->getEntityRepositoryForClass(Website::class)->getDefaultWebsite();
        $count = $this->getRepository()->countUserShoppingLists(
            $user->getId(),
            $user->getOrganization()->getId(),
            $website
        );

        self::assertEquals(6, $count);
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

        self::assertEquals(1, $count);
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
        self::assertCount(2, $shoppingLists);
    }

    public function testResetCustomerUser()
    {
        $customerUser = $this->getCustomerUser();
        $this->getRepository()->resetCustomerUser($customerUser);

        $shoppingLists = $this->getRepository()->findBy(['customerUser' => null]);
        self::assertCount(7, $shoppingLists);
    }

    public function testHasEmptyConfigurableLineItems(): void
    {
        $shoppingListRepository = $this->getRepository();

        self::assertTrue(
            $shoppingListRepository->hasEmptyConfigurableLineItems(
                $this->getReference(LoadShoppingLists::SHOPPING_LIST_2)
            )
        );

        self::assertFalse(
            $shoppingListRepository->hasEmptyConfigurableLineItems(
                $this->getReference(LoadShoppingLists::SHOPPING_LIST_1)
            )
        );
    }
}
