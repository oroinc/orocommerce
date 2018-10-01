<?php

namespace Oro\Bundle\ShoppingListBundle\Tests\Unit\Manager;

use Doctrine\Common\Cache\Cache;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Persistence\ManagerRegistry;
use Doctrine\Common\Persistence\ObjectManager;
use Doctrine\ORM\EntityManager;

use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Translation\TranslatorInterface;

use Oro\Bundle\CustomerBundle\Entity\Customer;
use Oro\Bundle\CustomerBundle\Entity\CustomerUser;
use Oro\Bundle\OrganizationBundle\Entity\Organization;
use Oro\Bundle\PricingBundle\Manager\UserCurrencyManager;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\ProductBundle\Entity\ProductUnit;
use Oro\Bundle\ProductBundle\Provider\ProductVariantAvailabilityProvider;
use Oro\Bundle\ProductBundle\Rounding\QuantityRoundingService;
use Oro\Bundle\SecurityBundle\ORM\Walker\AclHelper;
use Oro\Bundle\ShoppingListBundle\Entity\LineItem;
use Oro\Bundle\ShoppingListBundle\Entity\Repository\LineItemRepository;
use Oro\Bundle\ShoppingListBundle\Entity\Repository\ShoppingListRepository;
use Oro\Bundle\ShoppingListBundle\Entity\ShoppingList;
use Oro\Bundle\ShoppingListBundle\Manager\GuestShoppingListManager;
use Oro\Bundle\ShoppingListBundle\Manager\ShoppingListManager;
use Oro\Bundle\ShoppingListBundle\Manager\ShoppingListTotalManager;
use Oro\Bundle\WebsiteBundle\Entity\Website;
use Oro\Bundle\WebsiteBundle\Manager\WebsiteManager;
use Oro\Component\Testing\Unit\EntityTrait;

/**
 * @Todo: Must be refactored in scope of - #BB-10192
 * @SuppressWarnings(PHPMD.TooManyMethods)
 * @SuppressWarnings(PHPMD.TooManyPublicMethods)
 * @SuppressWarnings(PHPMD.ExcessiveClassLength)
 * @SuppressWarnings(PHPMD.ExcessiveClassComplexity)
 */
class ShoppingListManagerTest extends \PHPUnit_Framework_TestCase
{
    const CURRENCY_EUR = 'EUR';
    use EntityTrait;

    /**
     * @var ShoppingList
     */
    protected $shoppingListOne;

    /**
     * @var ShoppingList
     */
    protected $shoppingListTwo;

    /**
     * @var ShoppingListManager
     */
    protected $manager;

    /**
     * @var ShoppingList[]
     */
    protected $shoppingLists = [];

    /**
     * @var LineItem[]
     */
    protected $lineItems = [];

    /**
     * @var ManagerRegistry|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $registry = [];

    /**
     * @var AclHelper
     */
    protected $aclHelper;

    /**
     * @var TokenInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $securityToken;

    /**
     * @var ShoppingListTotalManager|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $totalManager;

    /**
     * @var Cache|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $cache;

    /**
     * @var ProductVariantAvailabilityProvider|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $productVariantProvider;

    /**
     * @var GuestShoppingListManager|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $guestShoppingListManager;

    /**
     * @var ShoppingListRepository|\PHPUnit_Framework_MockObject_MockObject
     */
    private $shoppingListRepository;

    protected function setUp()
    {
        $this->shoppingListOne = $this->getShoppingList(1, true);
        $this->shoppingListTwo = $this->getShoppingList(2, false);

        $this->aclHelper = $this->getAclHelperMock();

        $tokenStorage = $this->getTokenStorage(
            (new CustomerUser())
                ->setFirstName('skip')
                ->setCustomer(new Customer())
                ->setOrganization(new Organization())
        );

        $this->registry = $this->getManagerRegistry();
        $this->cache = $this->createMock(Cache::class);
        $this->totalManager = $this->getShoppingListTotalManager();
        $this->productVariantProvider = $this->createMock(ProductVariantAvailabilityProvider::class);

        $this->manager = new ShoppingListManager(
            $this->registry,
            $tokenStorage,
            $this->getTranslator(),
            $this->getRoundingService(),
            $this->getUserCurrencyManager(),
            $this->getWebsiteManager(),
            $this->totalManager,
            $this->aclHelper,
            $this->cache,
            $this->productVariantProvider
        );

        $this->guestShoppingListManager = $this->createMock(GuestShoppingListManager::class);
        $this->manager->setGuestShoppingListManager($this->guestShoppingListManager);
    }

    public function testCreate()
    {
        $shoppingList = $this->manager->create();

        $this->assertInstanceOf('Oro\Bundle\ShoppingListBundle\Entity\ShoppingList', $shoppingList);
        $this->assertInstanceOf('Oro\Bundle\CustomerBundle\Entity\Customer', $shoppingList->getCustomer());
        $this->assertInstanceOf('Oro\Bundle\CustomerBundle\Entity\CustomerUser', $shoppingList->getCustomerUser());
        $this->assertInstanceOf('Oro\Bundle\OrganizationBundle\Entity\Organization', $shoppingList->getOrganization());
    }

    public function testCreateCurrent()
    {
        $this->manager->setCurrent(
            (new CustomerUser())->setFirstName('setCurrent'),
            $this->shoppingListTwo
        );
        $this->assertTrue($this->shoppingListTwo->isCurrent());
    }

    public function testSetCurrent()
    {
        $this->assertEmpty($this->shoppingLists);
        $this->manager->createCurrent();
        $this->assertCount(1, $this->shoppingLists);
        /** @var ShoppingList $list */
        $list = array_shift($this->shoppingLists);
        $this->assertTrue($list->isCurrent());
    }

    public function testAddLineItem()
    {
        $shoppingList = new ShoppingList();
        $lineItem = new LineItem();
        $this->manager->addLineItem($lineItem, $shoppingList);
        $this->assertEquals(1, $shoppingList->getLineItems()->count());
        $this->assertEquals(null, $lineItem->getCustomerUser());
        $this->assertEquals(null, $lineItem->getOrganization());
    }

    public function testAddLineItemWithShoppingListData()
    {
        $shoppingList = new ShoppingList();
        $userName = 'Bob';
        $organizationName = 'Organization';
        $accountUser = (new CustomerUser())->setFirstName($userName);
        $shoppingList->setCustomerUser($accountUser);
        $organization = (new Organization())->setName($organizationName);
        $shoppingList->setOrganization($organization);

        $lineItem = new LineItem();
        $this->manager->addLineItem($lineItem, $shoppingList);
        $this->assertEquals($userName, $lineItem->getCustomerUser()->getFirstName());
        $this->assertEquals($organizationName, $lineItem->getOrganization()->getName());
    }

    public function testAddLineItemDuplicate()
    {
        $shoppingList = new ShoppingList();
        $reflectionClass = new \ReflectionClass(get_class($shoppingList));
        $reflectionProperty = $reflectionClass->getProperty('id');
        $reflectionProperty->setAccessible(true);
        $reflectionProperty->setValue($shoppingList, 1);

        $lineItem = (new LineItem())
            ->setUnit(
                (new ProductUnit())
                    ->setCode('test')
                    ->setDefaultPrecision(1)
            )
            ->setQuantity(10);

        $this->manager->addLineItem($lineItem, $shoppingList);
        $this->assertEquals(1, $shoppingList->getLineItems()->count());
        $this->assertEquals(1, count($this->lineItems));
        $lineItemDuplicate = clone $lineItem;
        $lineItemDuplicate->setQuantity(5);
        $this->manager->addLineItem($lineItemDuplicate, $shoppingList);
        $this->assertEquals(1, $shoppingList->getLineItems()->count());
        /** @var LineItem $resultingItem */
        $resultingItem = array_shift($this->lineItems);
        $this->assertEquals(15, $resultingItem->getQuantity());
    }

    public function testAddLineItemDuplicateAndConcatNotes()
    {
        $shoppingList = new ShoppingList();
        $reflectionClass = new \ReflectionClass(get_class($shoppingList));
        $reflectionProperty = $reflectionClass->getProperty('id');
        $reflectionProperty->setAccessible(true);
        $reflectionProperty->setValue($shoppingList, 1);

        $lineItem = (new LineItem())
            ->setUnit(
                (new ProductUnit())
                    ->setCode('test')
                    ->setDefaultPrecision(1)
            )
            ->setNotes('Notes');

        $this->manager->addLineItem($lineItem, $shoppingList);

        $lineItemDuplicate = clone $lineItem;
        $lineItemDuplicate->setNotes('Duplicated Notes');

        $this->manager->addLineItem($lineItemDuplicate, $shoppingList, true, true);

        $this->assertEquals(1, $shoppingList->getLineItems()->count());

        /** @var LineItem $resultingItem */
        $resultingItem = array_shift($this->lineItems);
        $this->assertSame('Notes Duplicated Notes', $resultingItem->getNotes());
    }

    /**
     * @expectedException \InvalidArgumentException
     * @expectedExceptionMessage Can not save not simple product
     */
    public function testAddLineItemNotAllowedProductType()
    {
        $shoppingList = new ShoppingList();
        $lineItem = new LineItem();
        $configurableProduct = new Product();
        $configurableProduct->setType(Product::TYPE_CONFIGURABLE);
        $lineItem->setProduct($configurableProduct);

        $this->manager->addLineItem($lineItem, $shoppingList);
    }

    /**
     * @dataProvider removeProductDataProvider
     *
     * @param array $lineItems
     * @param array $relatedLineItems
     * @param bool $flush
     * @param bool $expectedFlush
     */
    public function testRemoveProduct(array $lineItems, array $relatedLineItems, $flush, $expectedFlush)
    {
        /** @var Product $product */
        $product = $this->getEntity('Oro\Bundle\ProductBundle\Entity\Product', ['id' => 42]);

        foreach ($lineItems as $lineItem) {
            $this->shoppingListOne->addLineItem($lineItem);
        }

        /** @var ObjectManager|\PHPUnit_Framework_MockObject_MockObject $manager */
        $manager = $this->registry->getManagerForClass('OroShoppingListBundle:LineItem');
        $manager->expects($this->exactly(count($relatedLineItems)))
            ->method('remove')
            ->willReturnCallback(
                function (LineItem $item) {
                    $this->lineItems[] = $item;
                }
            );
        $manager->expects($expectedFlush ? $this->once() : $this->never())->method('flush');

        /** @var LineItemRepository|\PHPUnit_Framework_MockObject_MockObject $repository */
        $repository = $manager->getRepository('OroShoppingListBundle:LineItem');
        $repository->expects($this->once())
            ->method('getItemsByShoppingListAndProducts')
            ->with($this->shoppingListOne, [$product])
            ->willReturn($relatedLineItems);

        $result = $this->manager->removeProduct($this->shoppingListOne, $product, $flush);

        $this->assertEquals(count($relatedLineItems), $result);

        foreach ($relatedLineItems as $lineItem) {
            $this->assertContains($lineItem, $this->lineItems);
            $this->assertNotContains($lineItem, $this->shoppingListOne->getLineItems());
        }

        $this->assertEquals(
            count($lineItems) - count($relatedLineItems),
            $this->shoppingListOne->getLineItems()->count()
        );
    }

    /**
     * @return array
     */
    public function removeProductDataProvider()
    {
        /** @var LineItem $lineItem1 */
        $lineItem1 = $this->getEntity('Oro\Bundle\ShoppingListBundle\Entity\LineItem', ['id' => 35]);

        /** @var LineItem $lineItem2 */
        $lineItem2 = $this->getEntity('Oro\Bundle\ShoppingListBundle\Entity\LineItem', ['id' => 36]);

        /** @var LineItem $lineItem3 */
        $lineItem3 = $this->getEntity('Oro\Bundle\ShoppingListBundle\Entity\LineItem', ['id' => 37]);

        return [
            [
                'lineItems' => [$lineItem1, $lineItem2, $lineItem3],
                'relatedLineItems' => [$lineItem1, $lineItem3],
                'flush' => true,
                'expectedFlush' => true
            ],
            [
                'lineItems' => [$lineItem1, $lineItem2, $lineItem3],
                'relatedLineItems' => [],
                'flush' => true,
                'expectedFlush' => false
            ],
            [
                'lineItems' => [$lineItem1, $lineItem2, $lineItem3],
                'relatedLineItems' => [$lineItem2],
                'flush' => false,
                'expectedFlush' => false
            ]
        ];
    }

    /**
     * @param array           $simpleProducts
     * @param ArrayCollection $lineItems
     *
     * @dataProvider getSimpleProductsProvider
     */
    public function testRemoveConfigurableProduct($simpleProducts, ArrayCollection $lineItems)
    {
        /** @var Product $product */
        $product = $this->getEntity(
            Product::class,
            ['id' => 43, 'type' => Product::TYPE_CONFIGURABLE]
        );
        /** @var ShoppingList $shoppingList */
        $shoppingList = $this->getEntity(ShoppingList::class, ['lineItems' => $lineItems]);

        $this->productVariantProvider->expects($this->once())
            ->method('getSimpleProductsByVariantFields')
            ->with($product)
            ->willReturn($simpleProducts);

        /** @var ObjectManager|\PHPUnit_Framework_MockObject_MockObject $manager */
        $manager = $this->registry->getManagerForClass('OroShoppingListBundle:LineItem');
        $manager->expects($this->exactly(count($lineItems)))
            ->method('remove');

        $products = $simpleProducts;
        $products[] = $product;

        /** @var LineItemRepository|\PHPUnit_Framework_MockObject_MockObject $repository */
        $repository = $manager->getRepository('OroShoppingListBundle:LineItem');
        $repository->expects($this->once())
            ->method('getItemsByShoppingListAndProducts')
            ->with($shoppingList, $products)
            ->willReturn($lineItems);

        $result = $this->manager->removeProduct($shoppingList, $product, true);
        $this->assertEquals(count($lineItems), $result);
        $this->assertTrue($shoppingList->getLineItems()->isEmpty());
    }

    /**
     * @return array
     */
    public function getSimpleProductsProvider()
    {
        return [
            [
                [],
                new ArrayCollection()
            ],
            [
                [
                    $this->getEntity(Product::class, ['id' => 44, 'type' => Product::TYPE_SIMPLE]),
                    $this->getEntity(Product::class, ['id' => 45, 'type' => Product::TYPE_SIMPLE]),
                    $this->getEntity(Product::class, ['id' => 46, 'type' => Product::TYPE_SIMPLE])
                ],
                new ArrayCollection(
                    [
                        $this->getEntity(LineItem::class, ['id' => 38]),
                        $this->getEntity(LineItem::class, ['id' => 39]),
                        $this->getEntity(LineItem::class, ['id' => 40])
                    ]
                )
            ]
        ];
    }

    public function testGetForCurrentUser()
    {
        $shoppingList = $this->manager->getForCurrentUser();
        $this->assertInstanceOf('Oro\Bundle\ShoppingListBundle\Entity\ShoppingList', $shoppingList);
    }

    public function testGetForCurrentUserGuestShoppingList()
    {
        $this->guestShoppingListManager->expects($this->once())
            ->method('isGuestShoppingListAvailable')
            ->willReturn(true);

        $guestShoppingList = $this->getEntity(ShoppingList::class, ['id' => 31]);
        $this->guestShoppingListManager->expects($this->once())
            ->method('createAndGetShoppingListForCustomerVisitor')
            ->willReturn($guestShoppingList);

        $this->assertSame($guestShoppingList, $this->manager->getForCurrentUser());
    }

    public function testGetForCurrentUserWithShoppingListIdShoppingListExists()
    {
        $this->guestShoppingListManager->expects($this->once())
            ->method('isGuestShoppingListAvailable')
            ->willReturn(false);

        $existingShoppingList = $this->getEntity(ShoppingList::class, ['id' => 35]);

        $this->shoppingListRepository->expects($this->once())
            ->method('findByUserAndId')
            ->with($this->aclHelper, 35, null)
            ->willReturn($existingShoppingList);

        $this->assertSame($existingShoppingList, $this->manager->getForCurrentUser(35));
    }

    public function testGetForCurrentUserWithShoppingListIdShoppingListDoesntExist()
    {
        $customerUser = (new CustomerUser())
            ->setOrganization(new Organization())
            ->setCustomer(new Customer());

        $websiteManager = $this->getWebsiteManager();

        $manager = new ShoppingListManager(
            $this->registry,
            $this->getTokenStorage($customerUser),
            $this->getTranslator(),
            $this->getRoundingService(),
            $this->getUserCurrencyManager(),
            $websiteManager,
            $this->getShoppingListTotalManager(),
            $this->aclHelper,
            $this->cache,
            $this->productVariantProvider
        );
        $manager->setGuestShoppingListManager($this->guestShoppingListManager);

        $this->guestShoppingListManager->expects($this->exactly(2))
            ->method('isGuestShoppingListAvailable')
            ->willReturn(false);

        $this->shoppingListRepository->expects($this->once())
            ->method('findByUserAndId')
            ->with($this->aclHelper, 35, null)
            ->willReturn(null);

        $newShoppingList = $this->getEntity(ShoppingList::class, [
            'id' => null,
            'website' => $websiteManager->getCurrentWebsite(),
            'current' => true,
            'customerUser' => $customerUser,
            'customer' => new Customer(),
            'organization' => new Organization(),
        ]);

        $this->assertEquals($newShoppingList, $manager->getForCurrentUser(35));
    }

    public function testGetForCurrentUserNoShoppingListId()
    {
        $customerUser = (new CustomerUser())
            ->setOrganization(new Organization())
            ->setCustomer(new Customer());

        $websiteManager = $this->getWebsiteManager();

        $manager = new ShoppingListManager(
            $this->registry,
            $this->getTokenStorage($customerUser),
            $this->getTranslator(),
            $this->getRoundingService(),
            $this->getUserCurrencyManager(),
            $websiteManager,
            $this->getShoppingListTotalManager(),
            $this->aclHelper,
            $this->cache,
            $this->productVariantProvider
        );
        $manager->setGuestShoppingListManager($this->guestShoppingListManager);

        $this->guestShoppingListManager->expects($this->exactly(2))
            ->method('isGuestShoppingListAvailable')
            ->willReturn(false);

        $this->shoppingListRepository->expects($this->never())
            ->method('findByUserAndId');

        $newShoppingList = $this->getEntity(ShoppingList::class, [
            'id' => null,
            'website' => $websiteManager->getCurrentWebsite(),
            'current' => true,
            'customerUser' => $customerUser,
            'customer' => new Customer(),
            'organization' => new Organization(),
        ]);

        $this->assertEquals($newShoppingList, $manager->getForCurrentUser());
    }

    public function testGetCurrentGuestShoppingListCreate()
    {
        $this->guestShoppingListManager->expects($this->once())
            ->method('isGuestShoppingListAvailable')
            ->willReturn(true);

        $newShoppingList = $this->getEntity(ShoppingList::class, [
            'id' => 31,
            'label' => 'Default Shopping List Label'
        ]);
        $this->guestShoppingListManager->expects($this->once())
            ->method('createAndGetShoppingListForCustomerVisitor')
            ->willReturn($newShoppingList);

        $this->guestShoppingListManager->expects($this->never())
            ->method('getShoppingListForCustomerVisitor');

        $this->shoppingListRepository->expects($this->never())
            ->method('findByUserAndId');

        $this->shoppingListRepository->expects($this->never())
            ->method('findAvailableForCustomerUser');

        $this->assertSame(
            $newShoppingList,
            $this->manager->getCurrent($create = true, $label = 'New Shopping List Label')
        );
    }

    public function testGetCurrentGuestShoppingListDontCreate()
    {
        $this->guestShoppingListManager->expects($this->once())
            ->method('isGuestShoppingListAvailable')
            ->willReturn(true);

        $existingShoppingList = $this->getEntity(ShoppingList::class, [
            'id' => 31,
            'label' => 'Existing Shopping List Label'
        ]);
        $this->guestShoppingListManager->expects($this->once())
            ->method('getShoppingListForCustomerVisitor')
            ->willReturn($existingShoppingList);

        $this->guestShoppingListManager->expects($this->never())
            ->method('createAndGetShoppingListForCustomerVisitor');

        $this->shoppingListRepository->expects($this->never())
            ->method('findByUserAndId');

        $this->shoppingListRepository->expects($this->never())
            ->method('findAvailableForCustomerUser');

        $this->assertSame(
            $existingShoppingList,
            $this->manager->getCurrent()
        );
    }

    public function testGetCurrentGuestShoppingListDontCreateNoExistingShoppingList()
    {
        $this->guestShoppingListManager->expects($this->once())
            ->method('isGuestShoppingListAvailable')
            ->willReturn(true);

        $this->guestShoppingListManager->expects($this->once())
            ->method('getShoppingListForCustomerVisitor')
            ->willReturn(null);

        $this->guestShoppingListManager->expects($this->never())
            ->method('createAndGetShoppingListForCustomerVisitor');

        $this->shoppingListRepository->expects($this->never())
            ->method('findByUserAndId');

        $this->shoppingListRepository->expects($this->never())
            ->method('findAvailableForCustomerUser');

        $this->assertNull($this->manager->getCurrent());
    }

    public function testGetCurrentNoGuestShoppingListNoCustomerUser()
    {
        $websiteManager = $this->getWebsiteManager();

        $manager = new ShoppingListManager(
            $this->registry,
            $this->getTokenStorage(null),
            $this->getTranslator(),
            $this->getRoundingService(),
            $this->getUserCurrencyManager(),
            $websiteManager,
            $this->getShoppingListTotalManager(),
            $this->aclHelper,
            $this->cache,
            $this->productVariantProvider
        );
        $manager->setGuestShoppingListManager($this->guestShoppingListManager);

        $this->guestShoppingListManager->expects($this->once())
            ->method('isGuestShoppingListAvailable')
            ->willReturn(false);

        $this->guestShoppingListManager->expects($this->never())
            ->method('getShoppingListForCustomerVisitor');

        $this->guestShoppingListManager->expects($this->never())
            ->method('createAndGetShoppingListForCustomerVisitor');

        $this->shoppingListRepository->expects($this->never())
            ->method('findByUserAndId');

        $this->shoppingListRepository->expects($this->never())
            ->method('findAvailableForCustomerUser');

        $this->assertNull($manager->getCurrent());
    }

    public function testGetCurrentNoGuestShoppingList()
    {
        $this->guestShoppingListManager->expects($this->once())
            ->method('isGuestShoppingListAvailable')
            ->willReturn(false);

        $this->guestShoppingListManager->expects($this->never())
            ->method('getShoppingListForCustomerVisitor');

        $this->guestShoppingListManager->expects($this->never())
            ->method('createAndGetShoppingListForCustomerVisitor');

        $existingShoppingList = $this->getEntity(ShoppingList::class, [
            'id' => 31,
            'label' => 'Existing Shopping List Label'
        ]);

        $this->shoppingListRepository->expects($this->never())
            ->method('findByUserAndId');

        $this->shoppingListRepository->expects($this->once())
            ->method('findAvailableForCustomerUser')
            ->willReturn($existingShoppingList);

        $this->assertSame($existingShoppingList, $this->manager->getCurrent());
    }

    public function testGetCurrentNoGuestShoppingListWithCreateWithExistingShoppingList()
    {
        $this->guestShoppingListManager->expects($this->once())
            ->method('isGuestShoppingListAvailable')
            ->willReturn(false);

        $this->guestShoppingListManager->expects($this->never())
            ->method('getShoppingListForCustomerVisitor');

        $this->guestShoppingListManager->expects($this->never())
            ->method('createAndGetShoppingListForCustomerVisitor');

        $existingShoppingList = $this->getEntity(ShoppingList::class, [
            'id' => 31,
            'label' => 'Existing Shopping List Label'
        ]);

        $this->cache->expects($this->once())
            ->method('fetch')
            ->willReturn(13);

        $this->shoppingListRepository->expects($this->once())
            ->method('findByUserAndId')
            ->with($this->aclHelper, 13, null)
            ->willReturn($existingShoppingList);

        $this->shoppingListRepository->expects($this->never())
            ->method('findAvailableForCustomerUser');

        $this->assertSame(
            $existingShoppingList,
            $this->manager->getCurrent($create = true, $label = 'New Shopping List Label')
        );
    }

    public function testGetCurrentNoGuestShoppingListWithCreateWithAvailableShoppingList()
    {
        $this->guestShoppingListManager->expects($this->once())
            ->method('isGuestShoppingListAvailable')
            ->willReturn(false);

        $this->guestShoppingListManager->expects($this->never())
            ->method('getShoppingListForCustomerVisitor');

        $this->guestShoppingListManager->expects($this->never())
            ->method('createAndGetShoppingListForCustomerVisitor');

        $existingShoppingList = $this->getEntity(ShoppingList::class, [
            'id' => 31,
            'label' => 'Existing Shopping List Label'
        ]);

        $this->shoppingListRepository->expects($this->never())
            ->method('findByUserAndId');

        $this->shoppingListRepository->expects($this->once())
            ->method('findAvailableForCustomerUser')
            ->willReturn($existingShoppingList);

        $this->assertSame(
            $existingShoppingList,
            $this->manager->getCurrent($create = true, $label = 'New Shopping List Label')
        );
    }

    public function testGetCurrentNoGuestShoppingListWithCreateWithoutExistingShoppingList()
    {
        $customerUser = (new CustomerUser())
            ->setOrganization(new Organization())
            ->setCustomer(new Customer());

        $websiteManager = $this->getWebsiteManager();

        $manager = new ShoppingListManager(
            $this->registry,
            $this->getTokenStorage($customerUser),
            $this->getTranslator(),
            $this->getRoundingService(),
            $this->getUserCurrencyManager(),
            $websiteManager,
            $this->getShoppingListTotalManager(),
            $this->aclHelper,
            $this->cache,
            $this->productVariantProvider
        );
        $manager->setGuestShoppingListManager($this->guestShoppingListManager);

        $this->guestShoppingListManager->expects($this->once())
            ->method('isGuestShoppingListAvailable')
            ->willReturn(false);

        $this->guestShoppingListManager->expects($this->never())
            ->method('getShoppingListForCustomerVisitor');

        $this->guestShoppingListManager->expects($this->never())
            ->method('createAndGetShoppingListForCustomerVisitor');

        $newShoppingList = $this->getEntity(ShoppingList::class, [
            'id' => null,
            'website' => $websiteManager->getCurrentWebsite(),
            'current' => true,
            'customerUser' => $customerUser,
            'customer' => new Customer(),
            'organization' => new Organization(),
        ]);

        $this->shoppingListRepository->expects($this->never())
            ->method('findByUserAndId');

        $this->shoppingListRepository->expects($this->once())
            ->method('findAvailableForCustomerUser')
            ->willReturn(null);

        $this->assertEquals(
            $newShoppingList,
            $manager->getCurrent($create = true, $label = 'New Shopping List Label')
        );
    }

    public function testBulkAddLineItems()
    {
        $shoppingList = new ShoppingList();
        $lineItems = [];
        for ($i = 0; $i < 10; $i++) {
            $lineItems[] = new LineItem();
        }

        $this->manager->bulkAddLineItems($lineItems, $shoppingList, 10);
        $this->assertEquals(10, $shoppingList->getLineItems()->count());
    }

    public function testBulkAddLineItemsWithEmptyLineItems()
    {
        $this->assertEquals(0, $this->manager->bulkAddLineItems([], new ShoppingList(), 10));
    }


    /**
     * @dataProvider getShoppingListsDataProvider
     *
     * @param array $shoppingLists
     * @param array $expectedResult
     */
    public function testGetShoppingListsGuestShoppingListExists($shoppingLists, $expectedResult)
    {
        $this->guestShoppingListManager->expects($this->once())
            ->method('isGuestShoppingListAvailable')
            ->willReturn(true);

        $this->guestShoppingListManager->expects($this->once())
            ->method('getShoppingListsForCustomerVisitor')
            ->willReturn($shoppingLists);

        $this->assertEquals($expectedResult, $this->manager->getShoppingLists());
    }

    /**
     * @return array
     */
    public function getShoppingListsDataProvider()
    {
        $existingShoppingList = $this->getEntity(ShoppingList::class, [
            'id' => 31,
            'label' => 'Existing Shopping List Label'
        ]);

        return [
            'shopping list exists'=> [
                'shoppingLists' => [$existingShoppingList],
                'expectedResults' => [$existingShoppingList]
            ],
            'shopping list doesnt exist'=> [
                'shoppingLists' => [],
                'expectedResults' => []
            ]
        ];
    }

    public function testGetShoppingListsRegisteredUser()
    {
        $this->guestShoppingListManager->expects($this->once())
            ->method('isGuestShoppingListAvailable')
            ->willReturn(false);

        $this->guestShoppingListManager->expects($this->never())
            ->method('getShoppingListForCustomerVisitor');

        $user = new CustomerUser();

        $shoppingList1 = $this->getShoppingList(10, false);
        $shoppingList2 = $this->getShoppingList(20, false);
        $shoppingList3 = $this->getShoppingList(30, true);

        /* @var $repository ShoppingListRepository|\PHPUnit_Framework_MockObject_MockObject */
        $repository = $this->getMockBuilder('Oro\Bundle\ShoppingListBundle\Entity\Repository\ShoppingListRepository')
            ->disableOriginalConstructor()
            ->getMock();
        $repository->expects($this->once())
            ->method('findByUser')
            ->with($this->aclHelper)
            ->willReturn([$shoppingList3, $shoppingList1, $shoppingList2]);

        /* @var $entityManager EntityManager|\PHPUnit_Framework_MockObject_MockObject */
        $entityManager = $this->getMockBuilder('Doctrine\ORM\EntityManager')
            ->disableOriginalConstructor()
            ->getMock();

        $entityManager->expects($this->any())
            ->method('getRepository')
            ->willReturn($repository);

        /* @var $registry ManagerRegistry|\PHPUnit_Framework_MockObject_MockObject */
        $registry = $this->createMock('Doctrine\Common\Persistence\ManagerRegistry');
        $registry->expects($this->once())
            ->method('getManagerForClass')
            ->willReturn($entityManager);

        $manager = new ShoppingListManager(
            $registry,
            $this->getTokenStorage($user),
            $this->getTranslator(),
            $this->getRoundingService(),
            $this->getUserCurrencyManager(),
            $this->getWebsiteManager(),
            $this->getShoppingListTotalManager(),
            $this->aclHelper,
            $this->cache,
            $this->productVariantProvider
        );
        $manager->setGuestShoppingListManager($this->guestShoppingListManager);

        $this->assertEquals(
            [$shoppingList3, $shoppingList1, $shoppingList2],
            $manager->getShoppingLists()
        );
    }

    /**
     * @dataProvider getShoppingListsDataProvider
     *
     * @param array $shoppingLists
     * @param array $expectedResult
     */
    public function testGetShoppingListsWithCurrentFirst($shoppingLists, $expectedResult)
    {
        $this->guestShoppingListManager->expects($this->once())
            ->method('isGuestShoppingListAvailable')
            ->willReturn(true);

        $this->guestShoppingListManager->expects($this->once())
            ->method('getShoppingListsForCustomerVisitor')
            ->willReturn($shoppingLists);

        $this->assertEquals($expectedResult, $this->manager->getShoppingListsWithCurrentFirst());
    }

    /**
     * @return array
     */
    public function getShoppingListsWithCurrentFirstDataProvider()
    {
        $existingShoppingList = $this->getEntity(ShoppingList::class, [
            'id' => 31,
            'label' => 'Existing Shopping List Label'
        ]);

        return [
            'shopping list exists'=> [
                'shoppingLists' => [$existingShoppingList],
                'expectedResults' => [$existingShoppingList]
            ],
            'shopping list doesnt exist'=> [
                'shoppingLists' => [],
                'expectedResults' => []
            ]
        ];
    }

    public function testGetShoppingListsWithCurrentFirstRegisteredNoCurrentShoppingList()
    {
        $this->guestShoppingListManager->expects($this->exactly(2))
            ->method('isGuestShoppingListAvailable')
            ->willReturn(false);

        $this->guestShoppingListManager->expects($this->never())
            ->method('getShoppingListForCustomerVisitor');

        $this->shoppingListRepository->expects($this->once())
            ->method('findAvailableForCustomerUser')
            ->willReturn(null);

        $this->assertEquals([], $this->manager->getShoppingListsWithCurrentFirst());
    }

    public function testGetShoppingListsWithCurrentFirstRegisteredCurrentShoppingListExists()
    {
        $this->guestShoppingListManager->expects($this->exactly(2))
            ->method('isGuestShoppingListAvailable')
            ->willReturn(false);

        $this->guestShoppingListManager->expects($this->never())
            ->method('getShoppingListForCustomerVisitor');

        $currentShoppingList = $this->getEntity(ShoppingList::class, ['id' => 35]);
        $shoppingList1 = $this->getEntity(ShoppingList::class, ['id' => 21]);
        $shoppingList2 = $this->getEntity(ShoppingList::class, ['id' => 22]);

        $this->shoppingListRepository->expects($this->once())
            ->method('findAvailableForCustomerUser')
            ->willReturn($currentShoppingList);

        $this->shoppingListRepository->expects($this->once())
            ->method('findByUser')
            ->willReturn([$shoppingList2, $currentShoppingList, $shoppingList1]);

        $this->assertEquals(
            [$currentShoppingList, $shoppingList2, $shoppingList1],
            $this->manager->getShoppingListsWithCurrentFirst()
        );
    }

    /**
     * @param CustomerUser $customerUser
     * @return \PHPUnit_Framework_MockObject_MockObject|TokenStorageInterface
     */
    protected function getTokenStorage($customerUser)
    {
        /** @var \PHPUnit_Framework_MockObject_MockObject|TokenInterface $securityToken */
        $this->securityToken = $this->createMock('Symfony\Component\Security\Core\Authentication\Token\TokenInterface');
        $this->securityToken->expects($this->any())
            ->method('getUser')
            ->willReturn($customerUser);

        /** @var \PHPUnit_Framework_MockObject_MockObject|TokenStorageInterface $tokenStorage */
        $tokenStorage = $this
            ->createMock('Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface');
        $tokenStorage->expects($this->any())
            ->method('getToken')
            ->willReturn($this->securityToken);

        return $tokenStorage;
    }

    /**
     * @return \PHPUnit_Framework_MockObject_MockObject|TranslatorInterface
     */
    protected function getTranslator()
    {
        return $this->createMock('Symfony\Component\Translation\TranslatorInterface');
    }

    /**
     * @return \PHPUnit_Framework_MockObject_MockObject|QuantityRoundingService
     */
    protected function getRoundingService()
    {
        /** @var \PHPUnit_Framework_MockObject_MockObject|QuantityRoundingService $roundingService */
        $roundingService = $this->getMockBuilder('Oro\Bundle\ProductBundle\Rounding\QuantityRoundingService')
            ->disableOriginalConstructor()
            ->getMock();

        $roundingService->expects($this->any())
            ->method('roundQuantity')
            ->will(
                $this->returnCallback(
                    function ($value) {
                        return round($value, 0, PHP_ROUND_HALF_UP);
                    }
                )
            );

        return $roundingService;
    }

    /**
     * @return \PHPUnit_Framework_MockObject_MockObject|ManagerRegistry
     */
    protected function getManagerRegistry()
    {
        $this->shoppingListRepository = $this
            ->getMockBuilder('Oro\Bundle\ShoppingListBundle\Entity\Repository\ShoppingListRepository')
            ->disableOriginalConstructor()
            ->getMock();

        /** @var \PHPUnit_Framework_MockObject_MockObject|LineItemRepository $lineItemRepository */
        $lineItemRepository = $this
            ->getMockBuilder('Oro\Bundle\ShoppingListBundle\Entity\Repository\LineItemRepository')
            ->disableOriginalConstructor()
            ->getMock();

        $lineItemRepository
            ->expects($this->any())
            ->method('findDuplicate')
            ->willReturnCallback(function (LineItem $lineItem) {
                /** @var ArrayCollection $shoppingListLineItems */
                $shoppingListLineItems = $lineItem->getShoppingList()->getLineItems();
                if ($lineItem->getShoppingList()->getId() === 1
                    && $shoppingListLineItems->count() > 0
                    && $shoppingListLineItems->current()->getUnit() === $lineItem->getUnit()
                ) {
                    return $lineItem->getShoppingList()->getLineItems()->current();
                }

                return null;
            });

        /** @var \PHPUnit_Framework_MockObject_MockObject|EntityManager $entityManager */
        $entityManager = $this->getMockBuilder('Doctrine\ORM\EntityManager')
            ->disableOriginalConstructor()
            ->getMock();

        $entityManager->expects($this->any())
            ->method('getRepository')
            ->will($this->returnValueMap([
                ['OroShoppingListBundle:ShoppingList', $this->shoppingListRepository],
                ['OroShoppingListBundle:LineItem', $lineItemRepository]
            ]));

        $entityManager->expects($this->any())
            ->method('persist')
            ->willReturnCallback(function ($obj) {
                if ($obj instanceof ShoppingList) {
                    $this->shoppingLists[] = $obj;
                }
                if ($obj instanceof LineItem) {
                    $this->lineItems[] = $obj;
                }
            });

        /** @var \PHPUnit_Framework_MockObject_MockObject|ManagerRegistry $managerRegistry */
        $managerRegistry = $this->createMock('Doctrine\Common\Persistence\ManagerRegistry');
        $managerRegistry->expects($this->any())
            ->method('getManagerForClass')
            ->willReturn($entityManager);

        return $managerRegistry;
    }

    /**
     * @return \PHPUnit_Framework_MockObject_MockObject|ShoppingListTotalManager
     */
    protected function getShoppingListTotalManager()
    {
        return $this->getMockBuilder(ShoppingListTotalManager::class)
                ->disableOriginalConstructor()
                ->getMock();
    }

    /**
     * @return \PHPUnit_Framework_MockObject_MockObject|UserCurrencyManager
     */
    protected function getUserCurrencyManager()
    {
        $userCurrencyManager = $this->getMockBuilder('Oro\Bundle\PricingBundle\Manager\UserCurrencyManager')
            ->disableOriginalConstructor()
            ->getMock();

        $userCurrencyManager->expects($this->any())
            ->method('getUserCurrency')
            ->willReturn(self::CURRENCY_EUR);

        return $userCurrencyManager;
    }

    /**
     * @return \PHPUnit_Framework_MockObject_MockObject|WebsiteManager
     */
    protected function getWebsiteManager()
    {
        $websiteManager = $this->createMock(WebsiteManager::class);
        $website = $this->createMock(Website::class);

        $websiteManager->expects($this->any())
            ->method('getCurrentWebsite')
            ->willReturn($website);

        return $websiteManager;
    }

    /**
     * @param int  $id
     * @param bool $isCurrent
     *
     * @return ShoppingList
     */
    protected function getShoppingList($id, $isCurrent)
    {
        return $this->getEntity(
            'Oro\Bundle\ShoppingListBundle\Entity\ShoppingList',
            ['id' => $id, 'current' => $isCurrent]
        );
    }

    /**
     * @return \PHPUnit_Framework_MockObject_MockObject|AclHelper
     */
    protected function getAclHelperMock()
    {
        return $this->getMockBuilder('Oro\Bundle\SecurityBundle\ORM\Walker\AclHelper')
            ->disableOriginalConstructor()
            ->getMock();
    }

    public function testEdit()
    {
        $shoppingList = new ShoppingList();

        $this->assertSame($shoppingList, $this->manager->edit($shoppingList));
    }

    public function testRemoveLineItems()
    {
        $shoppingList = new ShoppingList();
        $lineItem1 = new LineItem();
        $this->manager->addLineItem($lineItem1, $shoppingList);
        $lineItem2 = new LineItem();
        $this->manager->addLineItem($lineItem2, $shoppingList);
        $this->assertEquals(2, $shoppingList->getLineItems()->count());

        $this->totalManager->expects($this->once())
            ->method('recalculateTotals')
            ->with($shoppingList, false);

        $this->manager->removeLineItems($shoppingList);
        $this->assertEquals(0, $shoppingList->getLineItems()->count());
    }

    public function testUpdateLineItem()
    {
        $lineItem = (new LineItem())
            ->setUnit(
                (new ProductUnit())
                    ->setCode('test')
                    ->setDefaultPrecision(1)
            )
            ->setQuantity(10);

        /** @var ShoppingList $shoppingList */
        $shoppingList = $this->getEntity(ShoppingList::class, [
            'id' => 1,
            'lineItems' => [$lineItem]
        ]);

        $lineItemDuplicate = clone $lineItem;
        $lineItemDuplicate->setQuantity(5);
        $this->manager->updateLineItem($lineItemDuplicate, $shoppingList);

        $this->assertEquals(1, $shoppingList->getLineItems()->count());
        /** @var LineItem $resultingItem */
        $resultingItem = $shoppingList->getLineItems()->first();
        $this->assertEquals(5, $resultingItem->getQuantity());
    }

    public function testUpdateAndRemoveLineItem()
    {
        $lineItem = (new LineItem())
            ->setUnit(
                (new ProductUnit())
                    ->setCode('test')
                    ->setDefaultPrecision(1)
            )
            ->setQuantity(10);

        /** @var ShoppingList $shoppingList */
        $shoppingList = $this->getEntity(ShoppingList::class, [
            'id' => 1,
            'lineItems' => [$lineItem]
        ]);

        $lineItemDuplicate = clone $lineItem;
        $lineItemDuplicate->setQuantity(0);
        $this->manager->updateLineItem($lineItemDuplicate, $shoppingList);

        $this->assertEmpty($shoppingList->getLineItems());
    }
}
