<?php

namespace Oro\Bundle\ShoppingListBundle\Tests\Unit\Manager;

use Oro\Bundle\ConfigBundle\Config\ConfigManager;
use Oro\Bundle\CustomerBundle\Entity\CustomerUser;
use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;
use Oro\Bundle\OrganizationBundle\Entity\Organization;
use Oro\Bundle\SecurityBundle\Authentication\TokenAccessor;
use Oro\Bundle\ShoppingListBundle\Entity\Repository\ShoppingListRepository;
use Oro\Bundle\ShoppingListBundle\Entity\ShoppingList;
use Oro\Bundle\ShoppingListBundle\Manager\ShoppingListLimitManager;
use Oro\Bundle\UserBundle\Entity\User;
use Oro\Bundle\WebsiteBundle\Entity\Website;
use Oro\Bundle\WebsiteBundle\Manager\WebsiteManager;
use Oro\Component\Testing\Unit\EntityTrait;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;

/**
 * @SuppressWarnings(PHPMD.TooManyPublicMethods)
 */
class ShoppingListLimitManagerTest extends \PHPUnit\Framework\TestCase
{
    const USER_ID = 777;
    const ORGANIZATION_ID = 555;
    const WEBSITE_ID = 888;

    use EntityTrait;

    /** @var ShoppingListLimitManager */
    private $shoppingListLimitManager;

    /** @var ConfigManager|\PHPUnit\Framework\MockObject\MockObject */
    private $configManager;

    /** @var DoctrineHelper|\PHPUnit\Framework\MockObject\MockObject */
    private $doctrineHelper;

    /** @var TokenAccessor|\PHPUnit\Framework\MockObject\MockObject */
    private $tokenAccessor;

    /** @var TokenInterface|\PHPUnit\Framework\MockObject\MockObject */
    private $token;

    /** @var WebsiteManager|\PHPUnit\Framework\MockObject\MockObject */
    private $websiteManager;

    /**
     * {@inheritdoc}
     */
    protected function setUp(): void
    {
        $this->configManager = $this->getMockBuilder(ConfigManager::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->doctrineHelper = $this->getMockBuilder(DoctrineHelper::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->tokenAccessor = $this->getMockBuilder(TokenAccessor::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->token = $this->getMockBuilder(TokenInterface::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->websiteManager = $this->getMockBuilder(WebsiteManager::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->shoppingListLimitManager = new ShoppingListLimitManager(
            $this->configManager,
            $this->tokenAccessor,
            $this->doctrineHelper,
            $this->websiteManager
        );
    }

    public function testIsReachedNotLogged()
    {
        $this->tokenAccessor
            ->expects($this->once())
            ->method('hasUser')
            ->willReturn(false);

        $this->assertEquals(true, $this->shoppingListLimitManager->isReachedLimit());
    }

    /**
     * @dataProvider limitDataProvider
     *
     * @param int $limit
     * @param int $count
     * @param bool $expected
     */
    public function testIsReached($limit, $count, $expected)
    {
        $this->tokenAccessor
            ->expects($this->once())
            ->method('hasUser')
            ->willReturn(true);

        $this->configManager
            ->expects($this->once())
            ->method('get')
            ->with('oro_shopping_list.shopping_list_limit')
            ->willReturn($limit);

        if ($limit) {
            $this->configureCount($count);
        }

        $this->assertEquals(!$expected, $this->shoppingListLimitManager->isReachedLimit());
    }

    public function testIsCreateEnabledNotLogged()
    {
        $this->tokenAccessor
            ->expects($this->once())
            ->method('hasUser')
            ->willReturn(false);

        $this->assertEquals(false, $this->shoppingListLimitManager->isCreateEnabled());
    }

    /**
     * @dataProvider limitDataProvider
     *
     * @param int $limit
     * @param int $count
     * @param bool $expected
     */
    public function testIsCreateEnabled($limit, $count, $expected)
    {
        $this->tokenAccessor
            ->expects($this->atLeastOnce())
            ->method('hasUser')
            ->willReturn(true);

        $this->configManager
            ->expects($this->once())
            ->method('get')
            ->with('oro_shopping_list.shopping_list_limit')
            ->willReturn($limit);

        if ($limit) {
            $this->configureCount($count);
        }

        $this->assertEquals($expected, $this->shoppingListLimitManager->isCreateEnabled());
        // Check internal cache
        $this->assertEquals($expected, $this->shoppingListLimitManager->isCreateEnabled());
    }

    /**
     * @return array
     */
    public function limitDataProvider()
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

    public function testIsCreateEnabledForUserNotLimitSet()
    {
        $user = $this->createMock(CustomerUser::class);

        $website = new Website();
        $user->expects($this->once())
            ->method('getWebsite')
            ->willReturn($website);

        $this->configManager
            ->expects($this->once())
            ->method('get')
            ->with('oro_shopping_list.shopping_list_limit', false, false, $website)
            ->willReturn(0);

        $this->assertEquals(true, $this->shoppingListLimitManager->isCreateEnabledForCustomerUser($user));
    }

    /**
     * @dataProvider isCreateEnabledForUserDataProvider
     * @param integer $actualShoppingListCount
     * @param bool    $result
     */
    public function testIsCreateEnabledForUser($actualShoppingListCount, $result)
    {
        $website = new Website();
        $user = $this->getEntity(
            CustomerUser::class,
            [
                'id' => self::USER_ID,
                'organization' => $this->getEntity(Organization::class, ['id' => self::ORGANIZATION_ID]),
                'website' => $website,
            ]
        );

        $this->configManager
            ->expects($this->once())
            ->method('get')
            ->with('oro_shopping_list.shopping_list_limit', false, false, $website)
            ->willReturn(5);

        $repository = $this->getMockBuilder(ShoppingListRepository::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->doctrineHelper
            ->expects($this->once())
            ->method('getEntityRepository')
            ->with(ShoppingList::class)
            ->willReturn($repository);

        $repository->expects($this->once())
            ->method('countUserShoppingLists')
            ->with(self::USER_ID, self::ORGANIZATION_ID)
            ->willReturn($actualShoppingListCount);

        $this->assertEquals($result, $this->shoppingListLimitManager->isCreateEnabledForCustomerUser($user));
        // Check internal cache
        $this->assertEquals($result, $this->shoppingListLimitManager->isCreateEnabledForCustomerUser($user));
    }

    /**
     * @return array
     */
    public function isCreateEnabledForUserDataProvider()
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

    public function testIsOnlyOneEnabledNotLoggedUser()
    {
        $this->tokenAccessor
            ->expects($this->once())
            ->method('hasUser')
            ->willReturn(false);

        $this->assertEquals(true, $this->shoppingListLimitManager->isOnlyOneEnabled());
    }

    public function testIsOnlyOneEnabledNotLimitSet()
    {
        $this->tokenAccessor
            ->expects($this->once())
            ->method('hasUser')
            ->willReturn(true);

        $this->configManager
            ->expects($this->once())
            ->method('get')
            ->with('oro_shopping_list.shopping_list_limit')
            ->willReturn(0);

        $this->assertEquals(false, $this->shoppingListLimitManager->isOnlyOneEnabled());
    }

    public function testIsOnlyOneEnabledLimitSet()
    {
        $this->tokenAccessor
            ->expects($this->atLeastOnce())
            ->method('hasUser')
            ->willReturn(true);

        $this->configManager
            ->expects($this->once())
            ->method('get')
            ->with('oro_shopping_list.shopping_list_limit')
            ->willReturn(1);

        $this->configureCount(2);

        $this->assertEquals(false, $this->shoppingListLimitManager->isOnlyOneEnabled());
        // Check internal cache
        $this->assertEquals(false, $this->shoppingListLimitManager->isOnlyOneEnabled());
    }

    public function testIsOnlyOneEnabledLimitAndListOne()
    {
        $this->tokenAccessor
            ->expects($this->once())
            ->method('hasUser')
            ->willReturn(true);

        $this->configManager
            ->expects($this->once())
            ->method('get')
            ->with('oro_shopping_list.shopping_list_limit')
            ->willReturn(1);

        $this->configureCount(1);

        $this->assertEquals(true, $this->shoppingListLimitManager->isOnlyOneEnabled());
    }

    public function testIsOnlyOneEnabledWhenConfigReturnsString(): void
    {
        $this->tokenAccessor
            ->expects($this->once())
            ->method('hasUser')
            ->willReturn(true);

        $this->configManager
            ->expects($this->once())
            ->method('get')
            ->with('oro_shopping_list.shopping_list_limit')
            ->willReturn('1');

        $this->configureCount(1);

        $this->assertEquals(true, $this->shoppingListLimitManager->isOnlyOneEnabled());
    }

    /**
     * @param int $count
     */
    private function configureCount($count)
    {
        $organization = $this->getEntity(Organization::class, ['id' => self::ORGANIZATION_ID]);
        /** @var User $user */
        $user = $this->getEntity(User::class, ['id' => self::USER_ID]);
        $user->setOrganization($organization);

        $this->tokenAccessor->expects($this->once())
            ->method('getUser')
            ->willReturn($user);

        $repository = $this->getMockBuilder(ShoppingListRepository::class)
            ->disableOriginalConstructor()
            ->getMock();

        $website = new Website();

        $this->websiteManager->expects($this->once())
            ->method('getCurrentWebsite')
            ->willReturn($website);

        $repository->expects($this->once())
            ->method('countUserShoppingLists')
            ->with(self::USER_ID, self::ORGANIZATION_ID, $website)
            ->willReturn($count);

        $this->doctrineHelper
            ->expects($this->once())
            ->method('getEntityRepository')
            ->with(ShoppingList::class)
            ->willReturn($repository);
    }

    public function testGetShoppingListLimitForUser()
    {
        $website = new Website();
        $user = $this->getEntity(
            CustomerUser::class,
            [
                'id' => self::USER_ID,
                'organization' => $this->getEntity(Organization::class, ['id' => self::ORGANIZATION_ID]),
                'website' => $website,
            ]
        );

        $this->tokenAccessor
            ->expects($this->once())
            ->method('hasUser')
            ->willReturn(true);

        $this->configManager
            ->expects($this->once())
            ->method('get')
            ->with('oro_shopping_list.shopping_list_limit')
            ->willReturn(2);

        $this->tokenAccessor->expects($this->once())
            ->method('getUser')
            ->willReturn($user);

        $this->assertEquals(2, $this->shoppingListLimitManager->getShoppingListLimitForUser());
    }
}
