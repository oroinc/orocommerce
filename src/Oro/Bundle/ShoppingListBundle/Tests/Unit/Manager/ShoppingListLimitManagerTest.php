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
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;

/**
 * @SuppressWarnings(PHPMD.TooManyPublicMethods)
 */
class ShoppingListLimitManagerTest extends TestCase
{
    private const USER_ID = 777;
    private const ORGANIZATION_ID = 555;
    private const WEBSITE_ID = 888;

    private ConfigManager&MockObject $configManager;
    private TokenStorageInterface&MockObject $tokenStorage;
    private DoctrineHelper&MockObject $doctrineHelper;
    private WebsiteManager&MockObject $websiteManager;
    private ShoppingListLimitManager $shoppingListLimitManager;

    #[\Override]
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
        $token->expects(self::exactly($getUserCallCount))
            ->method('getUser')
            ->willReturn($user);

        $this->tokenStorage->expects(self::exactly($getUserCallCount))
            ->method('getToken')
            ->willReturn($token);
    }

    private function expectsCountUserShoppingLists(Website $website, int $count): void
    {
        $this->websiteManager->expects(self::once())
            ->method('getCurrentWebsite')
            ->willReturn($website);

        $repository = $this->createMock(ShoppingListRepository::class);
        $this->doctrineHelper->expects(self::once())
            ->method('getEntityRepository')
            ->with(ShoppingList::class)
            ->willReturn($repository);
        $repository->expects(self::once())
            ->method('countUserShoppingLists')
            ->with(self::USER_ID, self::ORGANIZATION_ID, $website)
            ->willReturn($count);
    }

    public function testIsReachedLimitForNotLoggedUser(): void
    {
        $this->expectsGetUser(null);

        self::assertTrue($this->shoppingListLimitManager->isReachedLimit());
    }

    /**
     * @dataProvider limitDataProvider
     */
    public function testIsReachedLimit(int $limit, int $count, bool $expected): void
    {
        $user = $this->getCustomerUser();

        $this->expectsGetUser($user);

        $this->configManager->expects(self::once())
            ->method('get')
            ->with('oro_shopping_list.shopping_list_limit')
            ->willReturn($limit);

        if ($limit) {
            $this->expectsCountUserShoppingLists($user->getWebsite(), $count);
        }

        self::assertSame(!$expected, $this->shoppingListLimitManager->isReachedLimit());
    }

    public function testIsCreateEnabledForNotLoggedUser(): void
    {
        $this->expectsGetUser(null);

        self::assertFalse($this->shoppingListLimitManager->isCreateEnabled());
    }

    /**
     * @dataProvider limitDataProvider
     */
    public function testIsCreateEnabled(int $limit, int $count, bool $expected): void
    {
        $user = $this->getCustomerUser();

        $this->expectsGetUser($user, 2);

        $this->configManager->expects(self::once())
            ->method('get')
            ->with('oro_shopping_list.shopping_list_limit')
            ->willReturn($limit);

        if ($limit) {
            $this->expectsCountUserShoppingLists($user->getWebsite(), $count);
        }

        self::assertSame($expected, $this->shoppingListLimitManager->isCreateEnabled());
        // Check internal cache
        self::assertSame($expected, $this->shoppingListLimitManager->isCreateEnabled());
    }

    public function limitDataProvider(): array
    {
        return [
            'without limit' => [
                'limit' => 0,
                'count' => 5,
                'expected' => true
            ],
            'with not reached limit' => [
                'limit' => 5,
                'count' => 4,
                'expected' => true
            ],
            'with reached limit' => [
                'limit' => 5,
                'count' => 5,
                'expected' => false
            ]
        ];
    }

    public function testIsCreateEnabledForCustomerUserWhenLimitNotSet(): void
    {
        $user = $this->getCustomerUser();

        $this->configManager->expects(self::once())
            ->method('get')
            ->with('oro_shopping_list.shopping_list_limit', false, false, $user->getWebsite())
            ->willReturn(0);

        self::assertTrue($this->shoppingListLimitManager->isCreateEnabledForCustomerUser($user));
    }

    /**
     * @dataProvider isCreateEnabledForUserDataProvider
     */
    public function testIsCreateEnabledForCustomerUser(int $actualShoppingListCount, bool $result): void
    {
        $user = $this->getCustomerUser();
        $website = $user->getWebsite();

        $this->configManager->expects(self::once())
            ->method('get')
            ->with('oro_shopping_list.shopping_list_limit', false, false, $website)
            ->willReturn(5);

        $repository = $this->createMock(ShoppingListRepository::class);
        $this->doctrineHelper->expects(self::once())
            ->method('getEntityRepository')
            ->with(ShoppingList::class)
            ->willReturn($repository);
        $repository->expects(self::once())
            ->method('countUserShoppingLists')
            ->with(self::USER_ID, self::ORGANIZATION_ID)
            ->willReturn($actualShoppingListCount);

        self::assertSame($result, $this->shoppingListLimitManager->isCreateEnabledForCustomerUser($user));
        // Check internal cache
        self::assertSame($result, $this->shoppingListLimitManager->isCreateEnabledForCustomerUser($user));
    }

    public function isCreateEnabledForUserDataProvider(): array
    {
        return [
            'shopping list limit set' => [
                'actualShoppingListCount' => 4,
                'result' => true
            ],
            'shopping list limit reached' => [
                'actualShoppingListCount' => 5,
                'result' => false
            ]
        ];
    }

    public function testIsOnlyOneEnabledForNotLoggedUser(): void
    {
        $this->expectsGetUser(null);

        self::assertTrue($this->shoppingListLimitManager->isOnlyOneEnabled());
    }

    public function testIsOnlyOneEnabledWhenLimitNotSet(): void
    {
        $user = $this->getCustomerUser();

        $this->expectsGetUser($user);

        $this->configManager->expects(self::once())
            ->method('get')
            ->with('oro_shopping_list.shopping_list_limit')
            ->willReturn(0);

        self::assertFalse($this->shoppingListLimitManager->isOnlyOneEnabled());
    }

    public function testIsOnlyOneEnabled(): void
    {
        $user = $this->getCustomerUser();

        $this->expectsGetUser($user, 2);

        $this->configManager->expects(self::once())
            ->method('get')
            ->with('oro_shopping_list.shopping_list_limit')
            ->willReturn(1);

        $this->expectsCountUserShoppingLists($user->getWebsite(), 2);

        self::assertFalse($this->shoppingListLimitManager->isOnlyOneEnabled());
        // Check internal cache
        self::assertFalse($this->shoppingListLimitManager->isOnlyOneEnabled());
    }

    public function testIsOnlyOneEnabledWhenLimitAndNumberOfExistingShoppingListsEqualToOne(): void
    {
        $user = $this->getCustomerUser();

        $this->expectsGetUser($user);

        $this->configManager->expects(self::once())
            ->method('get')
            ->with('oro_shopping_list.shopping_list_limit')
            ->willReturn(1);

        $this->expectsCountUserShoppingLists($user->getWebsite(), 1);

        self::assertTrue($this->shoppingListLimitManager->isOnlyOneEnabled());
    }

    public function testIsOnlyOneEnabledWhenConfigReturnsString(): void
    {
        $user = $this->getCustomerUser();

        $this->expectsGetUser($user);

        $this->configManager->expects(self::once())
            ->method('get')
            ->with('oro_shopping_list.shopping_list_limit')
            ->willReturn('1');

        $this->expectsCountUserShoppingLists($user->getWebsite(), 1);

        self::assertTrue($this->shoppingListLimitManager->isOnlyOneEnabled());
    }

    public function testGetShoppingListLimitForUser(): void
    {
        $user = $this->getCustomerUser();

        $this->expectsGetUser($user);

        $this->configManager->expects(self::once())
            ->method('get')
            ->with('oro_shopping_list.shopping_list_limit')
            ->willReturn(2);

        self::assertSame(2, $this->shoppingListLimitManager->getShoppingListLimitForUser());
    }
}
