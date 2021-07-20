<?php

namespace Oro\Bundle\ShoppingListBundle\EventListener;

use Doctrine\Common\Collections\ArrayCollection;
use Oro\Bundle\ConfigBundle\Config\ConfigManager;
use Oro\Bundle\CustomerBundle\Entity\CustomerUser;
use Oro\Bundle\CustomerBundle\Entity\CustomerVisitorManager;
use Oro\Bundle\CustomerBundle\Security\Firewall\AnonymousCustomerUserAuthenticationListener;
use Oro\Bundle\DataAuditBundle\EventListener\SendChangedEntitiesToMessageQueueListener;
use Oro\Bundle\ShoppingListBundle\Entity\ShoppingList;
use Oro\Bundle\ShoppingListBundle\Manager\GuestShoppingListMigrationManager;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Security\Http\Event\InteractiveLoginEvent;

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

    public function __construct(
        CustomerVisitorManager $visitorManager,
        GuestShoppingListMigrationManager $guestShoppingListMigrationManager,
        LoggerInterface $logger,
        ConfigManager $configManager,
        SendChangedEntitiesToMessageQueueListener $sendChangedEntitiesToMessageQueueListener
    ) {
        $this->visitorManager                            = $visitorManager;
        $this->guestShoppingListMigrationManager         = $guestShoppingListMigrationManager;
        $this->logger                                    = $logger;
        $this->configManager                             = $configManager;
        $this->sendChangedEntitiesToMessageQueueListener = $sendChangedEntitiesToMessageQueueListener;
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
                        $this->guestShoppingListMigrationManager
                            ->migrateGuestShoppingList($visitor, $user, $visitorShoppingList);
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
        $value = $request->cookies->get(AnonymousCustomerUserAuthenticationListener::COOKIE_NAME);
        if (!$value) {
            return null;
        }
        list($visitorId, $sessionId) = json_decode(base64_decode($value));

        return [
            'visitor_id' => $visitorId,
            'session_id' => $sessionId,
        ];
    }
}
