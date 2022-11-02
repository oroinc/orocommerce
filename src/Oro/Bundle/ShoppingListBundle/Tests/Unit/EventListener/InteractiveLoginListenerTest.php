<?php

namespace Oro\Bundle\ShoppingListBundle\Tests\Unit\EventListener;

use Oro\Bundle\ConfigBundle\Config\ConfigManager;
use Oro\Bundle\CustomerBundle\Entity\CustomerUser;
use Oro\Bundle\CustomerBundle\Entity\CustomerVisitorManager;
use Oro\Bundle\CustomerBundle\Security\Firewall\AnonymousCustomerUserAuthenticationListener;
use Oro\Bundle\DataAuditBundle\EventListener\SendChangedEntitiesToMessageQueueListener;
use Oro\Bundle\ShoppingListBundle\Entity\LineItem;
use Oro\Bundle\ShoppingListBundle\Entity\ShoppingList;
use Oro\Bundle\ShoppingListBundle\EventListener\InteractiveLoginListener;
use Oro\Bundle\ShoppingListBundle\Manager\GuestShoppingListMigrationManager;
use Oro\Bundle\ShoppingListBundle\Tests\Unit\Entity\Stub\CustomerVisitorStub;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Http\Event\InteractiveLoginEvent;

class InteractiveLoginListenerTest extends \PHPUnit\Framework\TestCase
{
    private const VISITOR_CREDENTIALS = [1, 'someSessionId'];

    private Request $request;

    private InteractiveLoginListener $listener;

    private CustomerVisitorManager|\PHPUnit\Framework\MockObject\MockObject $visitorManager;

    private GuestShoppingListMigrationManager|\PHPUnit\Framework\MockObject\MockObject
        $guestShoppingListMigrationManager;

    private ConfigManager|\PHPUnit\Framework\MockObject\MockObject $configManager;

    private LoggerInterface|\PHPUnit\Framework\MockObject\MockObject $logger;

    private SendChangedEntitiesToMessageQueueListener|\PHPUnit\Framework\MockObject\MockObject
        $sendChangedEntitiesToMessageQueueListener;

    /**
     * {@inheritdoc}
     */
    protected function setUp(): void
    {
        $this->guestShoppingListMigrationManager = $this->createMock(GuestShoppingListMigrationManager::class);
        $this->visitorManager = $this->createMock(CustomerVisitorManager::class);
        $this->logger = $this->createMock(LoggerInterface::class);
        $this->request = new Request();
        $this->configManager = $this->createMock(ConfigManager::class);
        $this->sendChangedEntitiesToMessageQueueListener = $this->createMock(
            SendChangedEntitiesToMessageQueueListener::class
        );

        $this->listener = new InteractiveLoginListener(
            $this->visitorManager,
            $this->guestShoppingListMigrationManager,
            $this->logger,
            $this->configManager,
            $this->sendChangedEntitiesToMessageQueueListener
        );
    }

    public function testWithoutCookie(): void
    {
        $this->configManager->expects(self::once())
            ->method('get')
            ->with('oro_shopping_list.availability_for_guests')
            ->willReturn(true);

        $this->listener->onInteractiveLogin(new InteractiveLoginEvent($this->request, $this->getToken()));
    }

    public function testWithoutVisitor(): void
    {
        $this->request->cookies->set(
            AnonymousCustomerUserAuthenticationListener::COOKIE_NAME,
            base64_encode(json_encode(self::VISITOR_CREDENTIALS))
        );

        $this->configManager->expects(self::once())
            ->method('get')
            ->with('oro_shopping_list.availability_for_guests')
            ->willReturn(true);

        $this->visitorManager->expects(self::once())
            ->method('find')
            ->willReturn(null);

        $this->sendChangedEntitiesToMessageQueueListener->expects(self::once())
            ->method('setEnabled')
            ->with();

        $this->guestShoppingListMigrationManager->expects(self::never())
            ->method('migrateGuestShoppingList');

        $this->listener->onInteractiveLogin(new InteractiveLoginEvent($this->request, $this->getToken()));
    }

    public function testWithoutVisitorShoppingLists(): void
    {
        $this->request->cookies->set(
            AnonymousCustomerUserAuthenticationListener::COOKIE_NAME,
            base64_encode(json_encode(self::VISITOR_CREDENTIALS))
        );

        $this->configManager->expects(self::once())
            ->method('get')
            ->with('oro_shopping_list.availability_for_guests')
            ->willReturn(true);

        $visitor = new CustomerVisitorStub();
        $this->visitorManager->expects(self::once())
            ->method('find')
            ->with(self::VISITOR_CREDENTIALS[0], self::VISITOR_CREDENTIALS[1])
            ->willReturn($visitor);

        $this->sendChangedEntitiesToMessageQueueListener->expects(self::exactly(2))
            ->method('setEnabled')
            ->withConsecutive([false], []);

        $this->guestShoppingListMigrationManager->expects(self::never())
            ->method('migrateGuestShoppingList');

        $this->listener->onInteractiveLogin(new InteractiveLoginEvent($this->request, $this->getToken()));
    }

    public function testMigrateGuestShoppingList(): void
    {
        $this->request->cookies->set(
            AnonymousCustomerUserAuthenticationListener::COOKIE_NAME,
            base64_encode(json_encode(self::VISITOR_CREDENTIALS))
        );
        $this->configManager->expects(self::once())
            ->method('get')
            ->with('oro_shopping_list.availability_for_guests')
            ->willReturn(true);

        $customerUser = new CustomerUser();
        $token = $this->getToken($customerUser);

        $shoppingList = new ShoppingList();
        $shoppingList->addLineItem(new LineItem());
        $visitor = new CustomerVisitorStub();
        $visitor->addShoppingList($shoppingList);
        $this->visitorManager->expects(self::once())
            ->method('find')
            ->with(self::VISITOR_CREDENTIALS[0], self::VISITOR_CREDENTIALS[1])
            ->willReturn($visitor);

        $this->sendChangedEntitiesToMessageQueueListener->expects(self::exactly(2))
            ->method('setEnabled')
            ->withConsecutive([false], []);

        $this->guestShoppingListMigrationManager->expects(self::once())
            ->method('migrateGuestShoppingList')
            ->with($visitor, $customerUser, $shoppingList);

        $this->listener->onInteractiveLogin(new InteractiveLoginEvent($this->request, $token));
    }

    public function testLogException(): void
    {
        $this->request->cookies->set(
            AnonymousCustomerUserAuthenticationListener::COOKIE_NAME,
            base64_encode(json_encode(self::VISITOR_CREDENTIALS))
        );
        $this->configManager->expects(self::once())
            ->method('get')
            ->with('oro_shopping_list.availability_for_guests')
            ->willReturn(true);

        $shoppingList = new ShoppingList();
        $shoppingList->addLineItem(new LineItem());
        $visitor = new CustomerVisitorStub();
        $visitor->addShoppingList($shoppingList);
        $this->visitorManager->expects(self::once())
            ->method('find')
            ->with(self::VISITOR_CREDENTIALS[0], self::VISITOR_CREDENTIALS[1])
            ->willReturn($visitor);

        $customerUser = new CustomerUser();
        $token = $this->getToken($customerUser);
        $this->guestShoppingListMigrationManager->expects(self::once())
            ->method('migrateGuestShoppingList')
            ->with($visitor, $customerUser, $shoppingList)
            ->willThrowException(new \Exception());

        $this->logger->expects(self::once())
            ->method('error')
            ->with('Migration of the guest shopping list failed.');

        $this->listener->onInteractiveLogin(new InteractiveLoginEvent($this->request, $token));
    }

    private function getToken(object $customerUser = null): TokenInterface
    {
        $customerUser = $customerUser ?: new CustomerUser();
        $token = $this->createMock(TokenInterface::class);
        $token->expects(self::once())
            ->method('getUser')
            ->willReturn($customerUser);

        return $token;
    }

    public function testNotCustomerLogin(): void
    {
        $this->configManager->expects(self::never())
            ->method('get');

        $this->visitorManager->expects(self::never())
            ->method('find');

        $this->guestShoppingListMigrationManager->expects(self::never())
            ->method('migrateGuestShoppingList');

        $this->listener->onInteractiveLogin(
            new InteractiveLoginEvent($this->request, $this->getToken(new \stdClass()))
        );
    }

    public function testGuestShoppingListConfigurationDisabled(): void
    {
        $this->configManager->expects(self::once())
            ->method('get')
            ->with('oro_shopping_list.availability_for_guests')
            ->willReturn(false);

        $this->visitorManager->expects(self::never())
            ->method('find');

        $this->guestShoppingListMigrationManager->expects(self::never())
            ->method('migrateGuestShoppingList');

        $this->listener->onInteractiveLogin(new InteractiveLoginEvent($this->request, $this->getToken()));
    }
}
