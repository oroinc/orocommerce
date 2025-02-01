<?php

namespace Oro\Bundle\CheckoutBundle\Workflow\ActionGroup;

use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\ActionBundle\Model\ActionExecutor;
use Oro\Bundle\AddressBundle\Entity\AbstractAddress;
use Oro\Bundle\CheckoutBundle\Entity\Checkout;
use Oro\Bundle\CustomerBundle\Entity\CustomerUserManager;
use Oro\Bundle\CustomerBundle\Entity\CustomerVisitor;
use Oro\Bundle\CustomerBundle\Entity\GuestCustomerUserManager;
use Oro\Bundle\CustomerBundle\Security\LoginManager;
use Oro\Bundle\CustomerBundle\Security\Token\AnonymousCustomerUserToken;
use Oro\Bundle\OrderBundle\Entity\Order;
use Oro\Bundle\ShoppingListBundle\Entity\ShoppingList;
use Oro\Bundle\ShoppingListBundle\Manager\GuestShoppingListMigrationManager;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;

/**
 * Checkout workflow Customer User-related actions.
 */
class CustomerUserActions implements CustomerUserActionsInterface
{
    public function __construct(
        private ManagerRegistry $registry,
        private TokenStorageInterface $tokenStorage,
        private GuestCustomerUserManager $guestCustomerUserManager,
        private CustomerUserManager $customerUserManager,
        private GuestShoppingListMigrationManager $guestShoppingListMigrationManager,
        private LoginManager $loginManager,
        private ActionExecutor $actionExecutor
    ) {
    }

    #[\Override]
    public function createGuestCustomerUser(
        Checkout $checkout,
        ?string $email = null,
        ?AbstractAddress $billingAddress = null
    ): void {
        if ($checkout->getCustomerUser() || $checkout->getCustomer()) {
            return;
        }

        $visitor = $this->getActiveVisitor();
        if (!$visitor) {
            return;
        }

        $customerUser = $this->guestCustomerUserManager->createFromAddress(
            $email,
            $billingAddress
        );
        $checkout->setCustomerUser($customerUser);
        $visitor->setCustomerUser($customerUser);

        $em = $this->registry->getManagerForClass(Checkout::class);
        $em->flush();
    }

    #[\Override]
    public function updateGuestCustomerUser(
        Checkout $checkout,
        ?string $email = null,
        ?AbstractAddress $billingAddress = null
    ): void {
        if (!$email) {
            return;
        }

        if (!$billingAddress) {
            return;
        }

        if (!$checkout->getCustomerUser() || !$checkout->getCustomerUser()->isGuest()) {
            return;
        }

        $customerUser = $this->guestCustomerUserManager->updateFromAddress(
            $checkout->getCustomerUser(),
            $email,
            $billingAddress
        );
        $checkout->setCustomerUser($customerUser);

        $em = $this->registry->getManagerForClass(Checkout::class);
        $em->flush();
    }

    #[\Override]
    public function handleLateRegistration(Checkout $checkout, Order $order, ?array $lateRegistrationData = []): array
    {
        $result = [];
        $customerUser = $checkout->getCustomerUser();
        if (!empty($lateRegistrationData['is_late_registration_enabled'])
            && $customerUser
            && !$checkout->getRegisteredCustomerUser()
        ) {
            $billingAddress = $order->getBillingAddress();

            $registeredCustomerUser = $customerUser;
            $registeredCustomerUser->setEmail($lateRegistrationData['email']);
            $registeredCustomerUser->setFirstName($billingAddress?->getFirstName());
            $registeredCustomerUser->setLastName($billingAddress?->getLastName());
            $registeredCustomerUser->setPlainPassword($lateRegistrationData['password']);
            $registeredCustomerUser->setEnabled(true);
            $registeredCustomerUser->setIsGuest(false);

            $checkout->setRegisteredCustomerUser($registeredCustomerUser);

            $this->customerUserManager->updateUser($registeredCustomerUser);
            $this->customerUserManager->register($registeredCustomerUser);

            $order->setCustomerUser($registeredCustomerUser);
            $order->setCustomer($registeredCustomerUser->getCustomer());

            $em = $this->registry->getManagerForClass(Order::class);
            $em->flush($order);

            $sourceEntity = $checkout->getSourceEntity();
            if ($sourceEntity instanceof ShoppingList && $sourceEntity->getCustomerUser()) {
                $visitor = $this->getActiveVisitor();
                if ($visitor) {
                    $sourceEntity->setCustomerUser(null);
                    $this->guestShoppingListMigrationManager->moveShoppingListToCustomerUser(
                        $visitor,
                        $registeredCustomerUser,
                        $sourceEntity
                    );
                }
            }

            $result['result']['confirmationRequired'] = true;
        }

        $registeredCustomerUser = $checkout->getRegisteredCustomerUser();
        if ($registeredCustomerUser?->isConfirmed()) {
            $this->loginManager->logInUser('frontend', $registeredCustomerUser);
            $this->actionExecutor->executeAction(
                'flash_message',
                [
                    'message' => 'oro.customer.controller.customeruser.registered.message',
                    'type' => 'success'
                ]
            );
            $result['result']['confirmationRequired'] = false;
        } elseif (!empty($result['result']['confirmationRequired'])) {
            $this->actionExecutor->executeAction(
                'flash_message',
                [
                    'message' => 'oro.customer.controller.customeruser.registered_with_confirmation.message',
                    'type' => 'success'
                ]
            );
        }

        return $result;
    }

    private function getActiveVisitor(): ?CustomerVisitor
    {
        $token = $this->tokenStorage->getToken();
        if (!$token instanceof AnonymousCustomerUserToken) {
            return null;
        }

        return $token->getVisitor();
    }
}
