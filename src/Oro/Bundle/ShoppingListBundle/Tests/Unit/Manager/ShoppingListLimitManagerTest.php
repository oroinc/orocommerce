<?php

namespace Oro\Bundle\ShoppingListBundle\Tests\Unit\Manager;

use Oro\Bundle\ConfigBundle\Config\ConfigManager;
use Oro\Bundle\CustomerBundle\Entity\CustomerUser;
use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;
use Oro\Bundle\OrganizationBundle\Entity\Organization;
use Oro\Bundle\ShoppingListBundle\Entity\Repository\ShoppingListRepository;
use Oro\Bundle\ShoppingListBundle\Entity\ShoppingList;
use Oro\Bundle\ShoppingListBundle\Manager\ShoppingListLimitManager;
use Oro\Bundle\WebsiteBundle\Entity\Website;
use Oro\Bundle\WebsiteBundle\Manager\WebsiteManager;
use Oro\Component\Testing\ReflectionUtil;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;

/**
 * @SuppressWarnings(PHPMD.TooManyPublicMethods)
 */
class ShoppingListLimitManagerTest extends \PHPUnit\Framework\TestCase
{
    private const USER_ID = 777;
    private const ORGANIZATION_ID = 555;
    private const WEBSITE_ID = 888;

    /** @var ConfigManager|\PHPUnit\Framework\MockObject\MockObject */
    private $configManager;

    /** @var TokenStorageInterface|\PHPUnit\Framework\MockObject\MockObject */
    private $tokenStorage;

    /** @var DoctrineHelper|\PHPUnit\Framework\MockObject\MockObject */
    private $doctrineHelper;

    /** @var WebsiteManager|\PHPUnit\Framework\MockObject\MockObject */
    private $websiteManager;

    /** @var ShoppingListLimitManager */
    private $shoppingListLimitManager;

    protected function setUp(): void
    {
        $this->configManager = $this->createMock(ConfigManager::class);
        $this->tokenStorage = $this->createMock(TokenStorageInterface::class);
        $this->doctrineHelper = $this->createMock(DoctrineHelper::class);
        $this->websiteManager = $this->createMock(WebsiteManager::class);

        $this->shoppingListLimitManager = new ShoppingListLimitManager(
            $this->configManager,
            $this->tokenStorage,
            $this->doctrineHelper,
            $this->websiteManager
        );
    }

    private function getCustomerUser(): CustomerUser
    {
        $customerUser = new CustomerUser();
        ReflectionUtil::setId($customerUser, self::USER_ID);
        $customerUser->setOrganization($this->getOrganization());
        $customerUser->setWebsite($this->getWebsite());

        return $customerUser;
    }

    private function getOrganization(): Organization
    {
        $organization = new Organization();
        $organization->setId(self::ORGANIZATION_ID);

        return $organization;
    }

    private function getWebsite(): Website
    {
        $website = new Website();
        ReflectionUtil::setId($website, self::WEBSITE_ID);

        return $website;
    }

    private function expectsGetUser(?CustomerUser $user, int $getUserCallCount = 1): void
    {
        $token = $this->createMock(TokenInterface::class);
        $token->expects($this->exactly($getUserCallCount))
            ->method('getUser')
            ->willReturn($user);

        $this->tokenStorage->expects($this->exactly($getUserCallCount))
            ->method('getToken')
            ->willReturn($token);
    }

    private function expectsCountUserShoppingLists(Website $website, int $count): void
    {
        $this->websiteManager->expects($this->once())
            ->method('getCurrentWebsite')
            ->willReturn($website);

        $repository = $this->createMock(ShoppingListRepository::class);
        $this->doctrineHelper->expects($this->once())
            ->method('getEntityRepository')
            ->with(ShoppingList::class)
            ->willReturn($repository);
        $repository->expects($this->once())
            ->method('countUserShoppingLists')
            ->with(self::USER_ID, self::ORGANIZATION_ID, $website)
            ->willReturn($count);
    }

    public function testIsReachedLimitForNotLoggedUser()
    {
        $this->expectsGetUser(null);

        $this->assertTrue($this->shoppingListLimitManager->isReachedLimit());
    }

    /**
     * @dataProvider limitDataProvider
     */
    public function testIsReachedLimit(int $limit, int $count, bool $expected)
    {
        $user = $this->getCustomerUser();

        $this->expectsGetUser($user);

        $this->configManager->expects($this->once())
            ->method('get')
            ->with('oro_shopping_list.shopping_list_limit')
            ->willReturn($limit);

        if ($limit) {
            $this->expectsCountUserShoppingLists($user->getWebsite(), $count);
        }

        $this->assertSame(!$expected, $this->shoppingListLimitManager->isReachedLimit());
    }

    public function testIsCreateEnabledForNotLoggedUser()
    {
        $this->expectsGetUser(null);

        $this->assertFalse($this->shoppingListLimitManager->isCreateEnabled());
    }

    /**
     * @dataProvider limitDataProvider
     */
    public function testIsCreateEnabled(int $limit, int $count, bool $expected)
    {
        $user = $this->getCustomerUser();

        $this->expectsGetUser($user, 2);

        $this->configManager->expects($this->once())
            ->method('get')
            ->with('oro_shopping_list.shopping_list_limit')
            ->willReturn($limit);

        if ($limit) {
            $this->expectsCountUserShoppingLists($user->getWebsite(), $count);
        }

        $this->assertSame($expected, $this->shoppingListLimitManager->isCreateEnabled());
        // Check internal cache
        $this->assertSame($expected, $this->shoppingListLimitManager->isCreateEnabled());
    }

    public function limitDataProvider(): array
    {
        return [
            'without limit' => [
                'limit' => 0,
                'count' => 5,
                'expected' => true,
            ],
            'with not reached limit' => [
                'limit' => 5,
                'count' => 4,
                'expected' => true,
            ],
            'with reached limit' => [
                'limit' => 5,
                'count' => 5,
                'expected' => false,
            ],
        ];
    }

    public function testIsCreateEnabledForCustomerUserWhenLimitNotSet()
    {
        $user = $this->getCustomerUser();

        $this->configManager->expects($this->once())
            ->method('get')
            ->with('oro_shopping_list.shopping_list_limit', false, false, $user->getWebsite())
            ->willReturn(0);

        $this->assertTrue($this->shoppingListLimitManager->isCreateEnabledForCustomerUser($user));
    }

    /**
     * @dataProvider isCreateEnabledForUserDataProvider
     */
    public function testIsCreateEnabledForCustomerUser(int $actualShoppingListCount, bool $result)
    {
        $user = $this->getCustomerUser();
        $website = $user->getWebsite();

        $this->configManager->expects($this->once())
            ->method('get')
            ->with('oro_shopping_list.shopping_list_limit', false, false, $website)
            ->willReturn(5);

        $repository = $this->createMock(ShoppingListRepository::class);
        $this->doctrineHelper->expects($this->once())
            ->method('getEntityRepository')
            ->with(ShoppingList::class)
            ->willReturn($repository);
        $repository->expects($this->once())
            ->method('countUserShoppingLists')
            ->with(self::USER_ID, self::ORGANIZATION_ID)
            ->willReturn($actualShoppingListCount);

        $this->assertSame($result, $this->shoppingListLimitManager->isCreateEnabledForCustomerUser($user));
        // Check internal cache
        $this->assertSame($result, $this->shoppingListLimitManager->isCreateEnabledForCustomerUser($user));
    }

    public function isCreateEnabledForUserDataProvider(): array
    {
        return [
            'shopping list limit set' => [
                'actualShoppingListCount' => 4,
                'result' => true,
            ],
            'shopping list limit reached' => [
                'actualShoppingListCount' => 5,
                'result' => false,
            ],
        ];
    }

    public function testIsOnlyOneEnabledForNotLoggedUser()
    {
        $this->expectsGetUser(null);

        $this->assertTrue($this->shoppingListLimitManager->isOnlyOneEnabled());
    }

    public function testIsOnlyOneEnabledWhenLimitNotSet()
    {
        $user = $this->getCustomerUser();

        $this->expectsGetUser($user);

        $this->configManager->expects($this->once())
            ->method('get')
            ->with('oro_shopping_list.shopping_list_limit')
            ->willReturn(0);

        $this->assertFalse($this->shoppingListLimitManager->isOnlyOneEnabled());
    }

    public function testIsOnlyOneEnabled()
    {
        $user = $this->getCustomerUser();

        $this->expectsGetUser($user, 2);

        $this->configManager->expects($this->once())
            ->method('get')
            ->with('oro_shopping_list.shopping_list_limit')
            ->willReturn(1);

        $this->expectsCountUserShoppingLists($user->getWebsite(), 2);

        $this->assertFalse($this->shoppingListLimitManager->isOnlyOneEnabled());
        // Check internal cache
        $this->assertFalse($this->shoppingListLimitManager->isOnlyOneEnabled());
    }

    public function testIsOnlyOneEnabledWhenLimitAndNumberOfExistingShoppingListsEqualToOne()
    {
        $user = $this->getCustomerUser();

        $this->expectsGetUser($user);

        $this->configManager->expects($this->once())
            ->method('get')
            ->with('oro_shopping_list.shopping_list_limit')
            ->willReturn(1);

        $this->expectsCountUserShoppingLists($user->getWebsite(), 1);

        $this->assertTrue($this->shoppingListLimitManager->isOnlyOneEnabled());
    }

    public function testIsOnlyOneEnabledWhenConfigReturnsString(): void
    {
        $user = $this->getCustomerUser();

        $this->expectsGetUser($user);

        $this->configManager->expects($this->once())
            ->method('get')
            ->with('oro_shopping_list.shopping_list_limit')
            ->willReturn('1');

        $this->expectsCountUserShoppingLists($user->getWebsite(), 1);

        $this->assertTrue($this->shoppingListLimitManager->isOnlyOneEnabled());
    }

    public function testGetShoppingListLimitForUser()
    {
        $user = $this->getCustomerUser();

        $this->expectsGetUser($user);

        $this->configManager->expects($this->once())
            ->method('get')
            ->with('oro_shopping_list.shopping_list_limit')
            ->willReturn(2);

        $this->assertSame(2, $this->shoppingListLimitManager->getShoppingListLimitForUser());
    }
}
