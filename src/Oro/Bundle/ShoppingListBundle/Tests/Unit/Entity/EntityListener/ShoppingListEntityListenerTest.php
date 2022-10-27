<?php

namespace Oro\Bundle\ShoppingListBundle\Tests\Unit\Entity\EntityListener;

use Oro\Bundle\CustomerBundle\Security\Token\AnonymousCustomerUserToken;
use Oro\Bundle\SecurityBundle\Authentication\TokenAccessorInterface;
use Oro\Bundle\ShoppingListBundle\Entity\EntityListener\ShoppingListEntityListener;
use Oro\Bundle\ShoppingListBundle\Entity\ShoppingList;
use Oro\Bundle\ShoppingListBundle\Manager\ShoppingListLimitManager;
use Oro\Bundle\UserBundle\Entity\User;
use Oro\Bundle\UserBundle\Provider\DefaultUserProvider;

class ShoppingListEntityListenerTest extends \PHPUnit\Framework\TestCase
{
    /** @var DefaultUserProvider|\PHPUnit\Framework\MockObject\MockObject */
    private $defaultUserProvider;

    /** @var TokenAccessorInterface|\PHPUnit\Framework\MockObject\MockObject */
    private $tokenAccessor;

    /** @var ShoppingListLimitManager|\PHPUnit\Framework\MockObject\MockObject */
    private $shoppingListLimitManager;

    /** @var ShoppingListEntityListener */
    private $listener;

    protected function setUp(): void
    {
        $this->defaultUserProvider = $this->createMock(DefaultUserProvider::class);
        $this->tokenAccessor = $this->createMock(TokenAccessorInterface::class);
        $this->shoppingListLimitManager = $this->createMock(ShoppingListLimitManager::class);

        $this->listener = new ShoppingListEntityListener(
            $this->defaultUserProvider,
            $this->tokenAccessor,
            $this->shoppingListLimitManager
        );
    }

    /**
     * @dataProvider persistNotSetDefaultOwnerDataProvider
     */
    public function testPrePersistNotSetDefaultOwner(?object $token, ShoppingList $shoppingList)
    {
        $this->tokenAccessor->expects($this->once())
            ->method('getToken')
            ->willReturn($token);

        $newUser = new User();
        $newUser->setFirstName('first_name');
        $this->listener->prePersist($shoppingList);
        $this->assertNotSame($newUser, $shoppingList->getOwner());
    }

    public function persistNotSetDefaultOwnerDataProvider(): array
    {
        return [
            'with token and without owner' => [
                'token' => new AnonymousCustomerUserToken(''),
                'shoppingList' => new ShoppingList()
            ],
            'without token and without owner' => [
                'token' => null,
                'shoppingList' => new ShoppingList()
            ],
            'unsupported token and without owner' => [
                'token' => new \stdClass(),
                'shoppingList' => new ShoppingList()
            ],
            'with owner' => [
                'token' => new AnonymousCustomerUserToken(''),
                'shoppingList' => (new ShoppingList())->setOwner(new User())
            ]
        ];
    }

    public function testPrePersistSetDefaultOwner()
    {
        $token = new AnonymousCustomerUserToken('');
        $shoppingList = new ShoppingList();

        $this->tokenAccessor->expects($this->once())
            ->method('getToken')
            ->willReturn($token);

        $newUser = new User();
        $newUser->setFirstName('first_name');
        $this->defaultUserProvider->expects($this->once())
            ->method('getDefaultUser')
            ->with('oro_shopping_list.default_guest_shopping_list_owner')
            ->willReturn($newUser);

        $this->listener->prePersist($shoppingList);
        $this->assertSame($newUser, $shoppingList->getOwner());
    }

    public function testPostPersist()
    {
        $this->shoppingListLimitManager->expects($this->once())
            ->method('resetState');

        $this->listener->postPersist();
    }

    public function testPostRemove()
    {
        $this->shoppingListLimitManager->expects($this->once())
            ->method('resetState');

        $this->listener->postRemove();
    }
}
