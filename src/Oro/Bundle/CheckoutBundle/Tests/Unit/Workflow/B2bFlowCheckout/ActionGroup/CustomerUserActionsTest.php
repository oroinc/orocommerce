<?php

namespace Oro\Bundle\CheckoutBundle\Tests\Unit\Workflow\B2bFlowCheckout\ActionGroup;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\ActionBundle\Model\ActionExecutor;
use Oro\Bundle\AddressBundle\Entity\AbstractAddress;
use Oro\Bundle\CheckoutBundle\Entity\Checkout;
use Oro\Bundle\CheckoutBundle\Workflow\B2bFlowCheckout\ActionGroup\CustomerUserActions;
use Oro\Bundle\CustomerBundle\Entity\CustomerUser;
use Oro\Bundle\CustomerBundle\Entity\CustomerUserManager;
use Oro\Bundle\CustomerBundle\Entity\CustomerVisitor;
use Oro\Bundle\CustomerBundle\Entity\GuestCustomerUserManager;
use Oro\Bundle\CustomerBundle\Security\LoginManager;
use Oro\Bundle\CustomerBundle\Security\Token\AnonymousCustomerUserToken;
use Oro\Bundle\OrderBundle\Entity\Order;
use Oro\Bundle\ShoppingListBundle\Entity\ShoppingList;
use Oro\Bundle\ShoppingListBundle\Manager\GuestShoppingListMigrationManager;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;

class CustomerUserActionsTest extends TestCase
{
    private ManagerRegistry|MockObject $registry;
    private TokenStorageInterface|MockObject $tokenStorage;
    private GuestCustomerUserManager|MockObject $guestCustomerUserManager;
    private CustomerUserManager|MockObject $customerUserManager;
    private GuestShoppingListMigrationManager|MockObject $guestShoppingListMigrationManager;
    private LoginManager|MockObject $loginManager;
    private ActionExecutor|MockObject $actionExecutor;
    private CustomerUserActions $customerUserActions;

    #[\Override]
    protected function setUp(): void
    {
        $this->registry = $this->createMock(ManagerRegistry::class);
        $this->tokenStorage = $this->createMock(TokenStorageInterface::class);
        $this->guestCustomerUserManager = $this->createMock(GuestCustomerUserManager::class);
        $this->customerUserManager = $this->createMock(CustomerUserManager::class);
        $this->guestShoppingListMigrationManager = $this->createMock(GuestShoppingListMigrationManager::class);
        $this->loginManager = $this->createMock(LoginManager::class);
        $this->actionExecutor = $this->createMock(ActionExecutor::class);

        $this->customerUserActions = new CustomerUserActions(
            $this->registry,
            $this->tokenStorage,
            $this->guestCustomerUserManager,
            $this->customerUserManager,
            $this->guestShoppingListMigrationManager,
            $this->loginManager,
            $this->actionExecutor
        );
    }

    public function testCreateGuestCustomerUser(): void
    {
        $checkout = $this->createMock(Checkout::class);
        $billingAddress = $this->createMock(AbstractAddress::class);
        $visitor = $this->createMock(CustomerVisitor::class);
        $customerUser = $this->createMock(CustomerUser::class);
        $entityManager = $this->createMock(EntityManagerInterface::class);

        $checkout->expects($this->once())
            ->method('getCustomerUser')
            ->willReturn(null);

        $checkout->expects($this->once())
            ->method('getCustomer')
            ->willReturn(null);

        $this->setTokenWithVisitor($visitor);

        $this->guestCustomerUserManager->expects($this->once())
            ->method('createFromAddress')
            ->with('test@example.com', $billingAddress)
            ->willReturn($customerUser);

        $checkout->expects($this->once())
            ->method('setCustomerUser')
            ->with($customerUser);

        $visitor->expects($this->once())
            ->method('setCustomerUser')
            ->with($customerUser);

        $this->registry->expects($this->once())
            ->method('getManagerForClass')
            ->with(Checkout::class)
            ->willReturn($entityManager);

        $entityManager->expects($this->once())
            ->method('flush');

        $this->customerUserActions->createGuestCustomerUser($checkout, 'test@example.com', $billingAddress);
    }

    public function testCreateGuestCustomerUserSkipsIfCustomerUserExists(): void
    {
        $checkout = $this->createMock(Checkout::class);

        $checkout->expects($this->once())
            ->method('getCustomerUser')
            ->willReturn($this->createMock(CustomerUser::class));

        $checkout->expects($this->never())
            ->method('getCustomer');

        $this->tokenStorage->expects($this->never())
            ->method('getToken');

        $this->customerUserActions->createGuestCustomerUser(
            $checkout,
            'test@example.com',
            $this->createMock(AbstractAddress::class)
        );
    }

    public function testUpdateGuestCustomerUser(): void
    {
        $checkout = $this->createMock(Checkout::class);
        $billingAddress = $this->createMock(AbstractAddress::class);
        $customerUser = $this->createMock(CustomerUser::class);
        $entityManager = $this->createMock(EntityManagerInterface::class);

        $checkout->expects($this->any())
            ->method('getCustomerUser')
            ->willReturn($customerUser);

        $customerUser->expects($this->once())
            ->method('isGuest')
            ->willReturn(true);

        $this->guestCustomerUserManager->expects($this->once())
            ->method('updateFromAddress')
            ->with($customerUser, 'test@example.com', $billingAddress)
            ->willReturn($customerUser);

        $checkout->expects($this->once())
            ->method('setCustomerUser')
            ->with($customerUser);

        $this->registry->expects($this->once())
            ->method('getManagerForClass')
            ->with(Checkout::class)
            ->willReturn($entityManager);

        $entityManager->expects($this->once())
            ->method('flush');

        $this->customerUserActions->updateGuestCustomerUser($checkout, 'test@example.com', $billingAddress);
    }

    public function testUpdateGuestCustomerUserSkipsIfNoEmailOrBillingAddress(): void
    {
        $checkout = $this->createMock(Checkout::class);

        $checkout->expects($this->never())
            ->method('getCustomerUser');

        $this->guestCustomerUserManager->expects($this->never())
            ->method('updateFromAddress');

        $this->customerUserActions->updateGuestCustomerUser($checkout);
    }

    /**
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     */
    public function testHandleLateRegistration(): void
    {
        $order = $this->createMock(Order::class);
        $customerUser = $this->createMock(CustomerUser::class);
        $billingAddress = $this->createMock(AbstractAddress::class);
        $entityManager = $this->createMock(EntityManagerInterface::class);
        $visitor = $this->createMock(CustomerVisitor::class);

        $registeredCustomerUser = $this->createMock(CustomerUser::class);
        $registeredCustomerUser->expects($this->once())
            ->method('isConfirmed')
            ->willReturn(true);

        $sourceEntity = $this->createMock(ShoppingList::class);
        $sourceEntity->expects($this->once())
            ->method('getCustomerUser')
            ->willReturn($customerUser);

        $checkout = $this->createMock(Checkout::class);
        $checkout->expects($this->any())
            ->method('getSourceEntity')
            ->willReturn($sourceEntity);
        $checkout->expects($this->once())
            ->method('getCustomerUser')
            ->willReturn($customerUser);
        $checkout->expects($this->exactly(2))
            ->method('getRegisteredCustomerUser')
            ->willReturnOnConsecutiveCalls(
                null,
                $registeredCustomerUser
            );

        $order->expects($this->once())
            ->method('getBillingAddress')
            ->willReturn($billingAddress);

        $customerUser->expects($this->once())
            ->method('setEmail')
            ->with('test@example.com');

        $customerUser->expects($this->once())
            ->method('setFirstName')
            ->with($billingAddress->getFirstName());

        $customerUser->expects($this->once())
            ->method('setLastName')
            ->with($billingAddress->getLastName());

        $customerUser->expects($this->once())
            ->method('setPlainPassword')
            ->with('password');

        $customerUser->expects($this->once())
            ->method('setEnabled')
            ->with(true);

        $customerUser->expects($this->once())
            ->method('setIsGuest')
            ->with(false);

        $checkout->expects($this->once())
            ->method('setRegisteredCustomerUser')
            ->with($customerUser);

        $this->customerUserManager->expects($this->once())
            ->method('updateUser')
            ->with($customerUser);

        $this->customerUserManager->expects($this->once())
            ->method('register')
            ->with($customerUser);

        $order->expects($this->once())
            ->method('setCustomerUser')
            ->with($customerUser);

        $order->expects($this->once())
            ->method('setCustomer')
            ->with($customerUser->getCustomer());

        $this->registry->expects($this->once())
            ->method('getManagerForClass')
            ->with(Order::class)
            ->willReturn($entityManager);

        $entityManager->expects($this->once())
            ->method('flush')
            ->with($order);

        $this->setTokenWithVisitor($visitor);

        $this->guestShoppingListMigrationManager->expects($this->once())
            ->method('moveShoppingListToCustomerUser')
            ->with($visitor, $customerUser, $sourceEntity);

        $this->loginManager->expects($this->once())
            ->method('logInUser')
            ->with('frontend', $customerUser);

        $this->actionExecutor->expects($this->once())
            ->method('executeAction')
            ->with(
                'flash_message',
                [
                    'message' => 'oro.customer.controller.customeruser.registered.message',
                    'type' => 'success'
                ]
            );

        $result = $this->customerUserActions->handleLateRegistration(
            $checkout,
            $order,
            [
                'is_late_registration_enabled' => true,
                'email' => 'test@example.com',
                'password' => 'password'
            ]
        );

        $this->assertIsArray($result);
        $this->assertArrayHasKey('result', $result);
        $this->assertArrayHasKey('confirmationRequired', $result['result']);
        $this->assertFalse($result['result']['confirmationRequired']);
    }

    /**
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     */
    public function testHandleLateRegistrationConfirmationRequired(): void
    {
        $checkout = $this->createMock(Checkout::class);
        $order = $this->createMock(Order::class);
        $customerUser = $this->createMock(CustomerUser::class);
        $billingAddress = $this->createMock(AbstractAddress::class);

        $checkout->expects($this->once())
            ->method('getCustomerUser')
            ->willReturn($customerUser);

        $checkout->expects($this->any())
            ->method('getRegisteredCustomerUser')
            ->willReturn(null);

        $order->expects($this->once())
            ->method('getBillingAddress')
            ->willReturn($billingAddress);

        $customerUser->expects($this->once())
            ->method('setEmail')
            ->with('test@example.com');

        $customerUser->expects($this->once())
            ->method('setFirstName')
            ->with($billingAddress->getFirstName());

        $customerUser->expects($this->once())
            ->method('setLastName')
            ->with($billingAddress->getLastName());

        $customerUser->expects($this->once())
            ->method('setPlainPassword')
            ->with('password');

        $customerUser->expects($this->once())
            ->method('setEnabled')
            ->with(true);

        $customerUser->expects($this->once())
            ->method('setIsGuest')
            ->with(false);

        $checkout->expects($this->once())
            ->method('setRegisteredCustomerUser')
            ->with($customerUser);

        $this->customerUserManager->expects($this->once())
            ->method('updateUser')
            ->with($customerUser);

        $this->customerUserManager->expects($this->once())
            ->method('register')
            ->with($customerUser);

        $order->expects($this->once())
            ->method('setCustomerUser')
            ->with($customerUser);

        $order->expects($this->once())
            ->method('setCustomer')
            ->with($customerUser->getCustomer());

        $entityManager = $this->createMock(EntityManagerInterface::class);
        $this->registry->expects($this->once())
            ->method('getManagerForClass')
            ->with(Order::class)
            ->willReturn($entityManager);

        $entityManager->expects($this->once())
            ->method('flush')
            ->with($order);

        $checkout->expects($this->once())
            ->method('getSourceEntity')
            ->willReturn(null);

        $this->actionExecutor->expects($this->once())
            ->method('executeAction')
            ->with(
                'flash_message',
                [
                    'message' => 'oro.customer.controller.customeruser.registered_with_confirmation.message',
                    'type' => 'success'
                ]
            );

        $result = $this->customerUserActions->handleLateRegistration(
            $checkout,
            $order,
            [
                'is_late_registration_enabled' => true,
                'email' => 'test@example.com',
                'password' => 'password'
            ]
        );

        $this->assertIsArray($result);
        $this->assertArrayHasKey('result', $result);
        $this->assertArrayHasKey('confirmationRequired', $result['result']);
        $this->assertTrue($result['result']['confirmationRequired']);
    }

    private function setTokenWithVisitor(CustomerVisitor $visitor): void
    {
        $token = $this->createMock(AnonymousCustomerUserToken::class);
        $token->expects($this->once())
            ->method('getVisitor')
            ->willReturn($visitor);

        $this->tokenStorage->expects($this->once())
            ->method('getToken')
            ->willReturn($token);
    }
}
