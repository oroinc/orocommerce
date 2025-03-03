<?php

namespace Oro\Bundle\ShoppingListBundle\Tests\Unit\Manager;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityRepository;
use Oro\Bundle\CustomerBundle\Entity\CustomerVisitor;
use Oro\Bundle\CustomerBundle\Security\Token\AnonymousCustomerUserToken;
use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;
use Oro\Bundle\FeatureToggleBundle\Checker\FeatureChecker;
use Oro\Bundle\OrganizationBundle\Entity\Organization;
use Oro\Bundle\ShoppingListBundle\Entity\ShoppingList;
use Oro\Bundle\ShoppingListBundle\Manager\GuestShoppingListManager;
use Oro\Bundle\ShoppingListBundle\Tests\Unit\Entity\Stub\CustomerVisitorStub;
use Oro\Bundle\UserBundle\Entity\User;
use Oro\Bundle\WebsiteBundle\Entity\Website;
use Oro\Bundle\WebsiteBundle\Manager\WebsiteManager;
use Oro\Component\Testing\ReflectionUtil;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * @SuppressWarnings(PHPMD.TooManyPublicMethods)
 */
class GuestShoppingListManagerTest extends TestCase
{
    private DoctrineHelper&MockObject $doctrineHelper;
    private TokenStorageInterface&MockObject $tokenStorage;
    private WebsiteManager&MockObject $websiteManager;
    private TranslatorInterface&MockObject $translator;
    private FeatureChecker&MockObject $featureChecker;
    private GuestShoppingListManager $guestShoppingListManager;

    #[\Override]
    protected function setUp(): void
    {
        $this->doctrineHelper = $this->createMock(DoctrineHelper::class);
        $this->tokenStorage = $this->createMock(TokenStorageInterface::class);
        $this->websiteManager = $this->createMock(WebsiteManager::class);
        $this->translator = $this->createMock(TranslatorInterface::class);
        $this->featureChecker = $this->createMock(FeatureChecker::class);

        $this->guestShoppingListManager = new GuestShoppingListManager(
            $this->doctrineHelper,
            $this->tokenStorage,
            $this->websiteManager,
            $this->translator
        );
        $this->guestShoppingListManager->setFeatureChecker($this->featureChecker);
        $this->guestShoppingListManager->addFeature('guest_shopping_list');
    }

    private function getWebsite(int $id, Organization $organization): Website
    {
        $website = new Website();
        ReflectionUtil::setId($website, $id);
        $website->setOrganization($organization);

        return $website;
    }

    private function getShoppingList(?int $id, Website $website, Organization $organization): ShoppingList
    {
        $shoppingList = new ShoppingList();
        if (null !== $id) {
            ReflectionUtil::setId($shoppingList, $id);
        }
        $shoppingList->setWebsite($website);
        $shoppingList->setOrganization($organization);

        return $shoppingList;
    }

    public function testGetDefaultUserWithId(): void
    {
        $user = new User();

        $userRepository = $this->createMock(EntityRepository::class);
        $userRepository->expects(self::once())
            ->method('find')
            ->with(1)
            ->willReturn($user);

        $this->doctrineHelper->expects(self::once())
            ->method('getEntityRepository')
            ->with(User::class)
            ->willReturn($userRepository);

        self::assertSame($user, $this->guestShoppingListManager->getDefaultUser(1));
    }

    public function testGetDefaultUserWithNull(): void
    {
        $user = new User();

        $userRepository = $this->createMock(EntityRepository::class);
        $userRepository->expects(self::once())
            ->method('findOneBy')
            ->with([], ['id' => 'ASC'])
            ->willReturn($user);

        $this->doctrineHelper->expects(self::once())
            ->method('getEntityRepository')
            ->with(User::class)
            ->willReturn($userRepository);

        self::assertSame($user, $this->guestShoppingListManager->getDefaultUser(null));
    }

    /**
     * @dataProvider availableDataProvider
     */
    public function testIsGuestShoppingListAvailable(
        string $tokenClass,
        bool $isFeatureEnabled,
        bool $expectedResult
    ): void {
        $this->tokenStorage->expects(self::once())
            ->method('getToken')
            ->willReturn($this->createMock($tokenClass));

        $this->featureChecker->expects(self::any())
            ->method('isFeatureEnabled')
            ->with('guest_shopping_list')
            ->willReturn($isFeatureEnabled);

        self::assertEquals($expectedResult, $this->guestShoppingListManager->isGuestShoppingListAvailable());
    }

    /**
     * @dataProvider getGuestShoppingListsDataProvider
     */
    public function testGetShoppingListsForCustomerVisitor(
        Website $currentWebsite,
        CustomerVisitorStub $customerVisitor,
        array $expectedShoppingList
    ): void {
        $token = new AnonymousCustomerUserToken($customerVisitor, []);

        $this->websiteManager->expects(self::once())
            ->method('getCurrentWebsite')
            ->willReturn($currentWebsite);

        $this->tokenStorage->expects(self::once())
            ->method('getToken')
            ->willReturn($token);

        $em = $this->createMock(EntityManagerInterface::class);
        $this->doctrineHelper->expects(self::any())
            ->method('getEntityManager')
            ->willReturn($em);

        self::assertEquals(
            $expectedShoppingList,
            $this->guestShoppingListManager->getShoppingListsForCustomerVisitor()
        );
    }

    public function testGetShoppingListForCustomerVisitorInValidToken(): void
    {
        $this->tokenStorage->expects(self::once())
            ->method('getToken')
            ->willReturn($this->createMock(TokenInterface::class));

        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage(
            'Token should be instance of Oro\Bundle\CustomerBundle\Security\Token\AnonymousCustomerUserToken.'
        );

        $this->guestShoppingListManager->getShoppingListForCustomerVisitor();
    }

    public function testGetShoppingListForCustomerVisitorEmptyVisitor(): void
    {
        $token = new AnonymousCustomerUserToken(new CustomerVisitor());
        $this->tokenStorage->expects(self::once())
            ->method('getToken')
            ->willReturn($token);

        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage('Current website is empty.');

        $this->guestShoppingListManager->getShoppingListForCustomerVisitor();
    }

    public function testGetShoppingListForCustomerVisitorEmptyWebsite(): void
    {
        $token = new AnonymousCustomerUserToken(new CustomerVisitor());
        $this->tokenStorage->expects(self::once())
            ->method('getToken')
            ->willReturn($token);

        $this->websiteManager->expects(self::once())
            ->method('getCurrentWebsite')
            ->willReturn(null);

        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage('Current website is empty.');

        $this->guestShoppingListManager->getShoppingListForCustomerVisitor();
    }

    public function availableDataProvider(): array
    {
        return [
            'not valid token' => [
                'tokenClass' => TokenInterface::class,
                'isFeatureEnabled' => true,
                'expectedResult' => false
            ],
            'feature disabled' => [
                'tokenClass' => AnonymousCustomerUserToken::class,
                'isFeatureEnabled' => false,
                'expectedResult' => false
            ],
            'valid token and enabled feature' => [
                'tokenClass' => AnonymousCustomerUserToken::class,
                'isFeatureEnabled' => true,
                'expectedResult' => true
            ]
        ];
    }

    public function getGuestShoppingListsDataProvider(): array
    {
        $customerVisitorWithFewShoppingLists = new CustomerVisitorStub();

        $organization = new Organization();

        $website1 = $this->getWebsite(1, $organization);
        $website2 = $this->getWebsite(2, $organization);

        $shoppingList1 = $this->getShoppingList(25, $website1, $organization);
        $expectedShoppingList1 = $this->getShoppingList(25, $website1, $organization);
        $shoppingList2 = $this->getShoppingList(31, $website2, $organization);
        $expectedShoppingList2 = $this->getShoppingList(31, $website2, $organization);

        $customerVisitorWithFewShoppingLists->addShoppingList($shoppingList1);
        $customerVisitorWithFewShoppingLists->addShoppingList($shoppingList2);

        $customerVisitorWithOneShoppingList = new CustomerVisitorStub();
        $customerVisitorWithOneShoppingList->addShoppingList($shoppingList2);

        return [
            'customer visitor has few shopping list' => [
                'currentWebsite' => $website1,
                'customerVisitor' => $customerVisitorWithFewShoppingLists,
                'expectedShoppingList' => [$expectedShoppingList1->setCurrent(true)]
            ],
            'customer visitor has one shopping list' => [
                'currentWebsite' => $website2,
                'customerVisitor' => $customerVisitorWithOneShoppingList,
                'expectedShoppingList' => [$expectedShoppingList2->setCurrent(true)]
            ],
            'customer visitor does not have shopping list' => [
                'currentWebsiteId' => $website1,
                'customerVisitor' => new CustomerVisitorStub(),
                'expectedShoppingList' => []
            ]
        ];
    }

    /**
     * @dataProvider createGuestShoppingListDataProvider
     */
    public function testCreateAndGetShoppingListForCustomerVisitor(
        Website $currentWebsite,
        CustomerVisitorStub $customerVisitor,
        ShoppingList $expectedShoppingList
    ): void {
        $token = new AnonymousCustomerUserToken($customerVisitor);

        $this->websiteManager->expects(self::atLeastOnce())
            ->method('getCurrentWebsite')
            ->willReturn($currentWebsite);

        $this->tokenStorage->expects(self::atLeastOnce())
            ->method('getToken')
            ->willReturn($token);

        $this->translator->expects(self::any())
            ->method('trans')
            ->with('oro.shoppinglist.default.label')
            ->willReturn('Shopping List Label');

        $em = $this->createMock(EntityManagerInterface::class);
        $this->doctrineHelper->expects(self::any())
            ->method('getEntityManager')
            ->willReturn($em);
        $actual = $this->guestShoppingListManager->createAndGetShoppingListForCustomerVisitor();

        self::assertEquals($expectedShoppingList, $actual);
    }

    public function createGuestShoppingListDataProvider(): array
    {
        $customerVisitor = new CustomerVisitorStub();

        $organization = new Organization();
        $website = $this->getWebsite(1, $organization);

        $shoppingList1 = $this->getShoppingList(25, $website, $organization);
        $expectedShoppingList = $this->getShoppingList(25, $website, $organization);
        $expectedShoppingList->setCurrent(true);
        $shoppingList2 = $this->getShoppingList(31, $website, $organization);
        $expectedShoppingList2 = $this->getShoppingList(null, $website, $organization);
        $expectedShoppingList2->setCurrent(true);
        $expectedShoppingList2->setLabel('Shopping List Label');

        $customerVisitor->addShoppingList($shoppingList1);
        $customerVisitor->addShoppingList($shoppingList2);

        return [
            'shopping lists exist' => [
                'currentWebsite' => $website,
                'customerVisitor' => $customerVisitor,
                'expectedShoppingList' => $expectedShoppingList
            ],
            'shopping list doesn\'t exist' => [
                'currentWebsite' => $website,
                'customerVisitor' => new CustomerVisitorStub(),
                'expectedShoppingList' => $expectedShoppingList2
            ]
        ];
    }
}
