<?php

namespace Oro\Bundle\CheckoutBundle\EventListener;

use Oro\Bundle\ActionBundle\Model\ActionData;
use Oro\Bundle\ActionBundle\Model\ActionGroupRegistry;
use Oro\Bundle\CheckoutBundle\Provider\ShoppingListCheckoutProvider;
use Oro\Bundle\CustomerBundle\Entity\CustomerUser;
use Oro\Bundle\FeatureToggleBundle\Checker\FeatureCheckerHolderTrait;
use Oro\Bundle\ShoppingListBundle\Entity\ShoppingList;
use Oro\Bundle\ShoppingListBundle\Event\ShoppingListEventPostTransfer;
use Oro\Bundle\WebsiteBundle\Manager\WebsiteManager;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerAwareTrait;
use Symfony\Component\Security\Http\Event\InteractiveLoginEvent;

/**
 * Listener to reset checkout when the shopping list source changes after login.
 *
 * This listener ensures that, upon user login, the checkout process is updated
 * to reflect the current shopping list if the source shopping list has changed.
 */
class ResetCheckoutOnSourceChange implements LoggerAwareInterface
{
    use FeatureCheckerHolderTrait;
    use LoggerAwareTrait;

    private ?ShoppingList $visitorShoppingList = null;
    private ?ShoppingList $currentShoppingList = null;

    public function __construct(
        private ShoppingListCheckoutProvider $checkoutProvider,
        private ActionGroupRegistry $actionGroupRegistry,
        private WebsiteManager $websiteManager,
    ) {
    }

    public function onShoppingListPostTransfer(ShoppingListEventPostTransfer $event): self
    {
        $this->visitorShoppingList = $event->getShoppingList();
        $this->currentShoppingList = $event->getCurrentShoppingList();

        return $this;
    }

    public function onInteractiveLogin(InteractiveLoginEvent $event): void
    {
        if ($this->shouldSkipProcessing($event->getAuthenticationToken()->getUser())) {
            return;
        }

        $website = $this->websiteManager->getCurrentWebsite();
        $checkout = $this->checkoutProvider->getCheckout($this->currentShoppingList);
        if ($checkout) {
            $actionData = new ActionData([
                'checkout' => $checkout,
                'currentWebsite' => $website,
                'sourceCriteria' => ['shoppingList' => $this->currentShoppingList]
            ]);
            try {
                $this->actionGroupRegistry->findByName('actualize_checkout')?->execute($actionData);
            } catch (\Exception $exception) {
                $this->logger?->error('An error occurred while resetting the checkout:' . $exception->getMessage());
            }
        }
    }

    private function shouldSkipProcessing(mixed $user): bool
    {
        return $this->isFeaturesEnabled()
            || !$user instanceof CustomerUser
            || !$this->currentShoppingList
            || !$this->visitorShoppingList
            || $this->visitorShoppingList === $this->currentShoppingList;
    }
}
