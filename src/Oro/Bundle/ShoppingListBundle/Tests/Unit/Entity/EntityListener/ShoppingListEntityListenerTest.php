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

    /**
     * {@inheritdoc}
     */
    protected function setUp(): void
    {
        $this->defaultUserProvider = $this->createMock(DefaultUserProvider::class);
        $this->tokenAccessor = $this->createMock(TokenAccessorInterface::class);
        $this->shoppingListLimitManager = $this->createMock(ShoppingListLimitManager::class);

        $this->listener = new ShoppingListEntityListener(
            $this->defaultUserProvider,
            $this->tokenAccessor
        );

        $this->listener->setShoppingListLimitManager($this->shoppingListLimitManager);
    }

    /**
     * @dataProvider persistNotSetDefaultOwnerDataProvider
     *
     * @param string $token
     * @param ShoppingList $shoppingList
     */
    public function testPrePersistNotSetDefaultOwner($token, ShoppingList $shoppingList)
    {
        $this->tokenAccessor
            ->expects($this->once())
            ->method('getToken')
            ->willReturn($token);

        $newUser = new User();
        $newUser->setFirstName('first_name');
        $this->listener->prePersist($shoppingList);
        $this->assertNotSame($newUser, $shoppingList->getOwner());
    }

    /**
     * @return array
     */
    public function persistNotSetDefaultOwnerDataProvider()
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

    /**
     * @dataProvider persistSetDefaultOwnerDataProvider
     *
     * @param string $token
     * @param ShoppingList $shoppingList
     */
    public function testPrePersistSetDefaultOwner($token, ShoppingList $shoppingList)
    {
        $this->tokenAccessor
            ->expects($this->once())
            ->method('getToken')
            ->willReturn($token);

        $newUser = new User();
        $newUser->setFirstName('first_name');
        $this->defaultUserProvider
            ->expects($this->once())
            ->method('getDefaultUser')
            ->with('oro_shopping_list', 'default_guest_shopping_list_owner')
            ->willReturn($newUser);

        $this->listener->prePersist($shoppingList);
        $this->assertSame($newUser, $shoppingList->getOwner());
    }

    /**
     * @return array
     */
    public function persistSetDefaultOwnerDataProvider()
    {
        return [
            'with token and without owner' => [
                'token' => new AnonymousCustomerUserToken(''),
                'shoppingList' => new ShoppingList()
            ]
        ];
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
