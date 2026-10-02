<?php

namespace Oro\Bundle\CheckoutBundle\EventListener;

use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\CheckoutBundle\Entity\Checkout;
use Oro\Bundle\CheckoutBundle\Event\LoginOnCheckoutEvent;
use Oro\Bundle\CheckoutBundle\Manager\CheckoutManager;
use Oro\Bundle\CheckoutBundle\Provider\CheckoutIdByTargetPathRequestProvider;
use Oro\Bundle\CheckoutBundle\Workflow\ActionGroup\StartShoppingListCheckoutInterface;
use Oro\Bundle\ConfigBundle\Config\ConfigManager;
use Oro\Bundle\CustomerBundle\Entity\CustomerUser;
use Oro\Bundle\CustomerBundle\Security\AnonymousCustomerUserAuthenticator;
use Oro\Bundle\ShoppingListBundle\Entity\ShoppingList;
use Oro\Bundle\ShoppingListBundle\Event\ShoppingListPostMergeEvent;
use Psr\Log\LoggerInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Component\Security\Http\Authenticator\InteractiveAuthenticatorInterface;
use Symfony\Component\Security\Http\Event\InteractiveLoginEvent;
use Symfony\Component\Security\Http\Event\LoginSuccessEvent;
use Symfony\Contracts\EventDispatcher\Event;

/**
 * InteractiveLoginListener executes before onCheckoutLogin method in this listener and merge or move ShoppingList,
 * following this logic, the checkout or shopping list can be removed for guest user,
 * it's why we keep $guestCheckoutId and $currentShoppingList.
 *
 * Assigns checkout to newly logged CustomerUser (registered from guest checkout).
 * Creates a new checkout during login for guest checkout.
 */
class LoginOnCheckoutListener
{
    private string $checkoutRoute = 'oro_checkout_frontend_checkout';

    private ?int $guestCheckoutId = null;

    private ?ShoppingList $currentShoppingList = null;

    public function __construct(
        private LoggerInterface $logger,
        private ConfigManager $configManager,
        private CheckoutManager $checkoutManager,
        private EventDispatcherInterface $eventDispatcher,
        private RouterInterface $router,
        private ManagerRegistry $registry,
        private CheckoutIdByTargetPathRequestProvider $checkoutIdByTargetPathRequestProvider,
        private StartShoppingListCheckoutInterface $startShoppingListCheckout,
    ) {
    }

    public function setCheckoutRoute(string $checkoutRoute): void
    {
        $this->checkoutRoute = $checkoutRoute;
    }

    public function onShoppingListPostMerge(ShoppingListPostMergeEvent $event): void
    {
        $this->currentShoppingList = $event->getCurrentShoppingList();
    }

    public function onInteractiveLogin(InteractiveLoginEvent $event): void
    {
        $user = $event->getAuthenticationToken()->getUser();
        if (!$user instanceof CustomerUser) {
            return;
        }

        if ($user->getLoginCount() <= 1) {
            $this->checkoutManager->reassignCustomerUser($user);
        }

        if (!$this->configManager->get('oro_checkout.guest_checkout')) {
            return;
        }

        $checkoutId = $this->getGuestCheckoutId($event);
        $checkout = $checkoutId ? $this->checkoutManager->getCheckoutById($checkoutId) : null;
        if (!$checkout || !$this->isCheckoutAdoptable($checkout, $user, $event->getRequest())) {
            if ($checkoutId) {
                $this->logger->warning(
                    'Wrong checkout id passed during login from checkout.',
                    ['checkoutId' => $checkoutId]
                );
            }

            return;
        }

        $this->guestCheckoutId = $checkout->getId();
        $this->checkoutManager->updateCheckoutCustomerUser($checkout, $user);
    }

    public function onCheckoutLogin(LoginSuccessEvent $event): void
    {
        if (!$this->isApplicable($event)) {
            return;
        }

        $checkoutId = $this->getGuestCheckoutId($event);
        if (!$checkoutId) {
            return;
        }

        $checkout = $this->checkoutManager->getCheckoutById($checkoutId);
        $sourceEntity = $this->getSourceByCheckout($checkout);
        if (!$sourceEntity) {
            return;
        }

        $em = $this->registry->getManager();
        $em->beginTransaction();
        try {
            $result = $this->startShoppingListCheckout->execute($sourceEntity, true);

            $this->dispatchLoginOnCheckoutEvent($result['checkout']);
            $event->setResponse(new RedirectResponse($this->getRedirectUrl($result)));

            if ($checkout) {
                $em->remove($checkout);
                $em->flush();
            }

            $em->commit();
        } catch (\Exception $e) {
            $em->rollback();

            $this->logger->error('Starting a guest checkout is not allowed after a user logs in.', ['exception' => $e]);
        } finally {
            $this->guestCheckoutId = null;
            $this->currentShoppingList = null;
        }
    }

    private function isApplicable(LoginSuccessEvent $event): bool
    {
        return $this->configManager->get('oro_checkout.guest_checkout') &&
            $event->getAuthenticator() instanceof InteractiveAuthenticatorInterface &&
            $event->getAuthenticator()->isInteractive() &&
            $event->getAuthenticatedToken()->getUser() instanceof CustomerUser;
    }

    private function getSourceByCheckout(?Checkout $checkout): ?object
    {
        $sourceEntity = null;
        if ($checkout?->getId() === (int)$this->guestCheckoutId) {
            $sourceEntity = $checkout->getSource()?->getEntity();
        }

        if (!$checkout && $this->currentShoppingList) {
            $sourceEntity = $this->currentShoppingList;
        }

        return $sourceEntity;
    }

    /**
     * A checkout the authenticating customer user already owns is continued as is; any other checkout is
     * adopted only when it is a guest checkout of the current visitor.
     */
    private function isCheckoutAdoptable(Checkout $checkout, CustomerUser $customerUser, Request $request): bool
    {
        $checkoutCustomerUser = $checkout->getCustomerUser();
        if (null === $checkoutCustomerUser) {
            if (null !== $checkout->getCustomer()) {
                return false;
            }
        } elseif (
            null !== $checkoutCustomerUser->getId()
            && $checkoutCustomerUser->getId() === $customerUser->getId()
        ) {
            return true;
        } elseif (!$checkoutCustomerUser->isGuest()) {
            return false;
        }

        $visitorSessionId = $checkout->getVisitor()?->getSessionId();

        return $visitorSessionId && $visitorSessionId === $this->getVisitorSessionId($request);
    }

    private function getVisitorSessionId(Request $request): ?string
    {
        return $this->getVisitorSessionIdFromCookie($request)
            ?? $this->getVisitorSessionIdFromAttributes($request);
    }

    private function getVisitorSessionIdFromCookie(Request $request): ?string
    {
        $cookieValue = $request->cookies->get(AnonymousCustomerUserAuthenticator::COOKIE_NAME);
        if (!$cookieValue) {
            return null;
        }

        try {
            $sessionId = json_decode(base64_decode($cookieValue), null, 512, JSON_THROW_ON_ERROR);
        } catch (\JsonException) {
            return null;
        }

        return \is_string($sessionId) && $sessionId ? $sessionId : null;
    }

    private function getVisitorSessionIdFromAttributes(Request $request): ?string
    {
        $sessionId = $request->attributes->get('visitor_session_id');

        return \is_string($sessionId) && $sessionId ? $sessionId : null;
    }

    private function getGuestCheckoutId(Event $event): ?int
    {
        $checkoutId = $event->getRequest()->request->get('_checkout_id');
        if (!$checkoutId) {
            $checkoutId = $this->checkoutIdByTargetPathRequestProvider->getCheckoutId($event->getRequest());
        }

        return $checkoutId;
    }

    private function dispatchLoginOnCheckoutEvent(Checkout $checkout): void
    {
        if (!$this->eventDispatcher->hasListeners(LoginOnCheckoutEvent::NAME)) {
            return;
        }

        $event = new LoginOnCheckoutEvent();
        $event->setSource($checkout->getSource());
        $event->setCheckoutEntity($checkout);

        $this->eventDispatcher->dispatch($event, LoginOnCheckoutEvent::NAME);
    }

    private function getRedirectUrl(array $result): string
    {
        return $result['redirectUrl'] ?? $this->router->generate(
            $this->checkoutRoute,
            ['id' => $result['checkout']->getId()]
        );
    }
}
