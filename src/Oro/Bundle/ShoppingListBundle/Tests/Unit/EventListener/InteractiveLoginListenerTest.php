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
    const VISITOR_CREDENTIALS = [1, 'someSessionId'];

    /**
     * @var Request
     */
    private $request;

    /**
     * @var InteractiveLoginEvent|\PHPUnit\Framework\MockObject\MockObject
     */
    private $event;

    /**
     * @var InteractiveLoginListener
     */
    private $listener;

    /**
     * @var CustomerVisitorManager|\PHPUnit\Framework\MockObject\MockObject
     */
    private $visitorManager;

    /**
     * @var GuestShoppingListMigrationManager|\PHPUnit\Framework\MockObject\MockObject
     */
    private $guestShoppingListMigrationManager;

    /**
     * @var ConfigManager|\PHPUnit\Framework\MockObject\MockObject
     */
    private $configManager;

    /**
     * @var LoggerInterface|\PHPUnit\Framework\MockObject\MockObject
     */
    private $logger;

    /**
     * @var SendChangedEntitiesToMessageQueueListener|\PHPUnit\Framework\MockObject\MockObject
     */
    private $sendChangedEntitiesToMessageQueueListener;
    /**
     * {@inheritdoc}
     */
    protected function setUp(): void
    {
        $this->guestShoppingListMigrationManager         = $this->createMock(GuestShoppingListMigrationManager::class);
        $this->visitorManager                            = $this->createMock(CustomerVisitorManager::class);
        $this->logger                                    = $this->createMock(LoggerInterface::class);
        $this->request                                   = new Request();
        $this->configManager                             = $this->createMock(ConfigManager::class);
        $this->sendChangedEntitiesToMessageQueueListener = $this->createMock(
            SendChangedEntitiesToMessageQueueListener::class
        );
        $this->event                             = $this->getMockBuilder(InteractiveLoginEvent::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->listener                          = new InteractiveLoginListener(
            $this->visitorManager,
            $this->guestShoppingListMigrationManager,
            $this->logger,
            $this->configManager,
            $this->sendChangedEntitiesToMessageQueueListener
        );
    }

    public function testWithoutCookie()
    {
        $this->event->expects($this->once())
            ->method('getRequest')
            ->willReturn($this->request);

        $this->configManager->expects($this->once())
            ->method('get')
            ->with('oro_shopping_list.availability_for_guests')
            ->willReturn(true);

        $this->configureToken();

        $this->listener->onInteractiveLogin($this->event);
    }

    public function testWithoutVisitor()
    {
        $this->request->cookies->set(
            AnonymousCustomerUserAuthenticationListener::COOKIE_NAME,
            base64_encode(json_encode(self::VISITOR_CREDENTIALS))
        );

        $this->configManager->expects($this->once())
            ->method('get')
            ->with('oro_shopping_list.availability_for_guests')
            ->willReturn(true);
        $this->configureToken();

        $this->event->expects($this->once())
            ->method('getRequest')
            ->willReturn($this->request);

        $this->visitorManager->expects($this->once())
            ->method('find')
            ->willReturn(null);

        $this->sendChangedEntitiesToMessageQueueListener->expects($this->once())
            ->method('setEnabled')
            ->with();

        $this->guestShoppingListMigrationManager->expects($this->never())
            ->method('migrateGuestShoppingList');

        $this->listener->onInteractiveLogin($this->event);
    }

    public function testWithoutVisitorShoppingLists()
    {
        $this->request->cookies->set(
            AnonymousCustomerUserAuthenticationListener::COOKIE_NAME,
            base64_encode(json_encode(self::VISITOR_CREDENTIALS))
        );

        $this->event->expects($this->once())
            ->method('getRequest')
            ->willReturn($this->request);

        $this->configManager->expects($this->once())
            ->method('get')
            ->with('oro_shopping_list.availability_for_guests')
            ->willReturn(true);
        $this->configureToken();

        $visitor = new CustomerVisitorStub();
        $this->visitorManager->expects($this->once())
            ->method('find')
            ->with(self::VISITOR_CREDENTIALS[0], self::VISITOR_CREDENTIALS[1])
            ->willReturn($visitor);

        $this->sendChangedEntitiesToMessageQueueListener->expects($this->exactly(2))
            ->method('setEnabled')
            ->withConsecutive([false], []);

        $this->guestShoppingListMigrationManager->expects($this->never())
            ->method('migrateGuestShoppingList');

        $this->listener->onInteractiveLogin($this->event);
    }

    public function testMigrateGuestShoppingList()
    {
        $this->request->cookies->set(
            AnonymousCustomerUserAuthenticationListener::COOKIE_NAME,
            base64_encode(json_encode(self::VISITOR_CREDENTIALS))
        );
        $this->event->expects($this->once())
            ->method('getRequest')
            ->willReturn($this->request);

        $this->configManager->expects($this->once())
            ->method('get')
            ->with('oro_shopping_list.availability_for_guests')
            ->willReturn(true);
        $customerUser = $this->configureToken();

        $shoppingList = new ShoppingList();
        $shoppingList->addLineItem(new LineItem());
        $visitor = new CustomerVisitorStub();
        $visitor->addShoppingList($shoppingList);
        $this->visitorManager->expects($this->once())
            ->method('find')
            ->with(self::VISITOR_CREDENTIALS[0], self::VISITOR_CREDENTIALS[1])
            ->willReturn($visitor);

        $this->sendChangedEntitiesToMessageQueueListener->expects($this->exactly(2))
            ->method('setEnabled')
            ->withConsecutive([false], []);

        $this->guestShoppingListMigrationManager->expects($this->once())
            ->method('migrateGuestShoppingList')
            ->with($visitor, $customerUser, $shoppingList);

        $this->listener->onInteractiveLogin($this->event);
    }

    public function testLogException()
    {
        $this->request->cookies->set(
            AnonymousCustomerUserAuthenticationListener::COOKIE_NAME,
            base64_encode(json_encode(self::VISITOR_CREDENTIALS))
        );
        $this->event->expects($this->once())
            ->method('getRequest')
            ->willReturn($this->request);

        $this->configManager->expects($this->once())
            ->method('get')
            ->with('oro_shopping_list.availability_for_guests')
            ->willReturn(true);

        $shoppingList = new ShoppingList();
        $shoppingList->addLineItem(new LineItem());
        $visitor = new CustomerVisitorStub();
        $visitor->addShoppingList($shoppingList);
        $this->visitorManager->expects($this->once())
            ->method('find')
            ->with(self::VISITOR_CREDENTIALS[0], self::VISITOR_CREDENTIALS[1])
            ->willReturn($visitor);

        $customerUser = $this->configureToken();
        $this->guestShoppingListMigrationManager->expects($this->once())
            ->method('migrateGuestShoppingList')
            ->with($visitor, $customerUser, $shoppingList)
            ->willThrowException(new \Exception());

        $this->logger->expects($this->once())
            ->method('error')
            ->with('Migration of the guest shopping list failed.');

        $this->listener->onInteractiveLogin($this->event);
    }

    /**
     * @param bool $isCustomer
     * @return CustomerUser|\stdClass
     */
    private function configureToken($isCustomer = true)
    {
        $customerUser = $isCustomer ? new CustomerUser() : new \stdClass();
        $token = $this->createMock(TokenInterface::class);
        $token->expects($this->once())
            ->method('getUser')
            ->willReturn($customerUser);
        $this->event->expects($this->once())
            ->method('getAuthenticationToken')
            ->willReturn($token);

        return $customerUser;
    }

    public function testNotCustomerLogin()
    {
        $this->configManager->expects($this->never())
            ->method('get');
        $this->configureToken(false);
        $this->event->expects($this->never())
            ->method('getRequest')
            ->willReturn($this->request);

        $this->visitorManager->expects($this->never())
            ->method('find');

        $this->guestShoppingListMigrationManager->expects($this->never())
            ->method('migrateGuestShoppingList');

        $this->listener->onInteractiveLogin($this->event);
    }

    public function testGuestShoppingListConfigurationDisabled()
    {
        $this->configManager->expects($this->once())
            ->method('get')
            ->with('oro_shopping_list.availability_for_guests')
            ->willReturn(false);

        $this->configureToken();
        $this->event->expects($this->never())
            ->method('getRequest')
            ->willReturn($this->request);

        $this->visitorManager->expects($this->never())
            ->method('find');

        $this->guestShoppingListMigrationManager->expects($this->never())
            ->method('migrateGuestShoppingList');

        $this->listener->onInteractiveLogin($this->event);
    }
}
