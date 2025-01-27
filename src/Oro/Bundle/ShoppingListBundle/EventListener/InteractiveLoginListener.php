<?php

namespace Oro\Bundle\ShoppingListBundle\EventListener;

use Doctrine\Common\Collections\ArrayCollection;
use Oro\Bundle\ConfigBundle\Config\ConfigManager;
use Oro\Bundle\CustomerBundle\Entity\CustomerUser;
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
    /**
     * @var CustomerVisitorManager
     */
    private $visitorManager;

    /**
     * @var GuestShoppingListMigrationManager
     */
    private $guestShoppingListMigrationManager;

    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @var ConfigManager
     */
    private $configManager;

    /** @var SendChangedEntitiesToMessageQueueListener */
    private $sendChangedEntitiesToMessageQueueListener;

    private ?TranslatorInterface $translator = null;

    public function __construct(
        CustomerVisitorManager $visitorManager,
        GuestShoppingListMigrationManager $guestShoppingListMigrationManager,
        LoggerInterface $logger,
        ConfigManager $configManager,
        SendChangedEntitiesToMessageQueueListener $sendChangedEntitiesToMessageQueueListener
    ) {
        $this->visitorManager = $visitorManager;
        $this->guestShoppingListMigrationManager = $guestShoppingListMigrationManager;
        $this->logger = $logger;
        $this->configManager = $configManager;
        $this->sendChangedEntitiesToMessageQueueListener = $sendChangedEntitiesToMessageQueueListener;
    }

    public function setTranslator(TranslatorInterface $translator): self
    {
        $this->translator = $translator;

        return $this;
    }

    public function onInteractiveLogin(InteractiveLoginEvent $event)
    {
        $user = $event->getAuthenticationToken()->getUser();

        if (!$user instanceof CustomerUser || !$this->configManager->get('oro_shopping_list.availability_for_guests')) {
            return;
        }

        try {
            $credentials = $this->getCredentials($event->getRequest());
            if ($credentials) {
                $visitor = $this->visitorManager->find($credentials['visitor_id'], $credentials['session_id']);
                if ($visitor) {
                    // Disable data audit 'cause it could fail the consumer
                    $this->sendChangedEntitiesToMessageQueueListener->setEnabled(false);

                    /** @var ArrayCollection $shoppingLists */
                    $shoppingLists = $visitor->getShoppingLists();
                    /** @var ShoppingList $visitorShoppingList */
                    $visitorShoppingList = $shoppingLists->first();
                    if ($visitorShoppingList) {
                        $operationCode = $this->guestShoppingListMigrationManager
                            ->migrateGuestShoppingListWithOperationCode($visitorShoppingList);

                        if ($operationCode === GuestShoppingListMigrationManager::OPERATION_MERGE) {
                            $this->addFlashMessage($event, 'notice', 'oro.shoppinglist.flash.merge');
                        }
                    }
                }
            }
        } catch (\Exception $e) {
            $this->logger->error('Migration of the guest shopping list failed.', ['exception' => $e]);
        } finally {
            $this->sendChangedEntitiesToMessageQueueListener->setEnabled();
        }
    }

    /**
     * @param Request $request
     *
     * @return array
     */
    private function getCredentials(Request $request)
    {
        $value = $request->cookies->get(AnonymousCustomerUserAuthenticator::COOKIE_NAME);
        if (!$value) {
            return null;
        }
        [$visitorId, $sessionId] = json_decode(base64_decode($value));

        return [
            'visitor_id' => $visitorId,
            'session_id' => $sessionId,
        ];
    }

    private function addFlashMessage(InteractiveLoginEvent $event, string $type, string $message): void
    {
        $event->getRequest()->getSession()->getFlashBag()->add($type, $this->translator->trans($message));
    }
}
