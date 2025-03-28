<?php

namespace Oro\Bundle\ShoppingListBundle\Tests\Unit\EventListener;

use Oro\Bundle\ConfigBundle\Config\ConfigManager;
use Oro\Bundle\CustomerBundle\Entity\CustomerUser;
use Oro\Bundle\CustomerBundle\Entity\CustomerVisitorManager;
use Oro\Bundle\CustomerBundle\Security\AnonymousCustomerUserAuthenticator;
use Oro\Bundle\DataAuditBundle\EventListener\SendChangedEntitiesToMessageQueueListener;
use Oro\Bundle\ShoppingListBundle\Entity\ShoppingList;
use Oro\Bundle\ShoppingListBundle\EventListener\InteractiveLoginListener;
use Oro\Bundle\ShoppingListBundle\Manager\GuestShoppingListMigrationManager;
use Oro\Bundle\ShoppingListBundle\Tests\Unit\Entity\Stub\CustomerVisitorStub;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Session\Flash\FlashBagInterface;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Http\Event\InteractiveLoginEvent;
use Symfony\Contracts\Translation\TranslatorInterface;

final class InteractiveLoginListenerTest extends TestCase
{
    private const string VISITOR_SESSION_ID = 'someSessionId';

    private Request $request;
    private CustomerVisitorManager&MockObject $visitorManager;
    private GuestShoppingListMigrationManager&MockObject $guestShoppingListMigrationManager;
    private ConfigManager&MockObject $configManager;
    private LoggerInterface&MockObject $logger;
    private SendChangedEntitiesToMessageQueueListener&MockObject $sendChangedEntitiesListener;
    private TranslatorInterface&MockObject $translator;
    private TokenInterface&MockObject $token;
    private InteractiveLoginListener $listener;

    #[\Override]
    protected function setUp(): void
    {
        $this->request = new Request();
        $this->guestShoppingListMigrationManager = $this->createMock(GuestShoppingListMigrationManager::class);
        $this->visitorManager = $this->createMock(CustomerVisitorManager::class);
        $this->logger = $this->createMock(LoggerInterface::class);
        $this->configManager = $this->createMock(ConfigManager::class);
        $this->sendChangedEntitiesListener = $this->createMock(SendChangedEntitiesToMessageQueueListener::class);
        $this->translator = $this->createMock(TranslatorInterface::class);
        $this->token = $this->createMock(TokenInterface::class);

        $this->listener = new InteractiveLoginListener(
            $this->visitorManager,
            $this->guestShoppingListMigrationManager,
            $this->logger,
            $this->configManager,
            $this->sendChangedEntitiesListener,
            $this->translator
        );
    }

    public function testNoCustomerUser(): void
    {
        $this->token->expects(self::once())
            ->method('getUser')
            ->willReturn(null);

        $this->configManager->expects(self::never())
            ->method('get')
            ->with('oro_shopping_list.availability_for_guests');

        $this->guestShoppingListMigrationManager->expects(self::never())
            ->method('migrateGuestShoppingList');

        $this->listener->onInteractiveLogin(new InteractiveLoginEvent($this->request, $this->token));
    }

    public function testAvailabilityForGuestsIsDisabled(): void
    {
        $this->token->expects(self::once())
            ->method('getUser')
            ->willReturn(new CustomerUser());

        $this->configManager->expects(self::once())
            ->method('get')
            ->with('oro_shopping_list.availability_for_guests')
            ->willReturn(false);

        $this->guestShoppingListMigrationManager->expects(self::never())
            ->method('migrateGuestShoppingList');

        $this->listener->onInteractiveLogin(new InteractiveLoginEvent($this->request, $this->token));
    }

    public function testWithoutCookie(): void
    {
        $this->token->expects(self::once())
            ->method('getUser')
            ->willReturn(new CustomerUser());

        $this->configManager->expects(self::once())
            ->method('get')
            ->with('oro_shopping_list.availability_for_guests')
            ->willReturn(true);

        $this->guestShoppingListMigrationManager->expects(self::never())
            ->method('migrateGuestShoppingList');

        $this->listener->onInteractiveLogin(new InteractiveLoginEvent($this->request, $this->token));
    }

    public function testWithoutVisitor(): void
    {
        $this->token->expects(self::once())
            ->method('getUser')
            ->willReturn(new CustomerUser());

        $this->configManager->expects(self::once())
            ->method('get')
            ->with('oro_shopping_list.availability_for_guests')
            ->willReturn(true);

        $this->request->cookies->set(
            AnonymousCustomerUserAuthenticator::COOKIE_NAME,
            base64_encode(json_encode(self::VISITOR_SESSION_ID, JSON_THROW_ON_ERROR))
        );

        $this->visitorManager->expects(self::once())
            ->method('find')
            ->with(self::VISITOR_SESSION_ID)
            ->willReturn(null);

        $this->guestShoppingListMigrationManager->expects(self::never())
            ->method('migrateGuestShoppingList');

        $this->listener->onInteractiveLogin(new InteractiveLoginEvent($this->request, $this->token));
    }

    public function testWithoutVisitorShoppingLists(): void
    {
        $this->token->expects(self::once())
            ->method('getUser')
            ->willReturn(new CustomerUser());

        $this->configManager->expects(self::once())
            ->method('get')
            ->with('oro_shopping_list.availability_for_guests')
            ->willReturn(true);

        $this->request->cookies->set(
            AnonymousCustomerUserAuthenticator::COOKIE_NAME,
            base64_encode(json_encode(self::VISITOR_SESSION_ID, JSON_THROW_ON_ERROR))
        );

        $visitor = new CustomerVisitorStub();
        $this->visitorManager->expects(self::once())
            ->method('find')
            ->with(self::VISITOR_SESSION_ID)
            ->willReturn($visitor);

        $this->guestShoppingListMigrationManager->expects(self::never())
            ->method('migrateGuestShoppingList');

        $this->listener->onInteractiveLogin(new InteractiveLoginEvent($this->request, $this->token));
    }

    public function testMigrateGuestShoppingListWithOperationCodeNone(): void
    {
        $shoppingList = new ShoppingList();
        $customerUser = new CustomerUser();
        $visitor = new CustomerVisitorStub();
        $visitor->addShoppingList($shoppingList);

        $this->token->expects(self::once())
            ->method('getUser')
            ->willReturn($customerUser);

        $this->configManager->expects(self::once())
            ->method('get')
            ->with('oro_shopping_list.availability_for_guests')
            ->willReturn(true);

        $this->request->cookies->set(
            AnonymousCustomerUserAuthenticator::COOKIE_NAME,
            base64_encode(json_encode(self::VISITOR_SESSION_ID, JSON_THROW_ON_ERROR))
        );

        $this->visitorManager->expects(self::once())
            ->method('find')
            ->with(self::VISITOR_SESSION_ID)
            ->willReturn($visitor);

        $this->sendChangedEntitiesListener->expects(self::exactly(2))
            ->method('setEnabled')
            ->withConsecutive([false], [true]);

        $this->guestShoppingListMigrationManager->expects(self::once())
            ->method('migrateGuestShoppingList')
            ->with($visitor, $customerUser, $shoppingList)
            ->willReturn(GuestShoppingListMigrationManager::OPERATION_NONE);

        $this->translator->expects(self::never())
            ->method('trans');

        $this->listener->onInteractiveLogin(new InteractiveLoginEvent($this->request, $this->token));
    }

    public function testMigrateGuestShoppingListWithOperationCodeMerge(): void
    {
        $shoppingList = new ShoppingList();
        $customerUser = new CustomerUser();
        $visitor = new CustomerVisitorStub();
        $visitor->addShoppingList($shoppingList);

        $this->token->expects(self::once())
            ->method('getUser')
            ->willReturn($customerUser);

        $this->configManager->expects(self::once())
            ->method('get')
            ->with('oro_shopping_list.availability_for_guests')
            ->willReturn(true);

        $this->request->cookies->set(
            AnonymousCustomerUserAuthenticator::COOKIE_NAME,
            base64_encode(json_encode(self::VISITOR_SESSION_ID, JSON_THROW_ON_ERROR))
        );

        $this->visitorManager->expects(self::once())
            ->method('find')
            ->with(self::VISITOR_SESSION_ID)
            ->willReturn($visitor);

        $this->sendChangedEntitiesListener->expects(self::exactly(2))
            ->method('setEnabled')
            ->withConsecutive([false], [true]);

        $this->guestShoppingListMigrationManager->expects(self::once())
            ->method('migrateGuestShoppingList')
            ->with($visitor, $customerUser, $shoppingList)
            ->willReturn(GuestShoppingListMigrationManager::OPERATION_MERGE);

        $this->translator->expects(self::once())
            ->method('trans')
            ->with('oro.shoppinglist.flash.merge')
            ->willReturn('some text');

        $flashBag = $this->createMock(FlashBagInterface::class);
        $flashBag->expects(self::once())
            ->method('add')
            ->with('notice', 'some text');

        $session = $this->createMock(Session::class);
        $session->expects(self::once())
            ->method('getFlashBag')
            ->willReturn($flashBag);

        $this->request->setSession($session);

        $this->listener->onInteractiveLogin(new InteractiveLoginEvent($this->request, $this->token));
    }

    public function testMigrateGuestShoppingListWhenVisitorSessionIdIsRetrievedFromRequestAttributes(): void
    {
        $shoppingList = new ShoppingList();
        $customerUser = new CustomerUser();
        $visitor = new CustomerVisitorStub();
        $visitor->addShoppingList($shoppingList);

        $this->token->expects(self::once())
            ->method('getUser')
            ->willReturn($customerUser);

        $this->configManager->expects(self::once())
            ->method('get')
            ->with('oro_shopping_list.availability_for_guests')
            ->willReturn(true);

        $this->request->attributes->set('visitor_session_id', self::VISITOR_SESSION_ID);

        $this->visitorManager->expects(self::once())
            ->method('find')
            ->with(self::VISITOR_SESSION_ID)
            ->willReturn($visitor);

        $this->sendChangedEntitiesListener->expects(self::exactly(2))
            ->method('setEnabled')
            ->withConsecutive([false], [true]);

        $this->guestShoppingListMigrationManager->expects(self::once())
            ->method('migrateGuestShoppingList')
            ->with($visitor, $customerUser, $shoppingList)
            ->willReturn(GuestShoppingListMigrationManager::OPERATION_MERGE);

        $this->translator->expects(self::never())
            ->method('trans');

        $this->listener->onInteractiveLogin(new InteractiveLoginEvent($this->request, $this->token));
    }

    public function testMigrateGuestShoppingListWhenNotExistingVisitorSessionIdIsRetrievedFromRequestAttributes(): void
    {
        $this->token->expects(self::once())
            ->method('getUser')
            ->willReturn(new CustomerUser());

        $this->configManager->expects(self::once())
            ->method('get')
            ->with('oro_shopping_list.availability_for_guests')
            ->willReturn(true);

        $this->request->attributes->set('visitor_session_id', self::VISITOR_SESSION_ID);

        $this->visitorManager->expects(self::once())
            ->method('find')
            ->with(self::VISITOR_SESSION_ID)
            ->willReturn(null);

        $this->guestShoppingListMigrationManager->expects(self::never())
            ->method('migrateGuestShoppingList');

        $this->listener->onInteractiveLogin(new InteractiveLoginEvent($this->request, $this->token));
    }

    public function testLogException(): void
    {
        $shoppingList = new ShoppingList();
        $customerUser = new CustomerUser();
        $visitor = new CustomerVisitorStub();
        $visitor->addShoppingList($shoppingList);

        $this->token->expects(self::once())
            ->method('getUser')
            ->willReturn($customerUser);

        $this->configManager->expects(self::once())
            ->method('get')
            ->with('oro_shopping_list.availability_for_guests')
            ->willReturn(true);

        $this->request->cookies->set(
            AnonymousCustomerUserAuthenticator::COOKIE_NAME,
            base64_encode(json_encode(self::VISITOR_SESSION_ID, JSON_THROW_ON_ERROR))
        );

        $this->visitorManager->expects(self::once())
            ->method('find')
            ->with(self::VISITOR_SESSION_ID)
            ->willReturn($visitor);

        $this->sendChangedEntitiesListener->expects(self::exactly(2))
            ->method('setEnabled')
            ->withConsecutive([false], [true]);

        $this->guestShoppingListMigrationManager->expects(self::once())
            ->method('migrateGuestShoppingList')
            ->with($visitor, $customerUser, $shoppingList)
            ->willThrowException(new \Exception());

        $this->translator->expects(self::never())
            ->method('trans');

        $this->logger->expects(self::once())
            ->method('error')
            ->with('Migration of the guest shopping list failed.');

        $this->listener->onInteractiveLogin(new InteractiveLoginEvent($this->request, $this->token));
    }
}
