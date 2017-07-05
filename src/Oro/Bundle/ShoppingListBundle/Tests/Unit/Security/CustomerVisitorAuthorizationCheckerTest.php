<?php

namespace Oro\Bundle\ShoppingListBundle\Tests\Unit\Security;

use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorage;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;

use Oro\Bundle\CustomerBundle\Entity\CustomerUser;
use Oro\Bundle\CustomerBundle\Security\Token\AnonymousCustomerUserToken;
use Oro\Bundle\ShoppingListBundle\Entity\LineItem;
use Oro\Bundle\ShoppingListBundle\Entity\ShoppingList;
use Oro\Bundle\ShoppingListBundle\Security\CustomerVisitorAuthorizationChecker;
use Oro\Bundle\ShoppingListBundle\Tests\Unit\Entity\Stub\CustomerVisitorStub;
use Oro\Component\Testing\Unit\EntityTrait;

class CustomerVisitorAuthorizationCheckerTest extends \PHPUnit_Framework_TestCase
{
    use EntityTrait;

    /**
     * @var CustomerVisitorAuthorizationChecker
     */
    private $customerVisitorAuthorizationChecker;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject|AuthorizationCheckerInterface
     */
    private $authorizationChecker;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject|TokenStorage
     */
    private $tokenStorage;

    protected function setUp()
    {
        $this->authorizationChecker = $this->createMock(AuthorizationCheckerInterface::class);
        $this->tokenStorage = $this->createMock(TokenStorage::class);

        $this->customerVisitorAuthorizationChecker = new CustomerVisitorAuthorizationChecker(
            $this->authorizationChecker, $this->tokenStorage
        );
    }

    public function testIsGrantedWithCustomerUser()
    {
        $customerUser = new CustomerUser();
        $shoppingList = new ShoppingList();
        $attribute = 'EDIT';

        $token = $this->createMock(TokenInterface::class);
        $token->expects($this->once())
            ->method('getUser')
            ->willReturn($customerUser);

        $this->tokenStorage->expects($this->once())
            ->method('getToken')
            ->willReturn($token);
        $this->authorizationChecker->expects($this->once())
            ->method('isGranted')
            ->with($attribute, $shoppingList)
            ->willReturn(true);

        $this->assertTrue($this->customerVisitorAuthorizationChecker->isGranted($attribute, $shoppingList));
    }

    public function testIsGrantedOwnShoppingList()
    {
        $shoppingList = new ShoppingList();
        $shoppingList->setLabel('My shopping List');

        $customerVisitor = new CustomerVisitorStub();
        $customerVisitor->addShoppingList($shoppingList);

        $token = new AnonymousCustomerUserToken('anon.');
        $token->setVisitor($customerVisitor);

        $this->tokenStorage->expects($this->any())
            ->method('getToken')
            ->willReturn($token);

        $this->authorizationChecker->expects($this->never())
            ->method('isGranted');

        $this->assertTrue($this->customerVisitorAuthorizationChecker->isGranted('VIEW', $shoppingList));
        $this->assertTrue($this->customerVisitorAuthorizationChecker->isGranted('EDIT', $shoppingList));
        $this->assertFalse($this->customerVisitorAuthorizationChecker->isGranted('DELETE', $shoppingList));
    }

    public function testIsGrantedOwnLineItem()
    {
        $lineItem = $this->getEntity(LineItem::class, ['id' => 43]);

        $shoppingList = new ShoppingList();
        $shoppingList->setLabel('My shopping List');
        $shoppingList->addLineItem($lineItem);

        $customerVisitor = new CustomerVisitorStub();
        $customerVisitor->addShoppingList($shoppingList);

        $token = new AnonymousCustomerUserToken('anon.');
        $token->setVisitor($customerVisitor);

        $this->tokenStorage->expects($this->any())
            ->method('getToken')
            ->willReturn($token);

        $this->authorizationChecker->expects($this->never())
            ->method('isGranted');

        $this->assertTrue($this->customerVisitorAuthorizationChecker->isGranted('VIEW', $lineItem));
        $this->assertTrue($this->customerVisitorAuthorizationChecker->isGranted('EDIT', $lineItem));
        $this->assertFalse($this->customerVisitorAuthorizationChecker->isGranted('DELETE', $lineItem));
    }

    public function testIsGrantedNotOwnShoppingList()
    {
        $lineItem = $this->getEntity(LineItem::class, ['id' => 43]);

        $shoppingList = new ShoppingList();
        $shoppingList->setLabel('My shopping List');
        $shoppingList->addLineItem($lineItem);

        $customerVisitor = new CustomerVisitorStub();
        $customerVisitor->addShoppingList($shoppingList);

        $token = new AnonymousCustomerUserToken('anon.');
        $token->setVisitor($customerVisitor);

        $this->tokenStorage->expects($this->any())
            ->method('getToken')
            ->willReturn($token);

        $this->authorizationChecker->expects($this->never())
            ->method('isGranted');

        $this->assertFalse($this->customerVisitorAuthorizationChecker->isGranted('VIEW', new ShoppingList()));
        $this->assertFalse($this->customerVisitorAuthorizationChecker->isGranted('EDIT', new ShoppingList()));
    }

    public function testIsGrantedNotOwnLineItem()
    {
        $lineItem = $this->getEntity(LineItem::class, ['id' => 43]);

        $shoppingList = new ShoppingList();
        $shoppingList->setLabel('My shopping List');
        $shoppingList->addLineItem($lineItem);

        $customerVisitor = new CustomerVisitorStub();
        $customerVisitor->addShoppingList($shoppingList);

        $token = new AnonymousCustomerUserToken('anon.');
        $token->setVisitor($customerVisitor);

        $this->tokenStorage->expects($this->any())
            ->method('getToken')
            ->willReturn($token);

        $this->authorizationChecker->expects($this->never())
            ->method('isGranted');

        $this->assertFalse($this->customerVisitorAuthorizationChecker->isGranted('VIEW', new LineItem()));
        $this->assertFalse($this->customerVisitorAuthorizationChecker->isGranted('EDIt', new LineItem()));
    }

    public function testIsGrantedOtherEntity()
    {
        $lineItem = $this->getEntity(LineItem::class, ['id' => 43]);

        $shoppingList = new ShoppingList();
        $shoppingList->setLabel('My shopping List');
        $shoppingList->addLineItem($lineItem);

        $customerVisitor = new CustomerVisitorStub();
        $customerVisitor->addShoppingList($shoppingList);

        $token = new AnonymousCustomerUserToken('anon.');
        $token->setVisitor($customerVisitor);

        $this->tokenStorage->expects($this->any())
            ->method('getToken')
            ->willReturn($token);

        $this->authorizationChecker->expects($this->never())
            ->method('isGranted');

        $this->assertFalse($this->customerVisitorAuthorizationChecker->isGranted('VIEW', new \stdClass()));
        $this->assertFalse($this->customerVisitorAuthorizationChecker->isGranted('EDIt', new \stdClass()));
    }
}
