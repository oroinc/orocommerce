<?php

namespace Oro\Bundle\ShoppingListBundle\Tests\Unit\Manager;

use Doctrine\Common\Cache\Cache;
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
use Oro\Component\Testing\Unit\EntityTrait;

/**
 * @SuppressWarnings(PHPMD.TooManyMethods)
 * @SuppressWarnings(PHPMD.TooManyPublicMethods)
 * @SuppressWarnings(PHPMD.ExcessiveClassComplexity)
 */
class CurrentShoppingListManagerTest extends \PHPUnit\Framework\TestCase
{
    use EntityTrait;

    /** @var CurrentShoppingListManager */
    private $currentShoppingListManager;

    /** @var ShoppingListManager|\PHPUnit\Framework\MockObject\MockObject */
    private $shoppingListManager;

    /** @var GuestShoppingListManager|\PHPUnit\Framework\MockObject\MockObject */
    private $guestShoppingListManager;

    /** @var Cache|\PHPUnit\Framework\MockObject\MockObject */
    private $cache;

    /** @var AclHelper|\PHPUnit\Framework\MockObject\MockObject */
    private $aclHelper;

    /** @var TokenAccessorInterface|\PHPUnit\Framework\MockObject\MockObject */
    private $tokenAccessor;

    /** @var EntityManagerInterface|\PHPUnit\Framework\MockObject\MockObject */
    private $shoppingListEntityManager;

    /** @var ShoppingListRepository|\PHPUnit\Framework\MockObject\MockObject */
    private $shoppingListRepository;

    /** @var \PHPUnit\Framework\MockObject\MockObject|ConfigManager */
    private $configManager;

    protected function setUp(): void
    {
        $this->shoppingListManager = $this->createMock(ShoppingListManager::class);
        $this->guestShoppingListManager = $this->createMock(GuestShoppingListManager::class);
        $this->cache = $this->createMock(Cache::class);
        $this->aclHelper = $this->createMock(AclHelper::class);
        $this->tokenAccessor = $this->createMock(TokenAccessorInterface::class);
        $this->configManager = $this->createMock(ConfigManager::class);

        $this->shoppingListEntityManager = $this->createMock(EntityManagerInterface::class);
        $this->shoppingListRepository = $this->createMock(ShoppingListRepository::class);
        $doctrine = $this->createMock(ManagerRegistry::class);
        $doctrine->expects($this->any())
            ->method('getManagerForClass')
            ->with(ShoppingList::class)
            ->willReturn($this->shoppingListEntityManager);
        $this->shoppingListEntityManager->expects($this->any())
            ->method('getRepository')
            ->with(ShoppingList::class)
            ->willReturn($this->shoppingListRepository);

        $this->currentShoppingListManager = new CurrentShoppingListManager(
            $this->shoppingListManager,
            $this->guestShoppingListManager,
            new CurrentShoppingListStorage($this->cache),
            $doctrine,
            $this->aclHelper,
            $this->tokenAccessor
        );
        $this->currentShoppingListManager->setConfigManager($this->configManager);
    }

    /**
     * @param int|null $id
     *
     * @return ShoppingList
     */
    private function getShoppingList($id = null)
    {
        return $this->getEntity(ShoppingList::class, ['id' => $id]);
    }

    /**
     * @param int|null $id
     *
     * @return CustomerUser
     */
    private function getCustomerUser($id = null)
    {
        return $this->getEntity(CustomerUser::class, ['id' => $id]);
    }

    private function expectGetCurrentShoppingList(?ShoppingList $shoppingList)
    {
        $customerUserId = 234;
        $customerUser = $this->getCustomerUser($customerUserId);
        $this->tokenAccessor->expects($this->atLeastOnce())
            ->method('getUser')
            ->willReturn($customerUser);

        if (null === $shoppingList) {
            $this->expectCacheFetchAndNoSave($customerUserId, false);

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

    /**
     * @param int      $customerUserId
     * @param int|bool $fetchShoppingListId
     * @param int      $saveShoppingListId
     */
    private function expectCacheFetchAndSave($customerUserId, $fetchShoppingListId, $saveShoppingListId)
    {
        $this->cache->expects($this->once())
            ->method('fetch')
            ->with($customerUserId)
            ->willReturn($fetchShoppingListId);
        $this->cache->expects($this->once())
            ->method('save')
            ->with($customerUserId, $saveShoppingListId);
    }

    /**
     * @param int      $customerUserId
     * @param int|bool $fetchShoppingListId
     */
    private function expectCacheFetchAndNoSave($customerUserId, $fetchShoppingListId)
    {
        $this->cache->expects($this->once())
            ->method('fetch')
            ->with($customerUserId)
            ->willReturn($fetchShoppingListId);
        $this->cache->expects($this->never())
            ->method('save');
    }

    private function expectCacheNoFetchAndNoSave()
    {
        $this->cache->expects($this->never())
            ->method('fetch');
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

    public function labelDataProvider()
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
            ->method('save')
            ->with($customerUserId, $shoppingListId);

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
            ->method('save')
            ->with($customerUserId, $shoppingListId);

        $this->currentShoppingListManager->setCurrent($customerUser, $shoppingList);
        $this->assertTrue($shoppingList->isCurrent());
    }

    public function testSetCurrentWhenNewCustomerUserIsPassed()
    {
        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage('The customer user ID must not be empty.');

        $this->currentShoppingListManager
            ->setCurrent($this->getCustomerUser(), $this->getShoppingList(123));
    }

    public function testSetCurrentWhenNewShoppingListIsPassed()
    {
        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage('The shopping list ID must not be empty.');

        $this->currentShoppingListManager
            ->setCurrent($this->getCustomerUser(234), $this->getShoppingList());
    }

    public function testGetCurrentForGuestShoppingListWithCreate()
    {
        $shoppingList = $this->getShoppingList();

        $this->expectCreateGuestShoppingList($shoppingList);
        $this->expectCacheNoFetchAndNoSave();

        $this->assertSame($shoppingList, $this->currentShoppingListManager->getCurrent(true));
    }

    public function testGetCurrentForGuestShoppingListWithoutCreate()
    {
        $shoppingList = $this->getShoppingList();

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

    public function testGetForCurrentUserWithShoppingListIdAndShoppingListDoesNotExist()
    {
        $shoppingListId = 123;
        $newShoppingListId = 1234;
        $newShoppingList = $this->getShoppingList($newShoppingListId);

        $customerUserId = 234;
        $this->setCustomerUserForTokenAccessor($customerUserId);

        $this->expectNoGuestShoppingList();
        $this->expectCacheFetchAndSave($customerUserId, false, $newShoppingListId);

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
            $this->currentShoppingListManager->getForCurrentUser($shoppingListId)
        );
    }

    public function testGetForCurrentUserWithoutShoppingListId()
    {
        $newShoppingListId = 1234;
        $newShoppingList = $this->getShoppingList($newShoppingListId);

        $customerUserId = 234;
        $this->setCustomerUserForTokenAccessor($customerUserId);

        $this->expectNoGuestShoppingList();
        $this->expectCacheFetchAndSave($customerUserId, false, $newShoppingListId);

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

        $this->assertEquals($newShoppingList, $this->currentShoppingListManager->getForCurrentUser());
    }

    public function testGetShoppingListForCurrentUserWithShoppingListIdAndShoppingListDoesNotExistWithoutCreate()
    {
        $shoppingListId = 123;

        $customerUserId = 234;
        $this->setCustomerUserForTokenAccessor($customerUserId);

        $this->expectNoGuestShoppingList();
        $this->cache->expects($this->once())
            ->method('fetch')
            ->with($customerUserId)
            ->willReturn(null);
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

        $this->assertNull($this->currentShoppingListManager->getShoppingListForCurrentUser($shoppingListId, false));
    }

    public function testGetShoppingListForCurrentUserWithoutShoppingListIdWithoutCreate()
    {
        $customerUserId = 234;
        $this->setCustomerUserForTokenAccessor($customerUserId);

        $this->expectNoGuestShoppingList();
        $this->cache->expects($this->once())
            ->method('fetch')
            ->with($customerUserId)
            ->willReturn(null);
        $this->shoppingListRepository->expects($this->never())
            ->method('findByUserAndId');
        $this->shoppingListRepository->expects($this->once())
            ->method('findAvailableForCustomerUser')
            ->with($this->aclHelper, false)
            ->willReturn(null);
        $this->shoppingListManager->expects($this->never())
            ->method('create');

        $this->assertNull($this->currentShoppingListManager->getShoppingListForCurrentUser(null, false));
    }

    public function testGetShoppingListForCurrentUserShareShoppingListExistedButShowAllShoppingListOptionOffWithCreate()
    {
        $shoppingListId = 123;
        $shoppingList = $this->getShoppingList($shoppingListId);
        $newShoppingListId = 1234;
        $newShoppingList = $this->getShoppingList($newShoppingListId);

        $customerUserId = 234;
        $this->setCustomerUserForTokenAccessor($customerUserId);
        $this->setCustomerUserForShoppingList($shoppingList);

        $this->expectNoGuestShoppingList();
        $this->cache->expects($this->once())
            ->method('fetch')
            ->with($customerUserId)
            ->willReturn(null);
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

        $this->assertSame($newShoppingList, $this->currentShoppingListManager->getShoppingListForCurrentUser());
    }

    public function testGetShoppingListForCurrentUserShareShoppingListExistedButShowAllOptionOffWithoutCreate()
    {
        $shoppingListId = 123;
        $shoppingList = $this->getShoppingList($shoppingListId);

        $customerUserId = 234;
        $this->setCustomerUserForTokenAccessor($customerUserId);
        $this->setCustomerUserForShoppingList($shoppingList);

        $this->expectNoGuestShoppingList();
        $this->cache->expects($this->once())
            ->method('fetch')
            ->with($customerUserId)
            ->willReturn(null);
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

        $this->assertNull($this->currentShoppingListManager->getShoppingListForCurrentUser(null, false));
    }

    public function testGetForCurrentUserShareShoppingListExistedAndShowAllShoppingListOptionOn()
    {
        $shoppingListId = 123;
        $shoppingList = $this->getShoppingList($shoppingListId);

        $customerUserId = 234;
        $this->setCustomerUserForTokenAccessor($customerUserId);
        $this->setCustomerUserForShoppingList($shoppingList);

        $this->expectNoGuestShoppingList();
        $this->cache->expects($this->once())
            ->method('fetch')
            ->with($customerUserId)
            ->willReturn(null);
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

        $this->assertSame($shoppingList, $this->currentShoppingListManager->getShoppingListForCurrentUser());
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

        $this->expectCacheFetchAndNoSave($customerUserId, false);

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
        /** @var CustomerUser $customerUser1 */
        $customerUser1 = $this->getEntity(CustomerUser::class, ['id' => $customerUserId]);
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
        /** @var CustomerUser $customerUser1 */
        $customerUser1 = $this->getEntity(CustomerUser::class, ['id' => 42]);
        $currentShoppingList->setCustomerUser($customerUser1);

        $shoppingLists = [$this->getShoppingList(11)];
        $shoppingLists[0]->setCustomerUser($this->getEntity(CustomerUser::class, ['id' => $customerUserId]));
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
