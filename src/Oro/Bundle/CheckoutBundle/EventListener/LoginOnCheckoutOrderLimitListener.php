<?php

namespace Oro\Bundle\CheckoutBundle\EventListener;

use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\CheckoutBundle\Entity\Checkout;
use Oro\Bundle\CheckoutBundle\Manager\CheckoutManager;
use Oro\Bundle\CheckoutBundle\Provider\CheckoutIdByTargetPathRequestProvider;
use Oro\Bundle\CheckoutBundle\Provider\OrderLimitProviderInterface;
use Oro\Bundle\ConfigBundle\Config\ConfigManager;
use Oro\Bundle\CustomerBundle\Entity\CustomerUser;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Component\Security\Http\Authenticator\InteractiveAuthenticatorInterface;
use Symfony\Component\Security\Http\Event\LoginSuccessEvent;
use Symfony\Contracts\EventDispatcher\Event;

/**
 * Listener redirects the user back to the shopping list page in case order limits are not met
 * (this situation may appear when user logs in on guest checkout, and prices are different for
 * guest and registered users)
 */
class LoginOnCheckoutOrderLimitListener
{
    private string $shoppingListRoute = 'oro_shopping_list_frontend_update';

    public function __construct(
        private ConfigManager $configManager,
        private CheckoutManager $checkoutManager,
        private RouterInterface $router,
        private CheckoutIdByTargetPathRequestProvider $checkoutIdByTargetPathRequestProvider,
        private OrderLimitProviderInterface $orderLimitProvider,
        private ManagerRegistry $registry
    ) {
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
        $sourceEntity = $checkout?->getSource()?->getEntity();
        if (!$sourceEntity) {
            return;
        }

        if (
            !$this->orderLimitProvider->isMinimumOrderAmountMet($sourceEntity)
            || !$this->orderLimitProvider->isMaximumOrderAmountMet($sourceEntity)
        ) {
            $event->stopPropagation();

            $event->setResponse(new RedirectResponse(
                $this->router->generate(
                    $this->shoppingListRoute,
                    ['id' => $sourceEntity->getId()]
                )
            ));

            $this->removeUnusedCheckout($checkout);
        }
    }

    private function isApplicable(LoginSuccessEvent $event): bool
    {
        return $this->configManager->get('oro_checkout.guest_checkout') &&
            $event->getAuthenticator() instanceof InteractiveAuthenticatorInterface &&
            $event->getAuthenticator()->isInteractive() &&
            $event->getAuthenticatedToken()->getUser() instanceof CustomerUser;
    }

    private function getGuestCheckoutId(Event $event): ?int
    {
        $checkoutId = $event->getRequest()->request->get('_checkout_id');
        if (!$checkoutId) {
            $checkoutId = $this->checkoutIdByTargetPathRequestProvider->getCheckoutId($event->getRequest());
        }

        return $checkoutId;
    }

    private function removeUnusedCheckout(Checkout $checkout): void
    {
        $em = $this->registry->getManagerForClass(Checkout::class);

        $em->remove($checkout);
        $em->flush();
    }
}
