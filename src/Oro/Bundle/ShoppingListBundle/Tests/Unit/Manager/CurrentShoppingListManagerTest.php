<?php

namespace Oro\Bundle\ShoppingListBundle\Tests\Unit\Manager;

use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\ConfigBundle\Config\ConfigManager;
use Oro\Bundle\CustomerBundle\Entity\CustomerUser;
use Oro\Bundle\CustomerBundle\Security\Token\AnonymousCustomerUserToken;
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
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Cache\CacheItemInterface;
use Psr\Cache\CacheItemPoolInterface;
use Symfony\Component\Security\Core\Authentication\Token\UsernamePasswordToken;

/**
 * @SuppressWarnings(PHPMD.TooManyMethods)
 * @SuppressWarnings(PHPMD.TooManyPublicMethods)
 * @SuppressWarnings(PHPMD.ExcessiveClassComplexity)
 * @SuppressWarnings(PHPMD.ExcessiveClassLength)
 */
class CurrentShoppingListManagerTest extends TestCase
{
    private ShoppingListManager&MockObject $shoppingListManager;
    private GuestShoppingListManager&MockObject $guestShoppingListManager;
    private CacheItemPoolInterface&MockObject $cache;
    private CacheItemInterface&MockObject $cacheItem;
    private AclHelper&MockObject $aclHelper;
    private TokenAccessorInterface&MockObject $tokenAccessor;
    private ShoppingListRepository&MockObject $shoppingListRepository;
    private ConfigManager&MockObject $configManager;
    private CurrentShoppingListManager $currentShoppingListManager;

    #[\Override]
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

        $doctrine = $this->createMock(ManagerRegistry::class);
        $doctrine->expects(self::any())
            ->method('getRepository')
            ->with(ShoppingList::class)
            ->willReturn($this->shoppingListRepository);

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

    private function expectGetCurrentShoppingList(?ShoppingList $shoppingList): void
    {
        $customerUserId = 234;
        $customerUser = $this->getCustomerUser($customerUserId);
        $this->tokenAccessor->expects(self::atLeastOnce())
            ->method('getUser')
            ->willReturn($customerUser);

        if (null === $shoppingList) {
            $this->expectCacheFetchAndNoSave($customerUserId, null);

            $this->shoppingListRepository->expects(self::never())
                ->method('findByUserAndId');
            $this->shoppingListRepository->expects(self::once())
                ->method('findAvailableForCustomerUser')
                ->with($this->aclHelper, false)
                ->willReturn(null);
        } else {
            $this->expectCacheFetchAndNoSave($customerUserId, $shoppingList->getId());

            $this->shoppingListRepository->expects(self::once())
                ->method('findByUserAndId')
                ->with($this->aclHelper, $shoppingList->getId())
                ->willReturn($shoppingList);
            $this->shoppingListRepository->expects(self::never())
                ->method('findAvailableForCustomerUser');
        }

        $this->shoppingListManager->expects(self::never())
            ->method('create');
    }

    private function expectCacheFetchAndSave(
        int $customerUserId,
        ?int $fetchShoppingListId,
        int $saveShoppingListId
    ): void {
        $this->cache->expects(self::any())
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
        $this->cache->expects(self::once())
            ->method('save')
            ->with($this->cacheItem);
    }

    private function expectCacheFetchAndNoSave(int $customerUserId, ?int $fetchShoppingListId): void
    {
        $this->cache->expects(self::once())
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
        $this->cache->expects(self::never())
            ->method('save');
    }

    private function expectCacheNoFetchAndNoSave(): void
    {
        $this->cache->expects(self::never())
            ->method('getItem');
        $this->cache->expects(self::never())
            ->method('save');
    }

    private function expectGetGuestShoppingList(?ShoppingList $shoppingList): void
    {
        $token = $this->createMock(AnonymousCustomerUserToken::class);
        $this->tokenAccessor->expects(self::once())
            ->method('getToken')
            ->willReturn($token);

        $this->guestShoppingListManager->expects(self::once())
            ->method('isGuestShoppingListAvailable')
            ->willReturn(true);
        $this->guestShoppingListManager->expects(self::once())
            ->method('getShoppingListForCustomerVisitor')
            ->willReturn($shoppingList);
        $this->guestShoppingListManager->expects(self::never())
            ->method('createAndGetShoppingListForCustomerVisitor');
    }

    private function expectCreateGuestShoppingList(ShoppingList $shoppingList): void
    {
        $token = $this->createMock(AnonymousCustomerUserToken::class);
        $this->tokenAccessor->expects(self::once())
            ->method('getToken')
            ->willReturn($token);

        $this->guestShoppingListManager->expects(self::once())
            ->method('isGuestShoppingListAvailable')
            ->willReturn(true);
        $this->guestShoppingListManager->expects(self::never())
            ->method('getShoppingListForCustomerVisitor');
        $this->guestShoppingListManager->expects(self::once())
            ->method('createAndGetShoppingListForCustomerVisitor')
            ->willReturn($shoppingList);
    }

    private function expectNoGuestShoppingList(string $tokenClass = UsernamePasswordToken::class): void
    {
        $token = $this->createMock($tokenClass);
        $this->tokenAccessor->expects(self::once())
            ->method('getToken')
            ->willReturn($token);

        $isAnonymousCustomerUserToken = AnonymousCustomerUserToken::class === $tokenClass;
        $this->guestShoppingListManager->expects($isAnonymousCustomerUserToken ? self::once() : self::never())
            ->method('isGuestShoppingListAvailable')
            ->willReturn(false);
        $this->guestShoppingListManager->expects(self::never())
            ->method('getShoppingListForCustomerVisitor');
        $this->guestShoppingListManager->expects(self::never())
            ->method('createAndGetShoppingListForCustomerVisitor');
    }

    private function expectNoGuestShoppingLists(): void
    {
        $this->guestShoppingListManager->expects(self::once())
            ->method('isGuestShoppingListAvailable')
            ->willReturn(false);
        $this->guestShoppingListManager->expects(self::never())
            ->method('getShoppingListsForCustomerVisitor');
    }

    private function setCustomerUserForTokenAccessor(int $customerUserId = 234): void
    {
        $customerUser = $this->getCustomerUser($customerUserId);
        $this->tokenAccessor->expects(self::atLeastOnce())
            ->method('getUser')
            ->willReturn($customerUser);
    }

    private function setCustomerUserForShoppingList(ShoppingList $shoppingList, int $customerUserId = 2345): void
    {
        $customerUser = $this->getCustomerUser($customerUserId);
        $shoppingList->setCustomerUser($customerUser);
    }

    public function labelDataProvider(): array
    {
        return [
            'without label' => [],
            'with label' => ['label' => 'test label']
        ];
    }

    /**
     * @dataProvider labelDataProvider
     */
    public function testCreateCurrent($label = ''): void
    {
        $shoppingListId = 123;
        $shoppingList = $this->getShoppingList($shoppingListId);

        $customerUserId = 234;
        $customerUser = $this->getCustomerUser($customerUserId);
        $this->tokenAccessor->expects(self::atLeastOnce())
            ->method('getUser')
            ->willReturn($customerUser);

        $this->shoppingListManager->expects(self::once())
            ->method('create')
            ->with(true, $label)
            ->willReturn($shoppingList);
        $this->cache->expects(self::once())
            ->method('getItem')
            ->with($customerUserId)
            ->willReturn($this->cacheItem);
        $this->cacheItem->expects(self::once())
            ->method('set')
            ->with($shoppingListId)
            ->willReturn($this->cacheItem);
        $this->cache->expects(self::once())
            ->method('save')
            ->with($this->cacheItem);

        self::assertSame($shoppingList, $this->currentShoppingListManager->createCurrent($label));
        self::assertTrue($shoppingList->isCurrent());
    }

    public function testSetCurrent(): void
    {
        $shoppingListId = 123;
        $shoppingList = $this->getShoppingList($shoppingListId);

        $customerUserId = 234;
        $customerUser = $this->getCustomerUser($customerUserId);

        $this->cache->expects(self::once())
            ->method('getItem')
            ->with($customerUserId)
            ->willReturn($this->cacheItem);
        $this->cacheItem->expects(self::once())
            ->method('set')
            ->with($shoppingListId)
            ->willReturn($this->cacheItem);
        $this->cache->expects(self::once())
            ->method('save')
            ->with($this->cacheItem);

        $this->currentShoppingListManager->setCurrent($customerUser, $shoppingList);
        self::assertTrue($shoppingList->isCurrent());
    }

    public function testSetCurrentWhenNewCustomerUserIsPassed(): void
    {
        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage('The customer user ID must not be empty.');

        $this->currentShoppingListManager->setCurrent(new CustomerUser(), $this->getShoppingList(123));
    }

    public function testSetCurrentWhenNewShoppingListIsPassed(): void
    {
        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage('The shopping list ID must not be empty.');

        $this->currentShoppingListManager->setCurrent($this->getCustomerUser(234), new ShoppingList());
    }

    public function testGetCurrentForGuestShoppingListWithCreate(): void
    {
        $shoppingList = new ShoppingList();

        $this->expectCreateGuestShoppingList($shoppingList);
        $this->expectCacheNoFetchAndNoSave();

        self::assertSame($shoppingList, $this->currentShoppingListManager->getCurrent(true));
    }

    public function testGetCurrentForGuestShoppingListWithoutCreate(): void
    {
        $shoppingList = new ShoppingList();

        $this->expectGetGuestShoppingList($shoppingList);
        $this->expectCacheNoFetchAndNoSave();

        self::assertSame($shoppingList, $this->currentShoppingListManager->getCurrent());
    }

    public function testGetCurrentForGuestShoppingListNoExistingShoppingList(): void
    {
        $this->expectGetGuestShoppingList(null);
        $this->expectCacheNoFetchAndNoSave();

        self::assertNull($this->currentShoppingListManager->getCurrent());
    }

    public function testGetCurrentNoCustomerUserInSecurityContext(): void
    {
        $this->tokenAccessor->expects(self::atLeastOnce())
            ->method('getUser')
            ->willReturn(null);

        $this->expectNoGuestShoppingList();
        $this->expectCacheNoFetchAndNoSave();

        self::assertNull($this->currentShoppingListManager->getCurrent());
    }

    public function testGetCurrentHasCacheAndShoppingListExistsInDatabase(): void
    {
        $shoppingListId = 123;
        $shoppingList = $this->getShoppingList($shoppingListId);

        $customerUserId = 234;
        $customerUser = $this->getCustomerUser($customerUserId);
        $this->tokenAccessor->expects(self::atLeastOnce())
            ->method('getUser')
            ->willReturn($customerUser);

        $this->expectNoGuestShoppingList();
        $this->expectCacheFetchAndNoSave($customerUserId, $shoppingListId);

        $this->shoppingListRepository->expects(self::once())
            ->method('findByUserAndId')
            ->with($this->aclHelper, $shoppingListId)
            ->willReturn($shoppingList);
        $this->shoppingListRepository->expects(self::never())
            ->method('findAvailableForCustomerUser');
        $this->shoppingListManager->expects(self::never())
            ->method('create');

        self::assertSame($shoppingList, $this->currentShoppingListManager->getCurrent(true));
        self::assertTrue($shoppingList->isCurrent());
    }

    public function testGetCurrentHasCacheAndNoShoppingListInDatabaseButFoundAnotherShoppingListForCustomerUser(): void
    {
        $shoppingListId = 123;
        $anotherShoppingListId = 1234;
        $anotherShoppingList = $this->getShoppingList($anotherShoppingListId);

        $customerUserId = 234;
        $customerUser = $this->getCustomerUser($customerUserId);
        $this->tokenAccessor->expects(self::atLeastOnce())
            ->method('getUser')
            ->willReturn($customerUser);

        $this->expectNoGuestShoppingList();
        $this->expectCacheFetchAndSave($customerUserId, $shoppingListId, $anotherShoppingListId);

        $this->shoppingListRepository->expects(self::once())
            ->method('findByUserAndId')
            ->with($this->aclHelper, $shoppingListId)
            ->willReturn(null);
        $this->shoppingListRepository->expects(self::once())
            ->method('findAvailableForCustomerUser')
            ->with($this->aclHelper, false)
            ->willReturn($anotherShoppingList);
        $this->shoppingListManager->expects(self::never())
            ->method('create');

        self::assertSame($anotherShoppingList, $this->currentShoppingListManager->getCurrent(true));
        self::assertTrue($anotherShoppingList->isCurrent());
    }

    /**
     * @dataProvider labelDataProvider
     */
    public function testGetCurrentHasCacheButNoShoppingListInDatabaseWithCreate($label = ''): void
    {
        $shoppingListId = 123;
        $newShoppingListId = 1234;
        $newShoppingList = $this->getShoppingList($newShoppingListId);

        $customerUserId = 234;
        $customerUser = $this->getCustomerUser($customerUserId);
        $this->tokenAccessor->expects(self::atLeastOnce())
            ->method('getUser')
            ->willReturn($customerUser);

        $this->expectNoGuestShoppingList();
        $this->expectCacheFetchAndSave($customerUserId, $shoppingListId, $newShoppingListId);

        $this->shoppingListRepository->expects(self::once())
            ->method('findByUserAndId')
            ->with($this->aclHelper, $shoppingListId)
            ->willReturn(null);
        $this->shoppingListRepository->expects(self::once())
            ->method('findAvailableForCustomerUser')
            ->with($this->aclHelper, false)
            ->willReturn(null);
        $this->shoppingListManager->expects(self::once())
            ->method('create')
            ->with(true, $label)
            ->willReturn($newShoppingList);

        self::assertSame($newShoppingList, $this->currentShoppingListManager->getCurrent(true, $label));
        self::assertTrue($newShoppingList->isCurrent());
    }

    public function testGetCurrentHasCacheButNoShoppingListInDatabaseWithoutCreate(): void
    {
        $shoppingListId = 123;

        $customerUserId = 234;
        $customerUser = $this->getCustomerUser($customerUserId);
        $this->tokenAccessor->expects(self::atLeastOnce())
            ->method('getUser')
            ->willReturn($customerUser);

        $this->expectNoGuestShoppingList();
        $this->expectCacheFetchAndNoSave($customerUserId, $shoppingListId);

        $this->shoppingListRepository->expects(self::once())
            ->method('findByUserAndId')
            ->with($this->aclHelper, $shoppingListId)
            ->willReturn(null);
        $this->shoppingListRepository->expects(self::once())
            ->method('findAvailableForCustomerUser')
            ->with($this->aclHelper, false)
            ->willReturn(null);

        self::assertNull($this->currentShoppingListManager->getCurrent());
    }

    public function testGetForCurrentUserForGuestShoppingList(): void
    {
        $shoppingListId = 123;
        $shoppingList = $this->getShoppingList($shoppingListId);

        $this->expectCreateGuestShoppingList($shoppingList);

        $this->shoppingListRepository->expects(self::never())
            ->method('findByUserAndId');

        self::assertSame($shoppingList, $this->currentShoppingListManager->getForCurrentUser(1234));
    }

    public function testGetForCurrentUserWhenAnonymousAndGuestShoppingListNotEnabled(): void
    {
        $this->expectNoGuestShoppingList(AnonymousCustomerUserToken::class);

        $this->shoppingListRepository->expects(self::never())
            ->method('findByUserAndId');

        self::assertNull($this->currentShoppingListManager->getForCurrentUser());
    }

    public function testGetForCurrentUserWithShoppingListIdAndShoppingListExists(): void
    {
        $shoppingListId = 123;
        $shoppingList = $this->getShoppingList($shoppingListId);

        $this->expectNoGuestShoppingList();

        $this->shoppingListRepository->expects(self::once())
            ->method('findByUserAndId')
            ->with($this->aclHelper, $shoppingListId)
            ->willReturn($shoppingList);

        self::assertSame($shoppingList, $this->currentShoppingListManager->getForCurrentUser($shoppingListId));
    }

    public function testGetForCurrentUserWithShoppingListIdAndShoppingListDoesNotExistWithCreate(): void
    {
        $shoppingListId = 123;
        $newShoppingListId = 1234;
        $newShoppingList = $this->getShoppingList($newShoppingListId);

        $customerUserId = 234;
        $this->setCustomerUserForTokenAccessor($customerUserId);

        $this->expectNoGuestShoppingList();
        $this->expectCacheFetchAndSave($customerUserId, null, $newShoppingListId);

        $this->shoppingListRepository->expects(self::once())
            ->method('findByUserAndId')
            ->with($this->aclHelper, $shoppingListId)
            ->willReturn(null);
        $this->shoppingListRepository->expects(self::once())
            ->method('findAvailableForCustomerUser')
            ->with($this->aclHelper, false)
            ->willReturn(null);
        $this->shoppingListManager->expects(self::once())
            ->method('create')
            ->with(true, '')
            ->willReturn($newShoppingList);

        self::assertEquals(
            $newShoppingList,
            $this->currentShoppingListManager->getForCurrentUser($shoppingListId, true)
        );
    }

    public function testGetForCurrentUserWithShoppingListIdAndShoppingListDoesNotExistWithoutCreate(): void
    {
        $shoppingListId = 123;

        $customerUserId = 234;
        $this->setCustomerUserForTokenAccessor($customerUserId);

        $this->expectNoGuestShoppingList();
        $this->cache->expects(self::once())
            ->method('getItem')
            ->with($customerUserId)
            ->willReturn($this->cacheItem);
        $this->cacheItem->expects(self::once())
            ->method('isHit')
            ->willReturn(false);
        $this->shoppingListRepository->expects(self::once())
            ->method('findByUserAndId')
            ->with($this->aclHelper, $shoppingListId)
            ->willReturn(null);
        $this->shoppingListRepository->expects(self::once())
            ->method('findAvailableForCustomerUser')
            ->with($this->aclHelper, false)
            ->willReturn(null);
        $this->shoppingListManager->expects(self::never())
            ->method('create');

        self::assertNull($this->currentShoppingListManager->getForCurrentUser($shoppingListId));
    }

    public function testGetForCurrentUserWithoutShoppingListIdWithCreate(): void
    {
        $newShoppingListId = 1234;
        $newShoppingList = $this->getShoppingList($newShoppingListId);

        $customerUserId = 234;
        $this->setCustomerUserForTokenAccessor($customerUserId);

        $this->expectNoGuestShoppingList();
        $this->expectCacheFetchAndSave($customerUserId, null, $newShoppingListId);

        $this->shoppingListRepository->expects(self::never())
            ->method('findByUserAndId');
        $this->shoppingListRepository->expects(self::once())
            ->method('findAvailableForCustomerUser')
            ->with($this->aclHelper, false)
            ->willReturn(null);
        $this->shoppingListManager->expects(self::once())
            ->method('create')
            ->with(true, '')
            ->willReturn($newShoppingList);

        self::assertEquals($newShoppingList, $this->currentShoppingListManager->getForCurrentUser(null, true));
    }

    public function testGetForCurrentUserWithoutShoppingListIdWithoutCreate(): void
    {
        $customerUserId = 234;
        $this->setCustomerUserForTokenAccessor($customerUserId);

        $this->expectNoGuestShoppingList();
        $this->cache->expects(self::once())
            ->method('getItem')
            ->with($customerUserId)
            ->willReturn($this->cacheItem);
        $this->cacheItem->expects(self::once())
            ->method('isHit')
            ->willReturn(false);
        $this->shoppingListRepository->expects(self::never())
            ->method('findByUserAndId');
        $this->shoppingListRepository->expects(self::once())
            ->method('findAvailableForCustomerUser')
            ->with($this->aclHelper, false)
            ->willReturn(null);
        $this->shoppingListManager->expects(self::never())
            ->method('create');

        self::assertNull($this->currentShoppingListManager->getForCurrentUser());
    }

    public function testGetForCurrentUserShareShoppingListExistedButShowAllShoppingListOptionOffWithCreate(): void
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
        $this->shoppingListRepository->expects(self::never())
            ->method('findByUserAndId');
        $this->shoppingListRepository->expects(self::once())
            ->method('findAvailableForCustomerUser')
            ->with($this->aclHelper, false)
            ->willReturn($shoppingList);
        $this->configManager->expects(self::once())
            ->method('get')
            ->with('oro_shopping_list.show_all_in_shopping_list_widget')
            ->willReturn(false);
        $this->shoppingListManager->expects(self::once())
            ->method('create')
            ->with(true, '')
            ->willReturn($newShoppingList);

        self::assertSame($newShoppingList, $this->currentShoppingListManager->getForCurrentUser(null, true));
    }

    public function testGetForCurrentUserShareShoppingListExistedButShowAllShoppingListOptionOffWithoutCreate(): void
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
        $this->shoppingListRepository->expects(self::never())
            ->method('findByUserAndId');
        $this->shoppingListRepository->expects(self::once())
            ->method('findAvailableForCustomerUser')
            ->with($this->aclHelper, false)
            ->willReturn($shoppingList);
        $this->configManager->expects(self::once())
            ->method('get')
            ->with('oro_shopping_list.show_all_in_shopping_list_widget')
            ->willReturn(false);
        $this->shoppingListManager->expects(self::never())
            ->method('create');

        self::assertNull($this->currentShoppingListManager->getForCurrentUser());
    }

    public function testGetForCurrentUserShareShoppingListExistedAndShowAllShoppingListOptionOn(): void
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
        $this->shoppingListRepository->expects(self::never())
            ->method('findByUserAndId');
        $this->shoppingListRepository->expects(self::once())
            ->method('findAvailableForCustomerUser')
            ->with($this->aclHelper, false)
            ->willReturn($shoppingList);
        $this->configManager->expects(self::once())
            ->method('get')
            ->with('oro_shopping_list.show_all_in_shopping_list_widget')
            ->willReturn(true);

        self::assertSame($shoppingList, $this->currentShoppingListManager->getForCurrentUser());
    }

    public function testGetShoppingListsWithCurrentFirstForGuestShoppingLists(): void
    {
        $shoppingLists = [$this->getShoppingList(123)];

        $this->tokenAccessor->expects(self::never())
            ->method('getUser');

        $this->expectCacheNoFetchAndNoSave();

        $this->guestShoppingListManager->expects(self::once())
            ->method('isGuestShoppingListAvailable')
            ->willReturn(true);
        $this->guestShoppingListManager->expects(self::once())
            ->method('getShoppingListsForCustomerVisitor')
            ->willReturn($shoppingLists);

        self::assertEquals(
            $shoppingLists,
            $this->currentShoppingListManager->getShoppingListsWithCurrentFirst()
        );
    }

    public function testGetShoppingListsWithCurrentFirstWhenNoCurrentShoppingList(): void
    {
        $this->expectNoGuestShoppingLists();
        $this->expectGetCurrentShoppingList(null);

        self::assertSame([], $this->currentShoppingListManager->getShoppingListsWithCurrentFirst());
    }

    public function testGetShoppingListsWithCurrentFirstWhenCurrentShoppingListExists(): void
    {
        $sortCriteria = ['label' => 'ASC'];

        $currentShoppingList = $this->getShoppingList(123);
        $shoppingLists = [$this->getShoppingList(11)];
        $expectedShoppingLists = [$currentShoppingList, $shoppingLists[0]];

        $this->expectNoGuestShoppingLists();
        $this->expectGetCurrentShoppingList($currentShoppingList);

        $this->shoppingListRepository->expects(self::once())
            ->method('findByUser')
            ->with($this->aclHelper, $sortCriteria, $currentShoppingList)
            ->willReturn($shoppingLists);

        self::assertEquals(
            $expectedShoppingLists,
            $this->currentShoppingListManager->getShoppingListsWithCurrentFirst($sortCriteria)
        );
    }

    public function testGetShoppingListsForCustomerUserWithCurrentFirstWhenNoCurrentShoppingList(): void
    {
        $customerUserId = 234;
        $customerUser = $this->getCustomerUser($customerUserId);
        $this->tokenAccessor->expects(self::atLeastOnce())
            ->method('getUser')
            ->willReturn($customerUser);

        $this->expectCacheFetchAndNoSave($customerUserId, null);

        $this->shoppingListRepository->expects(self::never())
            ->method('findByUserAndId');
        $this->shoppingListRepository->expects(self::once())
            ->method('findAvailableForCustomerUser')
            ->with($this->aclHelper, false)
            ->willReturn(null);

        $this->shoppingListManager->expects(self::never())
            ->method('create');

        self::assertSame(
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
        $this->tokenAccessor->expects(self::atLeastOnce())
            ->method('getUser')
            ->willReturn($customerUser);

        $this->expectCacheFetchAndNoSave($customerUserId, $currentShoppingList->getId());

        $this->shoppingListRepository->expects(self::once())
            ->method('findByUserAndId')
            ->with($this->aclHelper, $currentShoppingList->getId())
            ->willReturn($currentShoppingList);
        $this->shoppingListRepository->expects(self::never())
            ->method('findAvailableForCustomerUser');

        $this->shoppingListManager->expects(self::never())
            ->method('create');

        $this->shoppingListRepository->expects(self::once())
            ->method('findByCustomerUserId')
            ->with($customerUserId, $this->aclHelper, $sortCriteria, $currentShoppingList)
            ->willReturn($shoppingLists);

        self::assertEquals(
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
        $this->tokenAccessor->expects(self::atLeastOnce())
            ->method('getUser')
            ->willReturn($customerUser);

        $this->expectCacheFetchAndSave($customerUserId, $currentShoppingList->getId(), 11);

        $this->shoppingListRepository->expects(self::once())
            ->method('findByUserAndId')
            ->with($this->aclHelper, $currentShoppingList->getId())
            ->willReturn($currentShoppingList);
        $this->shoppingListRepository->expects(self::never())
            ->method('findAvailableForCustomerUser');

        $this->shoppingListManager->expects(self::never())
            ->method('create');

        $this->shoppingListRepository->expects(self::once())
            ->method('findByCustomerUserId')
            ->with($customerUserId, $this->aclHelper, $sortCriteria, null)
            ->willReturn($shoppingLists);

        self::assertEquals(
            $expectedShoppingLists,
            $this->currentShoppingListManager->getShoppingListsForCustomerUserWithCurrentFirst(
                $customerUserId,
                $sortCriteria
            )
        );
    }

    public function testGetShoppingListsForGuestShoppingLists(): void
    {
        $shoppingLists = [$this->getShoppingList(123)];

        $this->tokenAccessor->expects(self::never())
            ->method('getUser');

        $this->expectCacheNoFetchAndNoSave();

        $this->guestShoppingListManager->expects(self::once())
            ->method('isGuestShoppingListAvailable')
            ->willReturn(true);
        $this->guestShoppingListManager->expects(self::once())
            ->method('getShoppingListsForCustomerVisitor')
            ->willReturn($shoppingLists);

        self::assertEquals(
            $shoppingLists,
            $this->currentShoppingListManager->getShoppingLists()
        );
    }

    public function testGetShoppingLists(): void
    {
        $sortCriteria = ['label' => 'ASC'];
        $shoppingLists = [$this->getShoppingList(123)];

        $this->expectNoGuestShoppingLists();

        $this->shoppingListRepository->expects(self::once())
            ->method('findByUser')
            ->with($this->aclHelper, $sortCriteria, null)
            ->willReturn($shoppingLists);

        self::assertEquals(
            $shoppingLists,
            $this->currentShoppingListManager->getShoppingLists($sortCriteria)
        );
    }

    public function testGetShoppingListsByCustomerUserWhenGuestShoppingLists(): void
    {
        $shoppingLists = [$this->getShoppingList(123)];

        $customerUser = $this->getCustomerUser(42);

        $this->tokenAccessor->expects(self::never())
            ->method('getUser');

        $this->expectCacheNoFetchAndNoSave();

        $this->guestShoppingListManager->expects(self::once())
            ->method('isGuestShoppingListAvailable')
            ->willReturn(true);
        $this->guestShoppingListManager->expects(self::once())
            ->method('getShoppingListsForCustomerVisitor')
            ->willReturn($shoppingLists);

        self::assertEquals(
            $shoppingLists,
            $this->currentShoppingListManager->getShoppingListsByCustomerUser($customerUser)
        );
    }

    public function testGetShoppingListsByCustomerUser(): void
    {
        $sortCriteria = ['label' => 'ASC'];
        $shoppingLists = [$this->getShoppingList(123)];

        $customerUser = $this->getCustomerUser(42);

        $this->expectNoGuestShoppingLists();

        $this->shoppingListRepository->expects(self::once())
            ->method('findByCustomerUserId')
            ->with($customerUser->getId(), $this->aclHelper, $sortCriteria, null)
            ->willReturn($shoppingLists);

        self::assertEquals(
            $shoppingLists,
            $this->currentShoppingListManager->getShoppingListsByCustomerUser($customerUser, $sortCriteria)
        );
    }

    public function testIsCurrentShoppingListEmptyForGuestShoppingListWhenItDoesNotExist(): void
    {
        $this->expectGetGuestShoppingList(null);

        self::assertTrue($this->currentShoppingListManager->isCurrentShoppingListEmpty());
    }

    public function testIsCurrentShoppingListEmptyForGuestShoppingListWithoutLineItems(): void
    {
        $shoppingList = $this->getShoppingList(123);

        $this->expectGetGuestShoppingList($shoppingList);

        self::assertTrue($this->currentShoppingListManager->isCurrentShoppingListEmpty());
    }

    public function testIsCurrentShoppingListEmptyForGuestShoppingListWithLineItems(): void
    {
        $shoppingList = $this->getShoppingList(123);
        $shoppingList->addLineItem(new LineItem());

        $this->expectGetGuestShoppingList($shoppingList);

        self::assertFalse($this->currentShoppingListManager->isCurrentShoppingListEmpty());
    }

    public function testIsCurrentShoppingListEmptyWhenShoppingListDoesNotExist(): void
    {
        $this->expectNoGuestShoppingList();
        $this->expectGetCurrentShoppingList(null);

        self::assertTrue($this->currentShoppingListManager->isCurrentShoppingListEmpty());
    }

    public function testIsCurrentShoppingListEmptyForShoppingListWithoutLineItems(): void
    {
        $shoppingList = $this->getShoppingList(123);

        $this->expectNoGuestShoppingList();
        $this->expectGetCurrentShoppingList($shoppingList);

        self::assertTrue($this->currentShoppingListManager->isCurrentShoppingListEmpty());
    }

    public function testIsCurrentShoppingListEmptyForShoppingListWithLineItems(): void
    {
        $shoppingList = $this->getShoppingList(123);
        $shoppingList->addLineItem(new LineItem());

        $this->expectNoGuestShoppingList();
        $this->expectGetCurrentShoppingList($shoppingList);

        self::assertFalse($this->currentShoppingListManager->isCurrentShoppingListEmpty());
    }
}
