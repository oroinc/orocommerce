<?php

namespace Oro\Bundle\ShoppingListBundle\Tests\Unit\Entity\EntityListener;

use Oro\Bundle\CustomerBundle\Entity\CustomerVisitor;
use Oro\Bundle\CustomerBundle\Security\Token\AnonymousCustomerUserToken;
use Oro\Bundle\PricingBundle\Manager\UserCurrencyManager;
use Oro\Bundle\SecurityBundle\Authentication\TokenAccessorInterface;
use Oro\Bundle\ShoppingListBundle\Entity\EntityListener\ShoppingListEntityListener;
use Oro\Bundle\ShoppingListBundle\Entity\ShoppingList;
use Oro\Bundle\ShoppingListBundle\Manager\ShoppingListLimitManager;
use Oro\Bundle\UserBundle\Entity\User;
use Oro\Bundle\UserBundle\Provider\DefaultUserProvider;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;

class ShoppingListEntityListenerTest extends \PHPUnit\Framework\TestCase
{
    private DefaultUserProvider $defaultUserProvider;
    private TokenAccessorInterface $tokenAccessor;
    private ShoppingListLimitManager $shoppingListLimitManager;
    private UserCurrencyManager $userCurrencyManager;
    private ShoppingListEntityListener $listener;

    #[\Override]
    protected function setUp(): void
    {
        $this->defaultUserProvider = $this->createMock(DefaultUserProvider::class);
        $this->tokenAccessor = $this->createMock(TokenAccessorInterface::class);
        $this->shoppingListLimitManager = $this->createMock(ShoppingListLimitManager::class);
        $this->userCurrencyManager = $this->createMock(UserCurrencyManager::class);

        $this->listener = new ShoppingListEntityListener(
            $this->defaultUserProvider,
            $this->tokenAccessor,
            $this->shoppingListLimitManager,
            $this->userCurrencyManager
        );
    }

    /**
     * @dataProvider persistNotSetDefaultOwnerDataProvider
     */
    public function testPrePersistNotSetDefaultOwner(?object $token, ShoppingList $shoppingList)
    {
        $this->tokenAccessor->expects(self::once())
            ->method('getToken')
            ->willReturn($token);

        $newUser = new User();
        $newUser->setFirstName('first_name');
        $this->listener->prePersist($shoppingList);
        self::assertNotSame($newUser, $shoppingList->getOwner());
    }

    public function persistNotSetDefaultOwnerDataProvider(): array
    {
        return [
            'with token and without owner' => [
                'token' => new AnonymousCustomerUserToken(new CustomerVisitor()),
                'shoppingList' => new ShoppingList()
            ],
            'without token and without owner' => [
                'token' => null,
                'shoppingList' => new ShoppingList()
            ],
            'unsupported token and without owner' => [
                'token' => $this->createMock(TokenInterface::class),
                'shoppingList' => new ShoppingList()
            ],
            'with owner' => [
                'token' => new AnonymousCustomerUserToken(new CustomerVisitor()),
                'shoppingList' => (new ShoppingList())->setOwner(new User())
            ]
        ];
    }

    public function testPrePersistSetDefaultOwnerAndCurrency()
    {
        $token = new AnonymousCustomerUserToken(new CustomerVisitor());
        $shoppingList = new ShoppingList();

        $currency = 'GBP';
        $this->userCurrencyManager->expects(self::once())
            ->method('getUserCurrency')
            ->willReturn($currency);

        $this->tokenAccessor->expects(self::once())
            ->method('getToken')
            ->willReturn($token);

        $newUser = new User();
        $newUser->setFirstName('first_name');
        $this->defaultUserProvider->expects(self::once())
            ->method('getDefaultUser')
            ->with('oro_shopping_list.default_guest_shopping_list_owner')
            ->willReturn($newUser);

        $this->listener->prePersist($shoppingList);
        self::assertSame($newUser, $shoppingList->getOwner());
        self::assertEquals($currency, $shoppingList->getCurrency());
    }

    public function testPostPersist()
    {
        $this->shoppingListLimitManager->expects(self::once())
            ->method('resetState');

        $this->listener->postPersist();
    }

    public function testPostRemove()
    {
        $this->shoppingListLimitManager->expects(self::once())
            ->method('resetState');

        $this->listener->postRemove();
    }
}
