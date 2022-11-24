<?php

namespace Oro\Bundle\ShoppingListBundle\Tests\Unit\Manager;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\ConfigBundle\Config\ConfigManager;
use Oro\Bundle\CustomerBundle\Entity\CustomerUser;
use Oro\Bundle\CustomerBundle\Tests\Unit\Stub\CustomerUserStub;
use Oro\Bundle\SecurityBundle\Authentication\TokenAccessorInterface;
use Oro\Bundle\SecurityBundle\ORM\Walker\AclHelper;
use Oro\Bundle\ShoppingListBundle\Entity\LineItem;
use Oro\Bundle\ShoppingListBundle\Entity\Repository\ShoppingListRepository;
use Oro\Bundle\ShoppingListBundle\Entity\ShoppingList;
use Oro\Bundle\ShoppingListBundle\Manager\CurrentShoppingListManager;
use Oro\Bundle\ShoppingListBundle\Manager\CurrentShoppingListStorage;
use Oro\Bundle\ShoppingListBundle\Manager\GuestShoppingListManager;
use Oro\Bundle\ShoppingListBundle\Manager\ShoppingListManager;
use Oro\Component\Testing\ReflectionUtil;
use Psr\Cache\CacheItemInterface;
use Psr\Cache\CacheItemPoolInterface;

/**
 * @SuppressWarnings(PHPMD.TooManyMethods)
 * @SuppressWarnings(PHPMD.TooManyPublicMethods)
 * @SuppressWarnings(PHPMD.ExcessiveClassComplexity)
 * @SuppressWarnings(PHPMD.ExcessiveClassLength)
 */
class CurrentShoppingListManagerTest extends \PHPUnit\Framework\TestCase
{
    /** @var ShoppingListManager|\PHPUnit\Framework\MockObject\MockObject */
    private $shoppingListManager;

    /** @var GuestShoppingListManager|\PHPUnit\Framework\MockObject\MockObject */
    private $guestShoppingListManager;

    /** @var CacheItemPoolInterface|\PHPUnit\Framework\MockObject\MockObject */
    private $cache;

    /** @var CacheItemInterface|\PHPUnit\Framework\MockObject\MockObject */
    private $cacheItem;

    /** @var AclHelper|\PHPUnit\Framework\MockObject\MockObject */
    private $aclHelper;

    /** @var TokenAccessorInterface|\PHPUnit\Framework\MockObject\MockObject */
    private $tokenAccessor;

    /** @var ShoppingListRepository|\PHPUnit\Framework\MockObject\MockObject */
    private $shoppingListRepository;

    /** @var \PHPUnit\Framework\MockObject\MockObject|ConfigManager */
    private $configManager;

    /** @var CurrentShoppingListManager */
    private $currentShoppingListManager;

    protected function setUp(): void
    {
        $this->shoppingListManager = $this->createMock(ShoppingListManager::class);
        $this->guestShoppingListManager = $this->createMock(GuestShoppingListManager::class);
        $this->cache = $this->createMock(CacheItemPoolInterface::class);
        $this->cacheItem = $this->createMock(CacheItemInterface::class);
        $this->aclHelper = $this->createMock(AclHelper::class);
        $this->tokenAccessor = $this->createMock(TokenAccessorInterface::class);
        $this->configManager = $this->createMock(ConfigManager::class);
        $this->shoppingListRepository = $this->createMock(ShoppingListRepository::class);

        $shoppingListEntityManager = $this->createMock(EntityManagerInterface::class);
        $shoppingListEntityManager->expects($this->any())
            ->method('getRepository')
            ->with(ShoppingList::class)
            ->willReturn($this->shoppingListRepository);

        $doctrine = $this->createMock(ManagerRegistry::class);
        $doctrine->expects($this->any())
            ->method('getManagerForClass')
            ->with(ShoppingList::class)
            ->willReturn($shoppingListEntityManager);

        $this->currentShoppingListManager = new CurrentShoppingListManager(
            $this->shoppingListManager,
            $this->guestShoppingListManager,
            new CurrentShoppingListStorage($this->cache),
            $doctrine,
            $this->aclHelper,
            $this->tokenAccessor,
            $this->configManager
        );
    }

    private function getShoppingList(int $id): ShoppingList
    {
        $shoppingList = new ShoppingList();
        ReflectionUtil::setId($shoppingList, $id);

        return $shoppingList;
    }

    private function getCustomerUser(int $id): CustomerUser
    {
        $customerUser = new CustomerUser();
        ReflectionUtil::setId($customerUser, $id);

        return $customerUser;
    }

    private function expectGetCurrentShoppingList(?ShoppingList $shoppingList)
    {
        $customerUserId = 234;
        $customerUser = $this->getCustomerUser($customerUserId);
        $this->tokenAccessor->expects($this->atLeastOnce())
            ->method('getUser')
            ->willReturn($customerUser);

        if (null === $shoppingList) {
            $this->expectCacheFetchAndNoSave($customerUserId, null);

            $this->shoppingListRepository->expects($this->never())
                ->method('findByUserAndId');
            $this->shoppingListRepository->expects($this->once())
                ->method('findAvailableForCustomerUser')
                ->with($this->aclHelper, false)
                ->willReturn(null);
        } else {
            $this->expectCacheFetchAndNoSave($customerUserId, $shoppingList->getId());

            $this->shoppingListRepository->expects($this->once())
                ->method('findByUserAndId')
                ->with($this->aclHelper, $shoppingList->getId())
                ->willReturn($shoppingList);
            $this->shoppingListRepository->expects($this->never())
                ->method('findAvailableForCustomerUser');
        }

        $this->shoppingListManager->expects($this->never())
            ->method('create');
    }

    private function expectCacheFetchAndSave(
        int $customerUserId,
        ?int $fetchShoppingListId,
        int $saveShoppingListId
    ): void {
        $this->cache->expects($this->any())
            ->method('getItem')
            ->with($customerUserId)
            ->willReturn($this->cacheItem);
        $this->cacheItem->expects(self::once())
            ->method('isHit')
            ->willReturn(null !== $fetchShoppingListId);
        if ($fetchShoppingListId) {
            $this->cacheItem->expects(self::once())
                ->method('get')
                ->willReturn($fetchShoppingListId);
        }
        $this->cacheItem->expects(self::once())
            ->method('set')
            ->with($saveShoppingListId)
            ->willReturn($this->cacheItem);
        $this->cache->expects($this->once())
            ->method('save')
            ->with($this->cacheItem);
    }

    private function expectCacheFetchAndNoSave(int $customerUserId, ?int $fetchShoppingListId): void
    {
        $this->cache->expects($this->once())
            ->method('getItem')
            ->with($customerUserId)
            ->willReturn($this->cacheItem);
        $this->cacheItem->expects(self::once())
            ->method('isHit')
            ->willReturn(null !== $fetchShoppingListId);
        if ($fetchShoppingListId) {
            $this->cacheItem->expects(self::once())
                ->method('get')
                ->willReturn($fetchShoppingListId);
        }
        $this->cache->expects($this->never())
            ->method('save');
    }

    private function expectCacheNoFetchAndNoSave()
    {
        $this->cache->expects($this->never())
            ->method('getItem');
        $this->cache->expects($this->never())
            ->method('save');
    }

    private function expectGetGuestShoppingList(?ShoppingList $shoppingList)
    {
        $this->guestShoppingListManager->expects($this->once())
            ->method('isGuestShoppingListAvailable')
            ->willReturn(true);
        $this->guestShoppingListManager->expects($this->once())
            ->method('getShoppingListForCustomerVisitor')
            ->willReturn($shoppingList);
        $this->guestShoppingListManager->expects($this->never())
            ->method('createAndGetShoppingListForCustomerVisitor');
    }

    private function expectCreateGuestShoppingList(ShoppingList $shoppingList)
    {
        $this->guestShoppingListManager->expects($this->once())
            ->method('isGuestShoppingListAvailable')
            ->willReturn(true);
        $this->guestShoppingListManager->expects($this->never())
            ->method('getShoppingListForCustomerVisitor');
        $this->guestShoppingListManager->expects($this->once())
            ->method('createAndGetShoppingListForCustomerVisitor')
            ->willReturn($shoppingList);
    }

    private function expectNoGuestShoppingList()
    {
        $this->guestShoppingListManager->expects($this->once())
            ->method('isGuestShoppingListAvailable')
            ->willReturn(false);
        $this->guestShoppingListManager->expects($this->never())
            ->method('getShoppingListForCustomerVisitor');
        $this->guestShoppingListManager->expects($this->never())
            ->method('createAndGetShoppingListForCustomerVisitor');
    }

    private function expectNoGuestShoppingLists()
    {
        $this->guestShoppingListManager->expects($this->once())
            ->method('isGuestShoppingListAvailable')
            ->willReturn(false);
        $this->guestShoppingListManager->expects($this->never())
            ->method('getShoppingListsForCustomerVisitor');
    }

    /**
     * @param int $customerUserId
     */
    private function setCustomerUserForTokenAccessor(int $customerUserId = 234): void
    {
        $customerUser = $this->getCustomerUser($customerUserId);
        $this->tokenAccessor->expects($this->atLeastOnce())
            ->method('getUser')
            ->willReturn($customerUser);
    }

    /**
     * @param ShoppingList $shoppingList
     * @param int $customerUserId
     */
    private function setCustomerUserForShoppingList(ShoppingList $shoppingList, int $customerUserId = 2345): void
    {
        $customerUser = $this->getCustomerUser($customerUserId);
        $shoppingList->setCustomerUser($customerUser);
    }

    public function labelDataProvider(): array
    {
        return [
            'without label' => [],
            'with label'    => ['label' => 'test label']
        ];
    }

    /**
     * @dataProvider labelDataProvider
     */
    public function testCreateCurrent($label = '')
    {
        $shoppingListId = 123;
        $shoppingList = $this->getShoppingList($shoppingListId);

        $customerUserId = 234;
        $customerUser = $this->getCustomerUser($customerUserId);
        $this->tokenAccessor->expects($this->atLeastOnce())
            ->method('getUser')
            ->willReturn($customerUser);

        $this->shoppingListManager->expects($this->once())
            ->method('create')
            ->with(true, $label)
            ->willReturn($shoppingList);
        $this->cache->expects($this->once())
            ->method('getItem')
            ->with($customerUserId)
            ->willReturn($this->cacheItem);
        $this->cacheItem->expects($this->once())
            ->method('set')
            ->with($shoppingListId)
            ->willReturn($this->cacheItem);
        $this->cache->expects($this->once())
            ->method('save')
            ->with($this->cacheItem);

        $this->assertSame($shoppingList, $this->currentShoppingListManager->createCurrent($label));
        $this->assertTrue($shoppingList->isCurrent());
    }

    public function testSetCurrent()
    {
        $shoppingListId = 123;
        $shoppingList = $this->getShoppingList($shoppingListId);

        $customerUserId = 234;
        $customerUser = $this->getCustomerUser($customerUserId);

        $this->cache->expects($this->once())
            ->method('getItem')
            ->with($customerUserId)
            ->willReturn($this->cacheItem);
        $this->cacheItem->expects($this->once())
            ->method('set')
            ->with($shoppingListId)
            ->willReturn($this->cacheItem);
        $this->cache->expects($this->once())
            ->method('save')
            ->with($this->cacheItem);

        $this->currentShoppingListManager->setCurrent($customerUser, $shoppingList);
        $this->assertTrue($shoppingList->isCurrent());
    }

    public function testSetCurrentWhenNewCustomerUserIsPassed()
    {
        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage('The customer user ID must not be empty.');

        $this->currentShoppingListManager->setCurrent(new CustomerUser(), $this->getShoppingList(123));
    }

    public function testSetCurrentWhenNewShoppingListIsPassed()
    {
        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage('The shopping list ID must not be empty.');

        $this->currentShoppingListManager->setCurrent($this->getCustomerUser(234), new ShoppingList());
    }

    public function testGetCurrentForGuestShoppingListWithCreate()
    {
        $shoppingList = new ShoppingList();

        $this->expectCreateGuestShoppingList($shoppingList);
        $this->expectCacheNoFetchAndNoSave();

        $this->assertSame($shoppingList, $this->currentShoppingListManager->getCurrent(true));
    }

    public function testGetCurrentForGuestShoppingListWithoutCreate()
    {
        $shoppingList = new ShoppingList();

        $this->expectGetGuestShoppingList($shoppingList);
        $this->expectCacheNoFetchAndNoSave();

        $this->assertSame($shoppingList, $this->currentShoppingListManager->getCurrent());
    }

    public function testGetCurrentForGuestShoppingListNoExistingShoppingList()
    {
        $this->expectGetGuestShoppingList(null);
        $this->expectCacheNoFetchAndNoSave();

        $this->assertNull($this->currentShoppingListManager->getCurrent());
    }

    public function testGetCurrentNoCustomerUserInSecurityContext()
    {
        $this->tokenAccessor->expects($this->atLeastOnce())
            ->method('getUser')
            ->willReturn(null);

        $this->expectNoGuestShoppingList();
        $this->expectCacheNoFetchAndNoSave();

        $this->assertNull($this->currentShoppingListManager->getCurrent());
    }

    public function testGetCurrentHasCacheAndShoppingListExistsInDatabase()
    {
        $shoppingListId = 123;
        $shoppingList = $this->getShoppingList($shoppingListId);

        $customerUserId = 234;
        $customerUser = $this->getCustomerUser($customerUserId);
        $this->tokenAccessor->expects($this->atLeastOnce())
            ->method('getUser')
            ->willReturn($customerUser);

        $this->expectNoGuestShoppingList();
        $this->expectCacheFetchAndNoSave($customerUserId, $shoppingListId);

        $this->shoppingListRepository->expects($this->once())
            ->method('findByUserAndId')
            ->with($this->aclHelper, $shoppingListId)
            ->willReturn($shoppingList);
        $this->shoppingListRepository->expects($this->never())
            ->method('findAvailableForCustomerUser');
        $this->shoppingListManager->expects($this->never())
            ->method('create');

        $this->assertSame($shoppingList, $this->currentShoppingListManager->getCurrent(true));
        $this->assertTrue($shoppingList->isCurrent());
    }

    public function testGetCurrentHasCacheAndNoShoppingListInDatabaseButFoundAnotherShoppingListForCustomerUser()
    {
        $shoppingListId = 123;
        $anotherShoppingListId = 1234;
        $anotherShoppingList = $this->getShoppingList($anotherShoppingListId);

        $customerUserId = 234;
        $customerUser = $this->getCustomerUser($customerUserId);
        $this->tokenAccessor->expects($this->atLeastOnce())
            ->method('getUser')
            ->willReturn($customerUser);

        $this->expectNoGuestShoppingList();
        $this->expectCacheFetchAndSave($customerUserId, $shoppingListId, $anotherShoppingListId);

        $this->shoppingListRepository->expects($this->once())
            ->method('findByUserAndId')
            ->with($this->aclHelper, $shoppingListId)
            ->willReturn(null);
        $this->shoppingListRepository->expects($this->once())
            ->method('findAvailableForCustomerUser')
            ->with($this->aclHelper, false)
            ->willReturn($anotherShoppingList);
        $this->shoppingListManager->expects($this->never())
            ->method('create');

        $this->assertSame($anotherShoppingList, $this->currentShoppingListManager->getCurrent(true));
        $this->assertTrue($anotherShoppingList->isCurrent());
    }

    /**
     * @dataProvider labelDataProvider
     */
    public function testGetCurrentHasCacheButNoShoppingListInDatabaseWithCreate($label = '')
    {
        $shoppingListId = 123;
        $newShoppingListId = 1234;
        $newShoppingList = $this->getShoppingList($newShoppingListId);

        $customerUserId = 234;
        $customerUser = $this->getCustomerUser($customerUserId);
        $this->tokenAccessor->expects($this->atLeastOnce())
            ->method('getUser')
            ->willReturn($customerUser);

        $this->expectNoGuestShoppingList();
        $this->expectCacheFetchAndSave($customerUserId, $shoppingListId, $newShoppingListId);

        $this->shoppingListRepository->expects($this->once())
            ->method('findByUserAndId')
            ->with($this->aclHelper, $shoppingListId)
            ->willReturn(null);
        $this->shoppingListRepository->expects($this->once())
            ->method('findAvailableForCustomerUser')
            ->with($this->aclHelper, false)
            ->willReturn(null);
        $this->shoppingListManager->expects($this->once())
            ->method('create')
            ->with(true, $label)
            ->willReturn($newShoppingList);

        $this->assertSame($newShoppingList, $this->currentShoppingListManager->getCurrent(true, $label));
        $this->assertTrue($newShoppingList->isCurrent());
    }

    public function testGetCurrentHasCacheButNoShoppingListInDatabaseWithoutCreate()
    {
        $shoppingListId = 123;

        $customerUserId = 234;
        $customerUser = $this->getCustomerUser($customerUserId);
        $this->tokenAccessor->expects($this->atLeastOnce())
            ->method('getUser')
            ->willReturn($customerUser);

        $this->expectNoGuestShoppingList();
        $this->expectCacheFetchAndNoSave($customerUserId, $shoppingListId);

        $this->shoppingListRepository->expects($this->once())
            ->method('findByUserAndId')
            ->with($this->aclHelper, $shoppingListId)
            ->willReturn(null);
        $this->shoppingListRepository->expects($this->once())
            ->method('findAvailableForCustomerUser')
            ->with($this->aclHelper, false)
            ->willReturn(null);

        $this->assertNull($this->currentShoppingListManager->getCurrent());
    }

    public function testGetForCurrentUserForGuestShoppingList()
    {
        $shoppingListId = 123;
        $shoppingList = $this->getShoppingList($shoppingListId);

        $this->expectCreateGuestShoppingList($shoppingList);

        $this->shoppingListRepository->expects($this->never())
            ->method('findByUserAndId');

        $this->assertSame($shoppingList, $this->currentShoppingListManager->getForCurrentUser(1234));
    }

    public function testGetForCurrentUserWithShoppingListIdAndShoppingListExists()
    {
        $shoppingListId = 123;
        $shoppingList = $this->getShoppingList($shoppingListId);

        $this->expectNoGuestShoppingList();

        $this->shoppingListRepository->expects($this->once())
            ->method('findByUserAndId')
            ->with($this->aclHelper, $shoppingListId)
            ->willReturn($shoppingList);

        $this->assertSame($shoppingList, $this->currentShoppingListManager->getForCurrentUser($shoppingListId));
    }

    public function testGetForCurrentUserWithShoppingListIdAndShoppingListDoesNotExistWithCreate()
    {
        $shoppingListId = 123;
        $newShoppingListId = 1234;
        $newShoppingList = $this->getShoppingList($newShoppingListId);

        $customerUserId = 234;
        $this->setCustomerUserForTokenAccessor($customerUserId);

        $this->expectNoGuestShoppingList();
        $this->expectCacheFetchAndSave($customerUserId, null, $newShoppingListId);

        $this->shoppingListRepository->expects($this->once())
            ->method('findByUserAndId')
            ->with($this->aclHelper, $shoppingListId)
            ->willReturn(null);
        $this->shoppingListRepository->expects($this->once())
            ->method('findAvailableForCustomerUser')
            ->with($this->aclHelper, false)
            ->willReturn(null);
        $this->shoppingListManager->expects($this->once())
            ->method('create')
            ->with(true, '')
            ->willReturn($newShoppingList);

        $this->assertEquals(
            $newShoppingList,
            $this->currentShoppingListManager->getForCurrentUser($shoppingListId, true)
        );
    }

    public function testGetForCurrentUserWithShoppingListIdAndShoppingListDoesNotExistWithoutCreate()
    {
        $shoppingListId = 123;

        $customerUserId = 234;
        $this->setCustomerUserForTokenAccessor($customerUserId);

        $this->expectNoGuestShoppingList();
        $this->cache->expects($this->once())
            ->method('getItem')
            ->with($customerUserId)
            ->willReturn($this->cacheItem);
        $this->cacheItem->expects(self::once())
            ->method('isHit')
            ->willReturn(false);
        $this->shoppingListRepository->expects($this->once())
            ->method('findByUserAndId')
            ->with($this->aclHelper, $shoppingListId)
            ->willReturn(null);
        $this->shoppingListRepository->expects($this->once())
            ->method('findAvailableForCustomerUser')
            ->with($this->aclHelper, false)
            ->willReturn(null);
        $this->shoppingListManager->expects($this->never())
            ->method('create');

        $this->assertNull($this->currentShoppingListManager->getForCurrentUser($shoppingListId));
    }

    public function testGetForCurrentUserWithoutShoppingListIdWithCreate()
    {
        $newShoppingListId = 1234;
        $newShoppingList = $this->getShoppingList($newShoppingListId);

        $customerUserId = 234;
        $this->setCustomerUserForTokenAccessor($customerUserId);

        $this->expectNoGuestShoppingList();
        $this->expectCacheFetchAndSave($customerUserId, null, $newShoppingListId);

        $this->shoppingListRepository->expects($this->never())
            ->method('findByUserAndId');
        $this->shoppingListRepository->expects($this->once())
            ->method('findAvailableForCustomerUser')
            ->with($this->aclHelper, false)
            ->willReturn(null);
        $this->shoppingListManager->expects($this->once())
            ->method('create')
            ->with(true, '')
            ->willReturn($newShoppingList);

        $this->assertEquals($newShoppingList, $this->currentShoppingListManager->getForCurrentUser(null, true));
    }

    public function testGetForCurrentUserWithoutShoppingListIdWithoutCreate()
    {
        $customerUserId = 234;
        $this->setCustomerUserForTokenAccessor($customerUserId);

        $this->expectNoGuestShoppingList();
        $this->cache->expects($this->once())
            ->method('getItem')
            ->with($customerUserId)
            ->willReturn($this->cacheItem);
        $this->cacheItem->expects(self::once())
            ->method('isHit')
            ->willReturn(false);
        $this->shoppingListRepository->expects($this->never())
            ->method('findByUserAndId');
        $this->shoppingListRepository->expects($this->once())
            ->method('findAvailableForCustomerUser')
            ->with($this->aclHelper, false)
            ->willReturn(null);
        $this->shoppingListManager->expects($this->never())
            ->method('create');

        $this->assertNull($this->currentShoppingListManager->getForCurrentUser());
    }

    public function testGetForCurrentUserShareShoppingListExistedButShowAllShoppingListOptionOffWithCreate()
    {
        $shoppingListId = 123;
        $shoppingList = $this->getShoppingList($shoppingListId);
        $newShoppingListId = 1234;
        $newShoppingList = $this->getShoppingList($newShoppingListId);

        $customerUserId = 234;
        $this->setCustomerUserForTokenAccessor($customerUserId);
        $this->setCustomerUserForShoppingList($shoppingList);

        $this->expectNoGuestShoppingList();
        $this->cache->expects(self::exactly(3))
            ->method('getItem')
            ->with($customerUserId)
            ->willReturn($this->cacheItem);
        $this->cacheItem->expects(self::once())
            ->method('isHit')
            ->willReturn(false);
        $this->cacheItem->expects(self::exactly(2))
            ->method('set')
            ->willReturn($this->cacheItem);
        $this->shoppingListRepository->expects($this->never())
            ->method('findByUserAndId');
        $this->shoppingListRepository->expects($this->once())
            ->method('findAvailableForCustomerUser')
            ->with($this->aclHelper, false)
            ->willReturn($shoppingList);
        $this->configManager->expects($this->once())
            ->method('get')
            ->with('oro_shopping_list.show_all_in_shopping_list_widget')
            ->willReturn(false);
        $this->shoppingListManager->expects($this->once())
            ->method('create')
            ->with(true, '')
            ->willReturn($newShoppingList);

        $this->assertSame($newShoppingList, $this->currentShoppingListManager->getForCurrentUser(null, true));
    }

    public function testGetForCurrentUserShareShoppingListExistedButShowAllShoppingListOptionOffWithoutCreate()
    {
        $shoppingListId = 123;
        $shoppingList = $this->getShoppingList($shoppingListId);

        $customerUserId = 234;
        $this->setCustomerUserForTokenAccessor($customerUserId);
        $this->setCustomerUserForShoppingList($shoppingList);

        $this->expectNoGuestShoppingList();
        $this->cache->expects(self::exactly(2))
            ->method('getItem')
            ->with($customerUserId)
            ->willReturn($this->cacheItem);
        $this->cacheItem->expects(self::once())
            ->method('isHit')
            ->willReturn(false);
        $this->cacheItem->expects(self::once())
            ->method('set')
            ->willReturn($this->cacheItem);
        $this->shoppingListRepository->expects($this->never())
            ->method('findByUserAndId');
        $this->shoppingListRepository->expects($this->once())
            ->method('findAvailableForCustomerUser')
            ->with($this->aclHelper, false)
            ->willReturn($shoppingList);
        $this->configManager->expects($this->once())
            ->method('get')
            ->with('oro_shopping_list.show_all_in_shopping_list_widget')
            ->willReturn(false);
        $this->shoppingListManager->expects($this->never())
            ->method('create');

        $this->assertNull($this->currentShoppingListManager->getForCurrentUser());
    }

    public function testGetForCurrentUserShareShoppingListExistedAndShowAllShoppingListOptionOn()
    {
        $shoppingListId = 123;
        $shoppingList = $this->getShoppingList($shoppingListId);

        $customerUserId = 234;
        $this->setCustomerUserForTokenAccessor($customerUserId);
        $this->setCustomerUserForShoppingList($shoppingList);

        $this->expectNoGuestShoppingList();
        $this->cache->expects(self::exactly(2))
            ->method('getItem')
            ->with($customerUserId)
            ->willReturn($this->cacheItem);
        $this->cacheItem->expects(self::once())
            ->method('isHit')
            ->willReturn(false);
        $this->cacheItem->expects(self::once())
            ->method('set')
            ->willReturn($this->cacheItem);
        $this->shoppingListRepository->expects($this->never())
            ->method('findByUserAndId');
        $this->shoppingListRepository->expects($this->once())
            ->method('findAvailableForCustomerUser')
            ->with($this->aclHelper, false)
            ->willReturn($shoppingList);
        $this->configManager->expects($this->once())
            ->method('get')
            ->with('oro_shopping_list.show_all_in_shopping_list_widget')
            ->willReturn(true);

        $this->assertSame($shoppingList, $this->currentShoppingListManager->getForCurrentUser());
    }

    public function testGetShoppingListsWithCurrentFirstForGuestShoppingLists()
    {
        $shoppingLists = [$this->getShoppingList(123)];

        $this->tokenAccessor->expects($this->never())
            ->method('getUser');

        $this->expectCacheNoFetchAndNoSave();

        $this->guestShoppingListManager->expects($this->once())
            ->method('isGuestShoppingListAvailable')
            ->willReturn(true);
        $this->guestShoppingListManager->expects($this->once())
            ->method('getShoppingListsForCustomerVisitor')
            ->willReturn($shoppingLists);

        $this->assertEquals(
            $shoppingLists,
            $this->currentShoppingListManager->getShoppingListsWithCurrentFirst()
        );
    }

    public function testGetShoppingListsWithCurrentFirstWhenNoCurrentShoppingList()
    {
        $this->expectNoGuestShoppingLists();
        $this->expectGetCurrentShoppingList(null);

        $this->assertSame([], $this->currentShoppingListManager->getShoppingListsWithCurrentFirst());
    }

    public function testGetShoppingListsWithCurrentFirstWhenCurrentShoppingListExists()
    {
        $sortCriteria = ['label' => 'ASC'];

        $currentShoppingList = $this->getShoppingList(123);
        $shoppingLists = [$this->getShoppingList(11)];
        $expectedShoppingLists = [$currentShoppingList, $shoppingLists[0]];

        $this->expectNoGuestShoppingLists();
        $this->expectGetCurrentShoppingList($currentShoppingList);

        $this->shoppingListRepository->expects($this->once())
            ->method('findByUser')
            ->with($this->aclHelper, $sortCriteria, $currentShoppingList)
            ->willReturn($shoppingLists);

        $this->assertEquals(
            $expectedShoppingLists,
            $this->currentShoppingListManager->getShoppingListsWithCurrentFirst($sortCriteria)
        );
    }

    public function testGetShoppingListsForCustomerUserWithCurrentFirstWhenNoCurrentShoppingList(): void
    {
        $customerUserId = 234;
        $customerUser = $this->getCustomerUser($customerUserId);
        $this->tokenAccessor->expects($this->atLeastOnce())
            ->method('getUser')
            ->willReturn($customerUser);

        $this->expectCacheFetchAndNoSave($customerUserId, null);

        $this->shoppingListRepository->expects($this->never())
            ->method('findByUserAndId');
        $this->shoppingListRepository->expects($this->once())
            ->method('findAvailableForCustomerUser')
            ->with($this->aclHelper, false)
            ->willReturn(null);

        $this->shoppingListManager->expects($this->never())
            ->method('create');

        $this->assertSame(
            [],
            $this->currentShoppingListManager->getShoppingListsForCustomerUserWithCurrentFirst($customerUserId)
        );
    }

    public function testGetShoppingListsForCustomerUserWithCurrentFirstWhenCurrentShoppingListExists(): void
    {
        $sortCriteria = ['label' => 'ASC'];

        $customerUserId = 234;
        $currentShoppingList = $this->getShoppingList(123);
        $customerUser1 = $this->getCustomerUser($customerUserId);
        $currentShoppingList->setCustomerUser($customerUser1);

        $shoppingLists = [$this->getShoppingList(11)];
        $expectedShoppingLists = [$currentShoppingList, $shoppingLists[0]];

        $customerUser = $this->getCustomerUser($customerUserId);
        $this->tokenAccessor->expects($this->atLeastOnce())
            ->method('getUser')
            ->willReturn($customerUser);

        $this->expectCacheFetchAndNoSave($customerUserId, $currentShoppingList->getId());

        $this->shoppingListRepository->expects($this->once())
            ->method('findByUserAndId')
            ->with($this->aclHelper, $currentShoppingList->getId())
            ->willReturn($currentShoppingList);
        $this->shoppingListRepository->expects($this->never())
            ->method('findAvailableForCustomerUser');

        $this->shoppingListManager->expects($this->never())
            ->method('create');

        $this->shoppingListRepository->expects($this->once())
            ->method('findByCustomerUserId')
            ->with($customerUserId, $this->aclHelper, $sortCriteria, $currentShoppingList)
            ->willReturn($shoppingLists);

        $this->assertEquals(
            $expectedShoppingLists,
            $this->currentShoppingListManager->getShoppingListsForCustomerUserWithCurrentFirst(
                $customerUserId,
                $sortCriteria
            )
        );
    }

    public function testGetShoppingListsForCustomerUserWithCurrentFirstWhenCurrentShoppingListNotOwn(): void
    {
        $sortCriteria = ['label' => 'ASC'];

        $customerUserId = 234;
        $currentShoppingList = $this->getShoppingList(123);
        $customerUser1 = $this->getCustomerUser(42);
        $currentShoppingList->setCustomerUser($customerUser1);

        $shoppingLists = [$this->getShoppingList(11)];
        $shoppingLists[0]->setCustomerUser($this->getCustomerUser($customerUserId));
        $expectedShoppingLists = [$shoppingLists[0]];

        $customerUser = $this->getCustomerUser($customerUserId);
        $this->tokenAccessor->expects($this->atLeastOnce())
            ->method('getUser')
            ->willReturn($customerUser);

        $this->expectCacheFetchAndSave($customerUserId, $currentShoppingList->getId(), 11);

        $this->shoppingListRepository->expects($this->once())
            ->method('findByUserAndId')
            ->with($this->aclHelper, $currentShoppingList->getId())
            ->willReturn($currentShoppingList);
        $this->shoppingListRepository->expects($this->never())
            ->method('findAvailableForCustomerUser');

        $this->shoppingListManager->expects($this->never())
            ->method('create');

        $this->shoppingListRepository->expects($this->once())
            ->method('findByCustomerUserId')
            ->with($customerUserId, $this->aclHelper, $sortCriteria, null)
            ->willReturn($shoppingLists);

        $this->assertEquals(
            $expectedShoppingLists,
            $this->currentShoppingListManager->getShoppingListsForCustomerUserWithCurrentFirst(
                $customerUserId,
                $sortCriteria
            )
        );
    }

    public function testGetShoppingListsForGuestShoppingLists()
    {
        $shoppingLists = [$this->getShoppingList(123)];

        $this->tokenAccessor->expects($this->never())
            ->method('getUser');

        $this->expectCacheNoFetchAndNoSave();

        $this->guestShoppingListManager->expects($this->once())
            ->method('isGuestShoppingListAvailable')
            ->willReturn(true);
        $this->guestShoppingListManager->expects($this->once())
            ->method('getShoppingListsForCustomerVisitor')
            ->willReturn($shoppingLists);

        $this->assertEquals(
            $shoppingLists,
            $this->currentShoppingListManager->getShoppingLists()
        );
    }

    public function testGetShoppingLists()
    {
        $sortCriteria = ['label' => 'ASC'];
        $shoppingLists = [$this->getShoppingList(123)];

        $this->expectNoGuestShoppingLists();

        $this->shoppingListRepository->expects($this->once())
            ->method('findByUser')
            ->with($this->aclHelper, $sortCriteria, null)
            ->willReturn($shoppingLists);

        $this->assertEquals(
            $shoppingLists,
            $this->currentShoppingListManager->getShoppingLists($sortCriteria)
        );
    }

    public function testGetShoppingListsByCustomerUserWhenGuestShoppingLists(): void
    {
        $shoppingLists = [$this->getShoppingList(123)];

        $customerUser = new CustomerUserStub();
        $customerUser->setId(42);

        $this->tokenAccessor->expects($this->never())
            ->method('getUser');

        $this->expectCacheNoFetchAndNoSave();

        $this->guestShoppingListManager->expects($this->once())
            ->method('isGuestShoppingListAvailable')
            ->willReturn(true);
        $this->guestShoppingListManager->expects($this->once())
            ->method('getShoppingListsForCustomerVisitor')
            ->willReturn($shoppingLists);

        $this->assertEquals(
            $shoppingLists,
            $this->currentShoppingListManager->getShoppingListsByCustomerUser($customerUser)
        );
    }

    public function testGetShoppingListsByCustomerUser(): void
    {
        $sortCriteria = ['label' => 'ASC'];
        $shoppingLists = [$this->getShoppingList(123)];

        $customerUser = new CustomerUserStub();
        $customerUser->setId(42);

        $this->expectNoGuestShoppingLists();

        $this->shoppingListRepository->expects($this->once())
            ->method('findByCustomerUserId')
            ->with($customerUser->getId(), $this->aclHelper, $sortCriteria, null)
            ->willReturn($shoppingLists);

        $this->assertEquals(
            $shoppingLists,
            $this->currentShoppingListManager->getShoppingListsByCustomerUser($customerUser, $sortCriteria)
        );
    }

    public function testIsCurrentShoppingListEmptyForGuestShoppingListWhenItDoesNotExist()
    {
        $this->expectGetGuestShoppingList(null);

        $this->assertTrue($this->currentShoppingListManager->isCurrentShoppingListEmpty());
    }

    public function testIsCurrentShoppingListEmptyForGuestShoppingListWithoutLineItems()
    {
        $shoppingList = $this->getShoppingList(123);

        $this->expectGetGuestShoppingList($shoppingList);

        $this->assertTrue($this->currentShoppingListManager->isCurrentShoppingListEmpty());
    }

    public function testIsCurrentShoppingListEmptyForGuestShoppingListWithLineItems()
    {
        $shoppingList = $this->getShoppingList(123);
        $shoppingList->addLineItem(new LineItem());

        $this->expectGetGuestShoppingList($shoppingList);

        $this->assertFalse($this->currentShoppingListManager->isCurrentShoppingListEmpty());
    }

    public function testIsCurrentShoppingListEmptyWhenShoppingListDoesNotExist()
    {
        $this->expectNoGuestShoppingList();
        $this->expectGetCurrentShoppingList(null);

        $this->assertTrue($this->currentShoppingListManager->isCurrentShoppingListEmpty());
    }

    public function testIsCurrentShoppingListEmptyForShoppingListWithoutLineItems()
    {
        $shoppingList = $this->getShoppingList(123);

        $this->expectNoGuestShoppingList();
        $this->expectGetCurrentShoppingList($shoppingList);

        $this->assertTrue($this->currentShoppingListManager->isCurrentShoppingListEmpty());
    }

    public function testIsCurrentShoppingListEmptyForShoppingListWithLineItems()
    {
        $shoppingList = $this->getShoppingList(123);
        $shoppingList->addLineItem(new LineItem());

        $this->expectNoGuestShoppingList();
        $this->expectGetCurrentShoppingList($shoppingList);

        $this->assertFalse($this->currentShoppingListManager->isCurrentShoppingListEmpty());
    }
}
