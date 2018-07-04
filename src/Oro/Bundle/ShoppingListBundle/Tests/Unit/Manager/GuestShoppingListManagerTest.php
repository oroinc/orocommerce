<?php

namespace Oro\Bundle\ShoppingListBundle\Tests\Unit\Manager;

use Doctrine\ORM\EntityManager;
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
use Oro\Component\Testing\Unit\EntityTrait;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Translation\TranslatorInterface;

class GuestShoppingListManagerTest extends \PHPUnit\Framework\TestCase
{
    use EntityTrait;

    /** @var GuestShoppingListManager */
    private $guestShoppingListManager;

    /** @var DoctrineHelper|\PHPUnit\Framework\MockObject\MockObject */
    private $doctrineHelper;

    /** @var TokenStorageInterface|\PHPUnit\Framework\MockObject\MockObject */
    private $tokenStorage;

    /** @var WebsiteManager|\PHPUnit\Framework\MockObject\MockObject */
    private $websiteManager;

    /** @var TranslatorInterface|\PHPUnit\Framework\MockObject\MockObject */
    private $translator;

    /** @var FeatureChecker|\PHPUnit\Framework\MockObject\MockObject */
    private $featureChecker;

    /**
     * {@inheritdoc}
     */
    protected function setUp()
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

    public function testGetDefaultUserWithId()
    {
        $user = new User();

        $userRepository = $this->createMock(EntityRepository::class);
        $userRepository->expects($this->once())
            ->method('find')
            ->with(1)
            ->willReturn($user);

        $this->doctrineHelper->expects($this->once())
            ->method('getEntityRepository')
            ->with(User::class)
            ->willReturn($userRepository);

        $this->assertSame($user, $this->guestShoppingListManager->getDefaultUser(1));
    }

    public function testGetDefaultUserWithNull()
    {
        $user = new User();

        $userRepository = $this->getMockBuilder(EntityRepository::class)
            ->disableOriginalConstructor()
            ->getMock();

        $userRepository->expects($this->once())
            ->method('findOneBy')
            ->with([], ['id' => 'ASC'])
            ->willReturn($user);

        $this->doctrineHelper->expects($this->once())
            ->method('getEntityRepository')
            ->with(User::class)
            ->willReturn($userRepository);

        $this->assertSame($user, $this->guestShoppingListManager->getDefaultUser(null));
    }

    /**
     * @dataProvider availableDataProvider
     *
     * @param string $tokenClass
     * @param bool $isFeatureEnabled
     * @param bool $expectedResult
     */
    public function testIsGuestShoppingListAvailable($tokenClass, $isFeatureEnabled, $expectedResult)
    {
        $this->tokenStorage
            ->expects($this->once())
            ->method('getToken')
            ->willReturn($this->createMock($tokenClass));

        $this->featureChecker
            ->expects($this->any())
            ->method('isFeatureEnabled')
            ->with('guest_shopping_list')
            ->willReturn($isFeatureEnabled);

        $this->assertEquals($expectedResult, $this->guestShoppingListManager->isGuestShoppingListAvailable());
    }

    /**
     * @dataProvider guestShoppingListDataProvider
     *
     * @param int $currentWebsiteId
     * @param array $shoppingListsData
     * @param array $expetedShoppingListData
     */
    public function testGetShoppingListForCustomerVisitor(
        $currentWebsiteId,
        array $shoppingListsData,
        array $expetedShoppingListData
    ) {
        /** @var ShoppingList $expectedShoppingList */
        $expectedShoppingList = $this->getEntity(ShoppingList::class, [
            'id' => $expetedShoppingListData['id'],
            'label' => $expetedShoppingListData['label'],
            'website' => $this->getEntity(Website::class, [
                'id' => $expetedShoppingListData['websiteId'],
                'organization' => new Organization()
            ]),
            'current' => true,
            'organization' => new Organization(),
        ]);

        $customerVisitor = new CustomerVisitorStub();
        foreach ($shoppingListsData as $shoppingListData) {
            $customerVisitor->addShoppingList($this->getEntity(ShoppingList::class, [
                'id' => $shoppingListData['id'],
                'website' => $this->getEntity(Website::class, [
                    'id' => $shoppingListData['websiteId'],
                    'organization' => new Organization()
                ]),
                'organization' => new Organization()
            ]));
        }

        $currentWebsite = $this->getEntity(Website::class, [
            'id' => $currentWebsiteId,
            'organization' => new Organization()
        ]);
        $token = new AnonymousCustomerUserToken('', [], $customerVisitor);

        $this->websiteManager
            ->expects($this->once())
            ->method('getCurrentWebsite')
            ->willReturn($currentWebsite);

        $this->tokenStorage
            ->expects($this->once())
            ->method('getToken')
            ->willReturn($token);

        $this->translator
            ->expects($this->any())
            ->method('trans')
            ->with('oro.shoppinglist.default.label')
            ->willReturn('Shopping List Label');

        $em = $this->createMock(EntityManager::class);

        $this->doctrineHelper
            ->expects($this->any())
            ->method('getEntityManager')
            ->willReturn($em);

        $this->assertEquals(
            $expectedShoppingList,
            $this->guestShoppingListManager->getShoppingListForCustomerVisitor()
        );
    }

    public function testGetShoppingListForCustomerVisitorInValidToken()
    {
        $this->tokenStorage
            ->expects($this->once())
            ->method('getToken')
            ->willReturn(new \stdClass());

        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage(
            'Token should be instance of Oro\Bundle\CustomerBundle\Security\Token\AnonymousCustomerUserToken.'
        );

        $this->guestShoppingListManager->getShoppingListForCustomerVisitor();
    }

    public function testGetShoppingListForCustomerVisitorEmptyVisitor()
    {
        $token = new AnonymousCustomerUserToken('', [], null);
        $this->tokenStorage
            ->expects($this->once())
            ->method('getToken')
            ->willReturn($token);

        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage('Customer visitor is empty.');

        $this->guestShoppingListManager->getShoppingListForCustomerVisitor();
    }

    public function testGetShoppingListForCustomerVisitorEmptyWebsite()
    {
        $token = new AnonymousCustomerUserToken('', [], new CustomerVisitor());
        $this->tokenStorage
            ->expects($this->once())
            ->method('getToken')
            ->willReturn($token);

        $this->websiteManager
            ->expects($this->once())
            ->method('getCurrentWebsite')
            ->willReturn(null);

        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage('Current website is empty.');

        $this->guestShoppingListManager->getShoppingListForCustomerVisitor();
    }

    /**
     * @return array
     */
    public function availableDataProvider()
    {
        return [
            'not valid token' => [
                'tokenClass' => \stdClass::class,
                'isFeatureEnabled' => true,
                'expectedResult' => false,
            ],
            'feature disabled' => [
                'tokenClass' => AnonymousCustomerUserToken::class,
                'isFeatureEnabled' => false,
                'expectedResult' => false,
            ],
            'valid token and enabled feature' => [
                'tokenClass' => AnonymousCustomerUserToken::class,
                'isFeatureEnabled' => true,
                'expectedResult' => true,
            ],
        ];
    }

    /**
     * @return array
     */
    public function guestShoppingListDataProvider()
    {
        return [
            'customer visitor has few shopping list' => [
                'currentWebsiteId' => 1,
                'shoppingListsData' => [
                    [
                        'id' => 25,
                        'websiteId' => 1,
                    ],
                    [
                        'id' => 31,
                        'websiteId' => 2,
                    ],
                ],
                'expetedShoppingListData' => [
                    'id' => 25,
                    'label' => null,
                    'websiteId' => 1,
                ],
            ],
            'customer visitor has one shopping list' => [
                'currentWebsiteId' => 1,
                'shoppingListsData' => [
                    [
                        'id' => 25,
                        'websiteId' => 1,
                    ],
                ],
                'expetedShoppingListData' => [
                    'id' => 25,
                    'label' => null,
                    'websiteId' => 1,
                ],
            ],

            'customer visitor does not have shopping list' => [
                'currentWebsiteId' => 1,
                'shoppingListsData' => [],
                'expetedShoppingListData' => [
                    'id' => null,
                    'label' => 'Shopping List Label',
                    'websiteId' => 1,
                ],
            ],
        ];
    }
}
