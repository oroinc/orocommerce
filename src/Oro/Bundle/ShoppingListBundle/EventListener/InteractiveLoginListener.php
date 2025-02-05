<?php

namespace Oro\Bundle\ShoppingListBundle\EventListener;

use Oro\Bundle\ConfigBundle\Config\ConfigManager;
use Oro\Bundle\CustomerBundle\Entity\CustomerUser;
use Oro\Bundle\CustomerBundle\Entity\CustomerVisitor;
use Oro\Bundle\CustomerBundle\Entity\CustomerVisitorManager;
use Oro\Bundle\CustomerBundle\Security\AnonymousCustomerUserAuthenticator;
use Oro\Bundle\DataAuditBundle\EventListener\SendChangedEntitiesToMessageQueueListener;
use Oro\Bundle\ShoppingListBundle\Entity\ShoppingList;
use Oro\Bundle\ShoppingListBundle\Manager\GuestShoppingListMigrationManager;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Security\Http\Event\InteractiveLoginEvent;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * Listener to migrate guest shopping list during interactive login.
 */
class InteractiveLoginListener
{
    public function __construct(
        private CustomerVisitorManager $visitorManager,
        private GuestShoppingListMigrationManager $guestShoppingListMigrationManager,
        private LoggerInterface $logger,
        private ConfigManager $configManager,
        private SendChangedEntitiesToMessageQueueListener $sendChangedEntitiesToMessageQueueListener,
        private TranslatorInterface $translator
    ) {
    }

    public function onInteractiveLogin(InteractiveLoginEvent $event): void
    {
        try {
            if (!$this->isApplicable($event)) {
                return;
            }

            $visitor = $this->getVisitorFromRequest($event->getRequest());
            if (!$visitor) {
                return;
            }

            // Disable data audit 'cause it could fail the consumer
            $this->sendChangedEntitiesToMessageQueueListener->setEnabled(false);
            $user = $event->getAuthenticationToken()->getUser();

            /** @var ShoppingList $visitorShoppingList */
            $visitorShoppingList = $visitor->getShoppingLists()->first();
            if (!$visitorShoppingList) {
                return;
            }

            $operationCode = $this->guestShoppingListMigrationManager
                ->migrateGuestShoppingList($visitor, $user, $visitorShoppingList);

            if ($operationCode === GuestShoppingListMigrationManager::OPERATION_MERGE) {
                $this->addFlashMessage($event, 'notice', 'oro.shoppinglist.flash.merge');
            }
        } catch (\Exception $e) {
            $this->logger->error('Migration of the guest shopping list failed.', ['exception' => $e]);
        } finally {
            $this->sendChangedEntitiesToMessageQueueListener->setEnabled();
        }
    }

    private function isApplicable(InteractiveLoginEvent $event): bool
    {
        return $this->configManager->get('oro_shopping_list.availability_for_guests')
            && $event->getAuthenticationToken()->getUser() instanceof CustomerUser
            && $event->getRequest()->cookies->has(AnonymousCustomerUserAuthenticator::COOKIE_NAME);
    }

    private function getVisitorFromRequest(Request $request): ?CustomerVisitor
    {
        $value = $request->cookies->get(AnonymousCustomerUserAuthenticator::COOKIE_NAME);
        if (!$value) {
            return null;
        }

        $sessionId = json_decode(base64_decode($value));
        if (\is_array($sessionId) && isset($sessionId[1])) {
            // BC compatibility (can be removed in v7.0): get sessionId from old format of the cookie value
            $sessionId = $sessionId[1];
        }
        if (!\is_string($sessionId) || !$sessionId) {
            return null;
        }

        return $this->visitorManager->find($sessionId);
    }

    private function addFlashMessage(InteractiveLoginEvent $event, string $type, string $message): void
    {
        $event->getRequest()->getSession()->getFlashBag()->add($type, $this->translator->trans($message));
    }
}
