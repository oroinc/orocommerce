<?php

namespace Oro\Bundle\ShoppingListBundle\EventListener;

use Oro\Bundle\ConfigBundle\Config\ConfigManager;
use Oro\Bundle\CustomerBundle\Entity\CustomerUser;
use Oro\Bundle\CustomerBundle\Entity\CustomerVisitor;
use Oro\Bundle\CustomerBundle\Entity\CustomerVisitorManager;
use Oro\Bundle\CustomerBundle\Security\AnonymousCustomerUserAuthenticator;
use Oro\Bundle\DataAuditBundle\EventListener\SendChangedEntitiesToMessageQueueListener;
use Oro\Bundle\ShoppingListBundle\Manager\GuestShoppingListMigrationManager;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Security\Http\Event\InteractiveLoginEvent;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * Migrates guest-created shopping list to customer user during interactive login.
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

    /**
     * @SuppressWarnings(PHPMD.NPathComplexity)
     */
    public function onInteractiveLogin(InteractiveLoginEvent $event): void
    {
        $user = $event->getAuthenticationToken()->getUser();
        if (!$user instanceof CustomerUser) {
            return;
        }

        if (!$this->configManager->get('oro_shopping_list.availability_for_guests')) {
            return;
        }

        $isFlashMessageAllowed = false;
        $visitor = $this->getVisitorFromCookie($event->getRequest());
        if ($visitor) {
            $isFlashMessageAllowed = true;
        } else {
            $visitor = $this->getVisitorFromAttributes($event->getRequest());
        }
        if (!$visitor) {
            return;
        }

        $visitorShoppingList = $visitor->getShoppingLists()->first();
        if (!$visitorShoppingList) {
            return;
        }

        // Disable data audit 'cause it could fail the consumer
        $this->sendChangedEntitiesToMessageQueueListener->setEnabled(false);
        try {
            $operationCode = $this->guestShoppingListMigrationManager->migrateGuestShoppingList(
                $visitor,
                $user,
                $visitorShoppingList
            );
        } catch (\Exception $e) {
            $this->logger->error('Migration of the guest shopping list failed.', ['exception' => $e]);

            return;
        } finally {
            $this->sendChangedEntitiesToMessageQueueListener->setEnabled();
        }

        if ($isFlashMessageAllowed && GuestShoppingListMigrationManager::OPERATION_MERGE === $operationCode) {
            $this->addFlashMessage($event, 'notice', 'oro.shoppinglist.flash.merge');
        }
    }

    private function getVisitorFromCookie(Request $request): ?CustomerVisitor
    {
        $value = $request->cookies->get(AnonymousCustomerUserAuthenticator::COOKIE_NAME);
        if (!$value) {
            return null;
        }

        $sessionId = json_decode(base64_decode($value), null, 512, JSON_THROW_ON_ERROR);
        if (\is_array($sessionId) && isset($sessionId[1])) {
            // BC compatibility (can be removed in v7.0): get sessionId from old format of the cookie value
            $sessionId = $sessionId[1];
        }
        if (!\is_string($sessionId) || !$sessionId) {
            return null;
        }

        return $this->visitorManager->find($sessionId);
    }

    private function getVisitorFromAttributes(Request $request): ?CustomerVisitor
    {
        $sessionId = $request->attributes->get('visitor_session_id');
        if (!$sessionId) {
            return null;
        }

        return $this->visitorManager->find($sessionId);
    }

    private function addFlashMessage(InteractiveLoginEvent $event, string $type, string $message): void
    {
        $event->getRequest()->getSession()->getFlashBag()->add($type, $this->translator->trans($message));
    }
}
