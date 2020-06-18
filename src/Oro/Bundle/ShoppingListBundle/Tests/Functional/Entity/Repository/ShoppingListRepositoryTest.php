<?php

namespace Oro\Bundle\ShoppingListBundle\Tests\Functional\Entity\Repository;

use Doctrine\Common\Collections\Criteria;
use Doctrine\ORM\Proxy\Proxy;
use Oro\Bundle\AttachmentBundle\Entity\File;
use Oro\Bundle\CatalogBundle\Entity\Category;
use Oro\Bundle\CatalogBundle\Tests\Functional\DataFixtures\LoadCategoryProductData;
use Oro\Bundle\CatalogBundle\Tests\Functional\DataFixtures\LoadCategoryWithEntityFallbackValuesData;
use Oro\Bundle\CustomerBundle\Entity\CustomerUser;
use Oro\Bundle\CustomerBundle\Tests\Functional\DataFixtures\LoadCustomerUserData;
use Oro\Bundle\EntityBundle\Entity\EntityFieldFallbackValue;
use Oro\Bundle\FrontendTestFrameworkBundle\Migrations\Data\ORM\LoadCustomerUserData as LoadAuthCustomerUserData;
use Oro\Bundle\FrontendTestFrameworkBundle\Test\WebsiteManagerTrait;
use Oro\Bundle\LocaleBundle\Entity\LocalizedFallbackValue;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\ProductBundle\Entity\ProductImage;
use Oro\Bundle\SecurityBundle\Authentication\Token\UsernamePasswordOrganizationToken;
use Oro\Bundle\SecurityBundle\ORM\Walker\AclHelper;
use Oro\Bundle\ShoppingListBundle\Entity\Repository\ShoppingListRepository;
use Oro\Bundle\ShoppingListBundle\Entity\ShoppingList;
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
    protected function setUp()
    {
        $this->initClient([], $this->generateBasicAuthHeader());
        $this->client->useHashNavigation(true);

        $this->setCurrentWebsite('default');

        $this->loadFixtures(
            [
                LoadShoppingListLineItems::class,
                LoadCategoryProductData::class,
                LoadCategoryWithEntityFallbackValuesData::class,
            ]
        );

        $this->customerUser = $this->getCustomerUser();
        $this->aclHelper = $this->getContainer()->get('oro_security.acl_helper');

        $this->client->getContainer()->get('security.token_storage')
            ->setToken($this->createToken($this->customerUser));
    }

    public function testFindAvailableForCustomerUser()
    {
        // Isset current shopping list
        $availableShoppingList = $this->getRepository()->findAvailableForCustomerUser($this->aclHelper);
        $this->assertInstanceOf(ShoppingList::class, $availableShoppingList);

        // the latest shopping list for current user
        $shoppingList = $this->getReference(LoadShoppingLists::SHOPPING_LIST_8);
        $this->assertSame($shoppingList, $availableShoppingList);
    }

    public function testFindByUser()
    {
        /** @var ShoppingList[] $shoppingLists */
        $shoppingLists = $this->getRepository()->findByUser($this->aclHelper, ['list.updatedAt' => Criteria::ASC]);
        $this->assertCount(6, $shoppingLists);

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
    private function getRepository()
    {
        return $this->getContainer()->get('doctrine')->getRepository(ShoppingList::class);
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

    public function testFindForViewAction(): void
    {
        $shoppingListRef = $this->getReference(LoadShoppingLists::SHOPPING_LIST_1);
        $shoppingList = $this->getRepository()->findForViewAction($shoppingListRef->getId());

        $this->assertNotNull($shoppingList);
        $this->assertTrue($shoppingList->getLineItems()->isInitialized());

        $lineItem = $shoppingList->getLineItems()->first();

        $product = $lineItem->getProduct();
        $this->assertProductLoaded($product);
        $this->assertProductNamesLoaded($product);
        $this->assertProductImagesLoaded($product);
        $this->assertCategoryLoaded($product->getCategory());
    }

    public function testFindForViewActionWhenConfigurableProduct(): void
    {
        $shoppingListRef = $this->getReference(LoadShoppingLists::SHOPPING_LIST_5);
        $shoppingList = $this->getRepository()->findForViewAction($shoppingListRef->getId());

        $this->assertNotNull($shoppingList);
        $this->assertTrue($shoppingList->getLineItems()->isInitialized());

        $lineItem = $shoppingList->getLineItems()->filter(
            static function ($lineItem) {
                return $lineItem->getParentProduct();
            }
        )->first();

        $product = $lineItem->getProduct();
        $this->assertProductLoaded($product);

        $parentProduct = $lineItem->getParentProduct();
        $this->assertProductLoaded($parentProduct);
        $this->assertProductNamesLoaded($parentProduct);
        $this->assertProductImagesLoaded($parentProduct);
        $this->assertProductImagesLoaded($product);
        $this->assertCategoryLoaded($parentProduct->getCategory());
    }

    public function testPreloadLineItemsByIdsForViewAction(): void
    {
        $this->getContainer()->get('oro_entity.doctrine_helper')
            ->getEntityManagerForClass(ShoppingList::class)
            ->clear();

        $lineItemRef = $this->getReference(LoadShoppingListLineItems::LINE_ITEM_4);

        $lineItems = $this->getRepository()->preloadLineItemsByIdsForViewAction([$lineItemRef->getId()]);

        $this->assertNotEmpty($lineItems);

        $product = $lineItems[0]->getProduct();
        $this->assertProductLoaded($product);
        $this->assertProductNamesLoaded($product);
        $this->assertProductImagesLoaded($product);
        $this->assertCategoryLoaded($product->getCategory());

        $shoppingListRef = $this->getReference(LoadShoppingLists::SHOPPING_LIST_5);
        $product = $shoppingListRef->getLineItems()->get(1)->getProduct();

        $this->assertInstanceOf(Product::class, $product);
        if ($product instanceof Proxy) {
            $this->assertFalse($product->__isInitialized());
        }
    }

    public function testPreloadLineItemsForViewAction(): void
    {
        $this->getContainer()->get('oro_entity.doctrine_helper')
            ->getEntityManagerForClass(ShoppingList::class)
            ->clear();

        $shoppingList = $this->getReference(LoadShoppingLists::SHOPPING_LIST_5);
        $lineItemRef = $this->getReference(LoadShoppingListLineItems::LINE_ITEM_4);

        $this->getRepository()->preloadLineItemsForViewAction([$lineItemRef]);

        $lineItem = $shoppingList->getLineItems()->get(0);

        $product = $lineItem->getProduct();
        $this->assertProductLoaded($product);
        $this->assertProductNamesLoaded($product);
        $this->assertProductImagesLoaded($product);
        $this->assertCategoryLoaded($product->getCategory());

        $lineItem = $shoppingList->getLineItems()->get(1);
        $product = $lineItem->getProduct();

        $this->assertInstanceOf(Product::class, $product);
        if ($product instanceof Proxy) {
            $this->assertFalse($product->__isInitialized());
        }
    }

    /**
     * @param Product $product
     */
    private function assertProductLoaded(Product $product): void
    {
        $this->assertNotProxyOrInitialized($product, Product::class);
        $this->assertNotProxyOrInitialized($product->getMinimumQuantityToOrder(), EntityFieldFallbackValue::class);
        $this->assertNotProxyOrInitialized($product->getMaximumQuantityToOrder(), EntityFieldFallbackValue::class);
        $this->assertNotProxyOrInitialized($product->getHighlightLowInventory(), EntityFieldFallbackValue::class);
        $this->assertNotProxyOrInitialized($product->getIsUpcoming(), EntityFieldFallbackValue::class);
    }

    /**
     * @param Product $product
     */
    private function assertProductNamesLoaded(Product $product): void
    {
        $localization = $this->getReference('en_CA');

        $this->assertTrue($product->getNames()->isInitialized());
        $this->assertNotProxyOrInitialized($product->getDefaultName(), LocalizedFallbackValue::class);
        $this->assertNotProxyOrInitialized($product->getName($localization), LocalizedFallbackValue::class);
    }

    /**
     * @param Product $product
     */
    private function assertProductImagesLoaded(Product $product): void
    {
        $this->assertTrue($product->getImages()->isInitialized());
        $productImage = $product->getImages()[0];
        $this->assertNotProxyOrInitialized($productImage, ProductImage::class);
        $this->assertNotProxyOrInitialized($productImage->getImage(), File::class);
        $this->assertTrue($productImage->getTypes()->isInitialized());
    }

    /**
     * @param Category $category
     */
    private function assertCategoryLoaded(Category $category): void
    {
        $this->assertNotProxyOrInitialized($category, Category::class);
        $this->assertNotProxyOrInitialized($category->getMinimumQuantityToOrder(), EntityFieldFallbackValue::class);
        $this->assertNotProxyOrInitialized($category->getMaximumQuantityToOrder(), EntityFieldFallbackValue::class);
        $this->assertNotProxyOrInitialized($category->getHighlightLowInventory(), EntityFieldFallbackValue::class);
        $this->assertNotProxyOrInitialized($category->getIsUpcoming(), EntityFieldFallbackValue::class);
    }

    public function testGetLineItemsCount(): void
    {
        $shoppingList1 = $this->getReference(LoadShoppingLists::SHOPPING_LIST_1);
        $shoppingList2 = $this->getReference(LoadShoppingLists::SHOPPING_LIST_5);

        $this->assertEquals(
            [
                $shoppingList1->getId() => 1,
                $shoppingList2->getId() => 3,
            ],
            $this->getRepository()->getLineItemsCount([$shoppingList1, $shoppingList2])
        );
    }

    public function testSetLineItemsCount(): void
    {
        $shoppingList = $this->getReference(LoadShoppingLists::SHOPPING_LIST_1);

        $this->assertEquals(1, $shoppingList->getLineItemsCount());
        $this->getRepository()->setLineItemsCount($shoppingList, 2);
        $this->assertEquals(2, $shoppingList->getLineItemsCount());
    }

    /**
     * @param object $value
     * @param string $expectedClass
     */
    private function assertNotProxyOrInitialized($value, string $expectedClass): void
    {
        if ($value !== null) {
            if ($value instanceof Proxy) {
                $this->assertTrue($value->__isInitialized());
            } else {
                $this->assertInstanceOf($expectedClass, $value);
            }
        }
    }
}
