<?php

namespace Oro\Bundle\CheckoutBundle\Tests\Unit\EventListener;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\CheckoutBundle\Entity\Checkout;
use Oro\Bundle\CheckoutBundle\Event\LoginOnCheckoutEvent;
use Oro\Bundle\CheckoutBundle\EventListener\LoginOnCheckoutListener;
use Oro\Bundle\CheckoutBundle\Manager\CheckoutManager;
use Oro\Bundle\CheckoutBundle\Provider\CheckoutIdByTargetPathRequestProvider;
use Oro\Bundle\CheckoutBundle\Tests\Unit\Model\Action\CheckoutSourceStub;
use Oro\Bundle\CheckoutBundle\Workflow\ActionGroup\StartShoppingListCheckoutInterface;
use Oro\Bundle\ConfigBundle\Config\ConfigManager;
use Oro\Bundle\CustomerBundle\Entity\Customer;
use Oro\Bundle\CustomerBundle\Entity\CustomerUser;
use Oro\Bundle\CustomerBundle\Entity\CustomerVisitor;
use Oro\Bundle\CustomerBundle\Security\AnonymousCustomerUserAuthenticator;
use Oro\Bundle\ShoppingListBundle\Entity\ShoppingList;
use Oro\Bundle\ShoppingListBundle\Tests\Unit\Entity\Stub\ShoppingListStub;
use Oro\Component\Testing\ReflectionUtil;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Http\Authenticator\AuthenticatorInterface;
use Symfony\Component\Security\Http\Authenticator\InteractiveAuthenticatorInterface;
use Symfony\Component\Security\Http\Authenticator\Passport\Passport;
use Symfony\Component\Security\Http\Event\InteractiveLoginEvent;
use Symfony\Component\Security\Http\Event\LoginSuccessEvent;

/**
 * @SuppressWarnings(PHPMD.TooManyMethods)
 * @SuppressWarnings(PHPMD.TooManyPublicMethods)
 */
final class LoginOnCheckoutListenerTest extends TestCase
{
    private LoggerInterface&MockObject $logger;
    private ConfigManager&MockObject $configManager;
    private CheckoutManager&MockObject $checkoutManager;
    private EventDispatcherInterface&MockObject $eventDispatcher;
    private RouterInterface&MockObject $router;
    private StartShoppingListCheckoutInterface&MockObject $startShoppingListCheckout;
    private ManagerRegistry&MockObject $registry;
    private CheckoutIdByTargetPathRequestProvider&MockObject $checkoutIdByTargetPathRequestProvider;
    private InteractiveAuthenticatorInterface&MockObject $authenticator;
    private TokenInterface&MockObject $token;

    private Request $request;
    private LoginOnCheckoutListener $listener;

    #[\Override]
    protected function setUp(): void
    {
        $this->logger = $this->createMock(LoggerInterface::class);
        $this->configManager = $this->createMock(ConfigManager::class);
        $this->checkoutManager = $this->createMock(CheckoutManager::class);
        $this->eventDispatcher = $this->createMock(EventDispatcherInterface::class);
        $this->router = $this->createMock(RouterInterface::class);
        $this->startShoppingListCheckout = $this->createMock(StartShoppingListCheckoutInterface::class);
        $this->registry = $this->createMock(ManagerRegistry::class);
        $this->checkoutIdByTargetPathRequestProvider = $this->createMock(CheckoutIdByTargetPathRequestProvider::class);
        $this->token = $this->createMock(TokenInterface::class);
        $this->authenticator = $this->createMock(InteractiveAuthenticatorInterface::class);
        $this->request = new Request();

        $this->listener = new LoginOnCheckoutListener(
            $this->logger,
            $this->configManager,
            $this->checkoutManager,
            $this->eventDispatcher,
            $this->router,
            $this->registry,
            $this->checkoutIdByTargetPathRequestProvider,
            $this->startShoppingListCheckout
        );
    }

    public function testOnInteractiveLoginNoCustomerUser(): void
    {
        $this->token->expects(self::once())
            ->method('getUser')
            ->willReturn(null);

        $this->checkoutManager->expects(self::never())
            ->method('reassignCustomerUser');

        $this->listener->onInteractiveLogin(new InteractiveLoginEvent($this->request, $this->token));
    }

    public function testOnInteractiveLoginDisableGuestCheckout(): void
    {
        $customerUser = new CustomerUser();

        $this->token->expects(self::once())
            ->method('getUser')
            ->willReturn($customerUser);

        $this->checkoutManager->expects(self::once())
            ->method('reassignCustomerUser')
            ->with($customerUser);

        $this->configManager->expects(self::once())
            ->method('get')
            ->with('oro_checkout.guest_checkout')
            ->willReturn(false);

        $this->logger->expects(self::never())
            ->method('warning');

        $this->listener->onInteractiveLogin(new InteractiveLoginEvent($this->request, $this->token));
    }

    public function testOnInteractiveLoginNoCheckoutIdRequestParameter(): void
    {
        $customerUser = new CustomerUser();

        $this->token->expects(self::once())
            ->method('getUser')
            ->willReturn($customerUser);

        $this->checkoutManager->expects(self::once())
            ->method('reassignCustomerUser')
            ->with($customerUser);

        $this->configManager->expects(self::once())
            ->method('get')
            ->with('oro_checkout.guest_checkout')
            ->willReturn(true);

        $this->checkoutIdByTargetPathRequestProvider->expects(self::once())
            ->method('getCheckoutId')
            ->with($this->request)
            ->willReturn(null);

        $this->checkoutManager->expects(self::never())
            ->method('getCheckoutById');

        $this->logger->expects(self::never())
            ->method('warning');

        $this->listener->onInteractiveLogin(new InteractiveLoginEvent($this->request, $this->token));
    }

    public function testOnInteractiveLoginNoFoundCheckout(): void
    {
        $customerUser = new CustomerUser();

        $this->token->expects(self::once())
            ->method('getUser')
            ->willReturn($customerUser);

        $this->checkoutManager->expects(self::once())
            ->method('reassignCustomerUser')
            ->with($customerUser);

        $this->configManager->expects(self::once())
            ->method('get')
            ->with('oro_checkout.guest_checkout')
            ->willReturn(true);

        $this->checkoutIdByTargetPathRequestProvider->expects(self::never())
            ->method('getCheckoutId')
            ->with($this->request);

        $this->checkoutManager->expects(self::once())
            ->method('getCheckoutById')
            ->with(1)
            ->willReturn(null);

        $this->checkoutManager->expects(self::never())
            ->method('updateCheckoutCustomerUser');

        $this->logger->expects(self::once())
            ->method('warning')
            ->with('Wrong checkout id passed during login from checkout.', ['checkoutId' => 1]);

        $this->request->request->add(['_checkout_id' => 1]);

        $this->listener->onInteractiveLogin(new InteractiveLoginEvent($this->request, $this->token));
    }

    public function testOnInteractiveLogin(): void
    {
        $customerUser = new CustomerUser();
        $shoppingList = new ShoppingListStub();
        $shoppingList->addVisitor((new CustomerVisitor())->setSessionId('visitor_session_1'));
        $checkout = (new Checkout())->setSource((new CheckoutSourceStub())->setShoppingList($shoppingList));
        ReflectionUtil::setId($checkout, 1);
        $this->request->cookies->set(
            AnonymousCustomerUserAuthenticator::COOKIE_NAME,
            base64_encode(json_encode('visitor_session_1', JSON_THROW_ON_ERROR))
        );

        $this->token->expects(self::once())
            ->method('getUser')
            ->willReturn($customerUser);

        $this->checkoutManager->expects(self::once())
            ->method('reassignCustomerUser')
            ->with($customerUser);

        $this->configManager->expects(self::once())
            ->method('get')
            ->with('oro_checkout.guest_checkout')
            ->willReturn(true);

        $this->checkoutIdByTargetPathRequestProvider->expects(self::never())
            ->method('getCheckoutId')
            ->with($this->request);

        $this->checkoutManager->expects(self::once())
            ->method('getCheckoutById')
            ->with(1)
            ->willReturn($checkout);

        $this->checkoutManager->expects(self::once())
            ->method('updateCheckoutCustomerUser')
            ->with($checkout, $customerUser);

        $this->logger->expects(self::never())
            ->method('warning');

        $this->request->request->add(['_checkout_id' => 1]);

        $this->listener->onInteractiveLogin(new InteractiveLoginEvent($this->request, $this->token));

        self::assertEquals(
            $checkout->getId(),
            ReflectionUtil::getPropertyValue($this->listener, 'guestCheckoutId')
        );
    }

    public function testOnInteractiveLoginAdoptsGuestCustomerUserCheckout(): void
    {
        $customerUser = new CustomerUser();
        $shoppingList = new ShoppingListStub();
        $shoppingList->addVisitor((new CustomerVisitor())->setSessionId('visitor_session_1'));
        $checkout = (new Checkout())->setSource((new CheckoutSourceStub())->setShoppingList($shoppingList));
        ReflectionUtil::setId($checkout, 1);
        $checkout->setCustomerUser((new CustomerUser())->setIsGuest(true));
        $this->request->cookies->set(
            AnonymousCustomerUserAuthenticator::COOKIE_NAME,
            base64_encode(json_encode('visitor_session_1', JSON_THROW_ON_ERROR))
        );
        $this->token->expects(self::once())
            ->method('getUser')
            ->willReturn($customerUser);

        $this->checkoutManager->expects(self::once())
            ->method('reassignCustomerUser')
            ->with($customerUser);

        $this->configManager->expects(self::once())
            ->method('get')
            ->with('oro_checkout.guest_checkout')
            ->willReturn(true);

        $this->request->request->add(['_checkout_id' => 1]);

        $this->checkoutIdByTargetPathRequestProvider->expects(self::never())
            ->method('getCheckoutId');

        $this->checkoutManager->expects(self::once())
            ->method('getCheckoutById')
            ->with(1)
            ->willReturn($checkout);

        $this->checkoutManager->expects(self::once())
            ->method('updateCheckoutCustomerUser')
            ->with($checkout, $customerUser);

        $this->logger->expects(self::never())
            ->method('warning');

        $this->listener->onInteractiveLogin(new InteractiveLoginEvent($this->request, $this->token));

        self::assertEquals(1, ReflectionUtil::getPropertyValue($this->listener, 'guestCheckoutId'));
    }

    public function testOnInteractiveLoginContinuesCheckoutOfTheAuthenticatingCustomerUser(): void
    {
        $customerUser = new CustomerUser();
        ReflectionUtil::setId($customerUser, 42);
        $ownCustomerUser = new CustomerUser();
        ReflectionUtil::setId($ownCustomerUser, 42);

        $shoppingList = new ShoppingListStub();
        $checkout = (new Checkout())->setSource((new CheckoutSourceStub())->setShoppingList($shoppingList));
        ReflectionUtil::setId($checkout, 1);
        $checkout->setCustomerUser($ownCustomerUser);

        $this->token->expects(self::once())
            ->method('getUser')
            ->willReturn($customerUser);

        $this->checkoutManager->expects(self::once())
            ->method('reassignCustomerUser')
            ->with($customerUser);

        $this->configManager->expects(self::once())
            ->method('get')
            ->with('oro_checkout.guest_checkout')
            ->willReturn(true);

        $this->request->request->add(['_checkout_id' => 1]);

        $this->checkoutIdByTargetPathRequestProvider->expects(self::never())
            ->method('getCheckoutId');

        $this->checkoutManager->expects(self::once())
            ->method('getCheckoutById')
            ->with(1)
            ->willReturn($checkout);

        $this->checkoutManager->expects(self::once())
            ->method('updateCheckoutCustomerUser')
            ->with($checkout, $customerUser);

        $this->logger->expects(self::never())
            ->method('warning');

        $this->listener->onInteractiveLogin(new InteractiveLoginEvent($this->request, $this->token));

        self::assertEquals(1, ReflectionUtil::getPropertyValue($this->listener, 'guestCheckoutId'));
    }

    public function testOnInteractiveLoginRejectsCheckoutOfAnotherCustomerUser(): void
    {
        $customerUser = new CustomerUser();
        $shoppingList = new ShoppingListStub();
        $shoppingList->addVisitor((new CustomerVisitor())->setSessionId('visitor_session_1'));
        $checkout = (new Checkout())->setSource((new CheckoutSourceStub())->setShoppingList($shoppingList));
        ReflectionUtil::setId($checkout, 1);
        $checkout->setCustomerUser((new CustomerUser())->setCustomer(new Customer()));
        $this->request->cookies->set(
            AnonymousCustomerUserAuthenticator::COOKIE_NAME,
            base64_encode(json_encode('visitor_session_1', JSON_THROW_ON_ERROR))
        );
        $this->token->expects(self::once())
            ->method('getUser')
            ->willReturn($customerUser);

        $this->checkoutManager->expects(self::once())
            ->method('reassignCustomerUser')
            ->with($customerUser);

        $this->configManager->expects(self::once())
            ->method('get')
            ->with('oro_checkout.guest_checkout')
            ->willReturn(true);

        $this->request->request->add(['_checkout_id' => 1]);

        $this->checkoutIdByTargetPathRequestProvider->expects(self::never())
            ->method('getCheckoutId');

        $this->checkoutManager->expects(self::once())
            ->method('getCheckoutById')
            ->with(1)
            ->willReturn($checkout);

        $this->checkoutManager->expects(self::never())
            ->method('updateCheckoutCustomerUser');

        $this->logger->expects(self::once())
            ->method('warning')
            ->with('Wrong checkout id passed during login from checkout.', ['checkoutId' => 1]);

        $this->listener->onInteractiveLogin(new InteractiveLoginEvent($this->request, $this->token));

        self::assertNull(ReflectionUtil::getPropertyValue($this->listener, 'guestCheckoutId'));
    }

    public function testOnInteractiveLoginRejectsOwnerlessCheckoutWithCustomer(): void
    {
        $customerUser = new CustomerUser();
        $shoppingList = new ShoppingListStub();
        $shoppingList->addVisitor((new CustomerVisitor())->setSessionId('visitor_session_1'));
        $checkout = (new Checkout())->setSource((new CheckoutSourceStub())->setShoppingList($shoppingList));
        ReflectionUtil::setId($checkout, 1);
        $checkout->setCustomer(new Customer());
        $this->request->cookies->set(
            AnonymousCustomerUserAuthenticator::COOKIE_NAME,
            base64_encode(json_encode('visitor_session_1', JSON_THROW_ON_ERROR))
        );
        $this->token->expects(self::once())
            ->method('getUser')
            ->willReturn($customerUser);

        $this->checkoutManager->expects(self::once())
            ->method('reassignCustomerUser')
            ->with($customerUser);

        $this->configManager->expects(self::once())
            ->method('get')
            ->with('oro_checkout.guest_checkout')
            ->willReturn(true);

        $this->request->request->add(['_checkout_id' => 1]);

        $this->checkoutIdByTargetPathRequestProvider->expects(self::never())
            ->method('getCheckoutId');

        $this->checkoutManager->expects(self::once())
            ->method('getCheckoutById')
            ->with(1)
            ->willReturn($checkout);

        $this->checkoutManager->expects(self::never())
            ->method('updateCheckoutCustomerUser');

        $this->logger->expects(self::once())
            ->method('warning')
            ->with('Wrong checkout id passed during login from checkout.', ['checkoutId' => 1]);

        $this->listener->onInteractiveLogin(new InteractiveLoginEvent($this->request, $this->token));

        self::assertNull(ReflectionUtil::getPropertyValue($this->listener, 'guestCheckoutId'));
    }

    public function testOnInteractiveLoginRejectsCheckoutOfAnotherVisitor(): void
    {
        $customerUser = new CustomerUser();
        $shoppingList = new ShoppingListStub();
        $shoppingList->addVisitor((new CustomerVisitor())->setSessionId('visitor_session_1'));
        $checkout = (new Checkout())->setSource((new CheckoutSourceStub())->setShoppingList($shoppingList));
        ReflectionUtil::setId($checkout, 1);
        $this->request->cookies->set(
            AnonymousCustomerUserAuthenticator::COOKIE_NAME,
            base64_encode(json_encode('visitor_session_2', JSON_THROW_ON_ERROR))
        );
        $this->token->expects(self::once())
            ->method('getUser')
            ->willReturn($customerUser);

        $this->checkoutManager->expects(self::once())
            ->method('reassignCustomerUser')
            ->with($customerUser);

        $this->configManager->expects(self::once())
            ->method('get')
            ->with('oro_checkout.guest_checkout')
            ->willReturn(true);

        $this->request->request->add(['_checkout_id' => 1]);

        $this->checkoutIdByTargetPathRequestProvider->expects(self::never())
            ->method('getCheckoutId');

        $this->checkoutManager->expects(self::once())
            ->method('getCheckoutById')
            ->with(1)
            ->willReturn($checkout);

        $this->checkoutManager->expects(self::never())
            ->method('updateCheckoutCustomerUser');

        $this->logger->expects(self::once())
            ->method('warning')
            ->with('Wrong checkout id passed during login from checkout.', ['checkoutId' => 1]);

        $this->listener->onInteractiveLogin(new InteractiveLoginEvent($this->request, $this->token));

        self::assertNull(ReflectionUtil::getPropertyValue($this->listener, 'guestCheckoutId'));
    }

    public function testOnInteractiveLoginRejectsCheckoutWithoutVisitor(): void
    {
        $customerUser = new CustomerUser();
        $shoppingList = new ShoppingListStub();
        $checkout = (new Checkout())->setSource((new CheckoutSourceStub())->setShoppingList($shoppingList));
        ReflectionUtil::setId($checkout, 1);
        $this->request->cookies->set(
            AnonymousCustomerUserAuthenticator::COOKIE_NAME,
            base64_encode(json_encode('visitor_session_1', JSON_THROW_ON_ERROR))
        );
        $this->token->expects(self::once())
            ->method('getUser')
            ->willReturn($customerUser);

        $this->checkoutManager->expects(self::once())
            ->method('reassignCustomerUser')
            ->with($customerUser);

        $this->configManager->expects(self::once())
            ->method('get')
            ->with('oro_checkout.guest_checkout')
            ->willReturn(true);

        $this->request->request->add(['_checkout_id' => 1]);

        $this->checkoutIdByTargetPathRequestProvider->expects(self::never())
            ->method('getCheckoutId');

        $this->checkoutManager->expects(self::once())
            ->method('getCheckoutById')
            ->with(1)
            ->willReturn($checkout);

        $this->checkoutManager->expects(self::never())
            ->method('updateCheckoutCustomerUser');

        $this->logger->expects(self::once())
            ->method('warning')
            ->with('Wrong checkout id passed during login from checkout.', ['checkoutId' => 1]);

        $this->listener->onInteractiveLogin(new InteractiveLoginEvent($this->request, $this->token));

        self::assertNull(ReflectionUtil::getPropertyValue($this->listener, 'guestCheckoutId'));
    }

    public function testOnInteractiveLoginRejectsCheckoutIdTakenFromTargetPath(): void
    {
        $customerUser = new CustomerUser();
        $shoppingList = new ShoppingListStub();
        $shoppingList->addVisitor((new CustomerVisitor())->setSessionId('visitor_session_1'));
        $checkout = (new Checkout())->setSource((new CheckoutSourceStub())->setShoppingList($shoppingList));
        ReflectionUtil::setId($checkout, 1);
        $this->request->cookies->set(
            AnonymousCustomerUserAuthenticator::COOKIE_NAME,
            base64_encode(json_encode('visitor_session_2', JSON_THROW_ON_ERROR))
        );
        $this->token->expects(self::once())
            ->method('getUser')
            ->willReturn($customerUser);

        $this->checkoutManager->expects(self::once())
            ->method('reassignCustomerUser')
            ->with($customerUser);

        $this->configManager->expects(self::once())
            ->method('get')
            ->with('oro_checkout.guest_checkout')
            ->willReturn(true);

        $this->checkoutIdByTargetPathRequestProvider->expects(self::once())
            ->method('getCheckoutId')
            ->with($this->request)
            ->willReturn(1);

        $this->checkoutManager->expects(self::once())
            ->method('getCheckoutById')
            ->with(1)
            ->willReturn($checkout);

        $this->checkoutManager->expects(self::never())
            ->method('updateCheckoutCustomerUser');

        $this->logger->expects(self::once())
            ->method('warning')
            ->with('Wrong checkout id passed during login from checkout.', ['checkoutId' => 1]);

        $this->listener->onInteractiveLogin(new InteractiveLoginEvent($this->request, $this->token));

        self::assertNull(ReflectionUtil::getPropertyValue($this->listener, 'guestCheckoutId'));
    }

    /**
     * @dataProvider visitorSessionIdDataProvider
     */
    public function testOnInteractiveLoginVisitorSessionIdSource(
        ?string $cookieValue,
        mixed $requestAttribute,
        bool $adopted
    ): void {
        $customerUser = new CustomerUser();
        $shoppingList = new ShoppingListStub();
        $shoppingList->addVisitor((new CustomerVisitor())->setSessionId('visitor_session_1'));
        $checkout = (new Checkout())->setSource((new CheckoutSourceStub())->setShoppingList($shoppingList));
        ReflectionUtil::setId($checkout, 1);

        if (null !== $cookieValue) {
            $this->request->cookies->set(AnonymousCustomerUserAuthenticator::COOKIE_NAME, $cookieValue);
        }
        if (null !== $requestAttribute) {
            $this->request->attributes->set('visitor_session_id', $requestAttribute);
        }

        $this->token->expects(self::once())
            ->method('getUser')
            ->willReturn($customerUser);

        $this->checkoutManager->expects(self::once())
            ->method('reassignCustomerUser')
            ->with($customerUser);

        $this->configManager->expects(self::once())
            ->method('get')
            ->with('oro_checkout.guest_checkout')
            ->willReturn(true);

        $this->request->request->add(['_checkout_id' => 1]);

        $this->checkoutIdByTargetPathRequestProvider->expects(self::never())
            ->method('getCheckoutId');

        $this->checkoutManager->expects(self::once())
            ->method('getCheckoutById')
            ->with(1)
            ->willReturn($checkout);

        $this->checkoutManager->expects($adopted ? self::once() : self::never())
            ->method('updateCheckoutCustomerUser')
            ->with($checkout, $customerUser);

        $this->logger->expects($adopted ? self::never() : self::once())
            ->method('warning');

        $this->listener->onInteractiveLogin(new InteractiveLoginEvent($this->request, $this->token));
    }

    public static function visitorSessionIdDataProvider(): array
    {
        $encode = static fn (mixed $value): string => base64_encode(json_encode($value, JSON_THROW_ON_ERROR));

        return [
            'cookie carries the visitor session id' => [$encode('visitor_session_1'), null, true],
            'malformed cookie falls back to the request attribute' => ['not-a-cookie', 'visitor_session_1', true],
            'non string cookie value falls back to the request attribute' => [$encode(123), 'visitor_session_1', true],
            'empty cookie value falls back to the request attribute' => [$encode(''), 'visitor_session_1', true],
            'request attribute only' => [null, 'visitor_session_1', true],
            'non string request attribute' => [null, 123, false],
            'no visitor identity at all' => [null, null, false],
        ];
    }

    public function testOnCheckoutLoginKeepsCheckoutRejectedOnInteractiveLogin(): void
    {
        $customerUser = new CustomerUser();
        $shoppingList = new ShoppingListStub();
        $shoppingList->addVisitor((new CustomerVisitor())->setSessionId('visitor_session_1'));
        $checkout = (new Checkout())->setSource((new CheckoutSourceStub())->setShoppingList($shoppingList));
        ReflectionUtil::setId($checkout, 1);
        $checkout->setCustomerUser((new CustomerUser())->setCustomer(new Customer()));
        $this->request->cookies->set(
            AnonymousCustomerUserAuthenticator::COOKIE_NAME,
            base64_encode(json_encode('visitor_session_1', JSON_THROW_ON_ERROR))
        );
        $this->request->request->add(['_checkout_id' => 1]);

        $this->token->expects(self::exactly(2))
            ->method('getUser')
            ->willReturn($customerUser);

        $this->configManager->expects(self::exactly(2))
            ->method('get')
            ->with('oro_checkout.guest_checkout')
            ->willReturn(true);

        $this->authenticator->expects(self::once())
            ->method('isInteractive')
            ->willReturn(true);

        $this->checkoutManager->expects(self::exactly(2))
            ->method('getCheckoutById')
            ->with(1)
            ->willReturn($checkout);

        $this->checkoutManager->expects(self::never())
            ->method('updateCheckoutCustomerUser');

        $this->registry->expects(self::never())
            ->method('getManager');

        $this->startShoppingListCheckout->expects(self::never())
            ->method('execute');

        $this->listener->onInteractiveLogin(new InteractiveLoginEvent($this->request, $this->token));
        $this->listener->onCheckoutLogin(new LoginSuccessEvent(
            $this->authenticator,
            $this->createMock(Passport::class),
            $this->token,
            $this->request,
            null,
            'test'
        ));

        self::assertNull(ReflectionUtil::getPropertyValue($this->listener, 'guestCheckoutId'));
    }

    public function testOnCheckoutLoginGuestCheckoutDisabled(): void
    {
        $this->configManager->expects(self::once())
            ->method('get')
            ->with('oro_checkout.guest_checkout')
            ->willReturn(false);

        $this->authenticator->expects(self::never())
            ->method('isInteractive');

        $this->listener->onCheckoutLogin(new LoginSuccessEvent(
            $this->authenticator,
            $this->createMock(Passport::class),
            $this->token,
            $this->request,
            null,
            'test'
        ));
    }

    public function testOnCheckoutLoginNoInteractiveAuthenticator(): void
    {
        $this->configManager->expects(self::once())
            ->method('get')
            ->with('oro_checkout.guest_checkout')
            ->willReturn(true);

        $this->authenticator->expects(self::never())
            ->method('isInteractive');

        $this->listener->onCheckoutLogin(new LoginSuccessEvent(
            $this->createMock(AuthenticatorInterface::class),
            $this->createMock(Passport::class),
            $this->token,
            $this->request,
            null,
            'test'
        ));
    }

    public function testOnCheckoutLoginNoInteractiveLogin(): void
    {
        $this->configManager->expects(self::once())
            ->method('get')
            ->with('oro_checkout.guest_checkout')
            ->willReturn(true);

        $this->authenticator->expects(self::once())
            ->method('isInteractive')
            ->willReturn(false);

        $this->token->expects(self::never())
            ->method('getUser');

        $this->listener->onCheckoutLogin(new LoginSuccessEvent(
            $this->authenticator,
            $this->createMock(Passport::class),
            $this->token,
            $this->request,
            null,
            'test'
        ));
    }

    public function testOnCheckoutLoginNoCustomerUser(): void
    {
        $this->configManager->expects(self::once())
            ->method('get')
            ->with('oro_checkout.guest_checkout')
            ->willReturn(true);

        $this->authenticator->expects(self::once())
            ->method('isInteractive')
            ->willReturn(true);

        $this->checkoutIdByTargetPathRequestProvider->expects(self::never())
            ->method('getCheckoutId');

        $this->listener->onCheckoutLogin(new LoginSuccessEvent(
            $this->authenticator,
            $this->createMock(Passport::class),
            $this->token,
            $this->request,
            null,
            'test'
        ));
    }

    public function testOnCheckoutLoginNoGuestCheckoutId(): void
    {
        $customerUser = new CustomerUser();

        $this->configManager->expects(self::once())
            ->method('get')
            ->with('oro_checkout.guest_checkout')
            ->willReturn(true);

        $this->authenticator->expects(self::once())
            ->method('isInteractive')
            ->willReturn(true);

        $this->token->expects(self::once())
            ->method('getUser')
            ->willReturn($customerUser);

        $this->checkoutIdByTargetPathRequestProvider->expects(self::once())
            ->method('getCheckoutId')
            ->with($this->request)
            ->willReturn(null);

        $this->checkoutManager->expects(self::never())
            ->method('getCheckoutById');

        $this->listener->onCheckoutLogin(new LoginSuccessEvent(
            $this->authenticator,
            $this->createMock(Passport::class),
            $this->token,
            $this->request,
            null,
            'test'
        ));
    }

    public function testOnCheckoutLoginNoFoundCheckout(): void
    {
        $customerUser = new CustomerUser();

        $this->configManager->expects(self::once())
            ->method('get')
            ->with('oro_checkout.guest_checkout')
            ->willReturn(true);

        $this->authenticator->expects(self::once())
            ->method('isInteractive')
            ->willReturn(true);

        $this->token->expects(self::once())
            ->method('getUser')
            ->willReturn($customerUser);

        $this->checkoutIdByTargetPathRequestProvider->expects(self::never())
            ->method('getCheckoutId')
            ->with($this->request);

        $this->checkoutManager->expects(self::once())
            ->method('getCheckoutById')
            ->with(1)
            ->willReturn(null);

        $this->request->request->add(['_checkout_id' => 1]);

        $this->startShoppingListCheckout->expects(self::never())
            ->method('execute');

        $this->listener->onCheckoutLogin(new LoginSuccessEvent(
            $this->authenticator,
            $this->createMock(Passport::class),
            $this->token,
            $this->request,
            null,
            'test'
        ));
    }

    public function testOnCheckoutLoginLogException(): void
    {
        $customerUser = new CustomerUser();
        $source = (new CheckoutSourceStub())->setShoppingList(new ShoppingList());
        $checkout = (new Checkout())->setSource($source);
        ReflectionUtil::setId($checkout, 1);
        ReflectionUtil::setPropertyValue($this->listener, 'guestCheckoutId', 1);

        $this->configManager->expects(self::once())
            ->method('get')
            ->with('oro_checkout.guest_checkout')
            ->willReturn(true);

        $this->authenticator->expects(self::once())
            ->method('isInteractive')
            ->willReturn(true);

        $this->token->expects(self::once())
            ->method('getUser')
            ->willReturn($customerUser);

        $this->checkoutIdByTargetPathRequestProvider->expects(self::never())
            ->method('getCheckoutId')
            ->with($this->request);

        $this->checkoutManager->expects(self::once())
            ->method('getCheckoutById')
            ->with(1)
            ->willReturn($checkout);

        $this->request->request->add(['_checkout_id' => 1]);

        $em = $this->createMock(EntityManagerInterface::class);
        $em->expects(self::once())->method('beginTransaction');
        $em->expects(self::once())->method('rollback');

        $this->registry->expects(self::once())
            ->method('getManager')
            ->willReturn($em);

        $this->startShoppingListCheckout->expects(self::once())
            ->method('execute')
            ->with(new ShoppingList(), true)
            ->willThrowException(new \Exception());

        $this->eventDispatcher->expects(self::never())
            ->method('hasListeners');

        $this->logger->expects(self::once())
            ->method('error')
            ->with('Starting a guest checkout is not allowed after a user logs in.');

        $this->listener->onCheckoutLogin(new LoginSuccessEvent(
            $this->authenticator,
            $this->createMock(Passport::class),
            $this->token,
            $this->request,
            null,
            'test'
        ));
    }

    public function testOnCheckoutLogin(): void
    {
        $customerUser = new CustomerUser();
        $source = (new CheckoutSourceStub())->setShoppingList(new ShoppingList());
        $checkout = (new Checkout())->setSource($source);
        ReflectionUtil::setId($checkout, 1);
        ReflectionUtil::setPropertyValue($this->listener, 'guestCheckoutId', 1);

        $this->configManager->expects(self::once())
            ->method('get')
            ->with('oro_checkout.guest_checkout')
            ->willReturn(true);

        $this->authenticator->expects(self::once())
            ->method('isInteractive')
            ->willReturn(true);

        $this->token->expects(self::once())
            ->method('getUser')
            ->willReturn($customerUser);

        $this->checkoutIdByTargetPathRequestProvider->expects(self::never())
            ->method('getCheckoutId')
            ->with($this->request);

        $this->checkoutManager->expects(self::once())
            ->method('getCheckoutById')
            ->with(1)
            ->willReturn($checkout);

        $this->request->request->add(['_checkout_id' => 1]);

        $em = $this->createMock(EntityManagerInterface::class);
        $em->expects(self::once())->method('beginTransaction');
        $em->expects(self::once())->method('remove')->with($checkout);
        $em->expects(self::once())->method('flush');
        $em->expects(self::once())->method('commit');

        $this->registry->expects(self::once())
            ->method('getManager')
            ->willReturn($em);

        $this->startShoppingListCheckout->expects(self::once())
            ->method('execute')
            ->with(new ShoppingList(), true)
            ->willReturn(['checkout' => new Checkout(), 'redirectUrl' => 'https://test.test']);

        $this->eventDispatcher->expects(self::once())
            ->method('hasListeners')
            ->with(LoginOnCheckoutEvent::NAME)
            ->willReturn(true);

        $this->eventDispatcher->expects(self::once())
            ->method('dispatch');

        $this->listener->onCheckoutLogin(new LoginSuccessEvent(
            $this->authenticator,
            $this->createMock(Passport::class),
            $this->token,
            $this->request,
            null,
            'test'
        ));

        self::assertNull(ReflectionUtil::getPropertyValue($this->listener, 'guestCheckoutId'));
    }

    public function testOnCheckoutLoginWithPostMergeShoppingList(): void
    {
        $currentShoppingList = new ShoppingList();
        $customerUser = new CustomerUser();
        ReflectionUtil::setPropertyValue($this->listener, 'currentShoppingList', $currentShoppingList);

        $this->configManager->expects(self::once())
            ->method('get')
            ->with('oro_checkout.guest_checkout')
            ->willReturn(true);

        $this->authenticator->expects(self::once())
            ->method('isInteractive')
            ->willReturn(true);

        $this->token->expects(self::once())
            ->method('getUser')
            ->willReturn($customerUser);

        $this->checkoutIdByTargetPathRequestProvider->expects(self::once())
            ->method('getCheckoutId')
            ->with($this->request)
            ->willReturn(1);

        $this->checkoutManager->expects(self::once())
            ->method('getCheckoutById')
            ->with(1)
            ->willReturn(null);

        $em = $this->createMock(EntityManagerInterface::class);
        $em->expects(self::once())->method('beginTransaction');
        $em->expects(self::never())->method('remove');
        $em->expects(self::never())->method('flush');
        $em->expects(self::once())->method('commit');

        $this->registry->expects(self::once())
            ->method('getManager')
            ->willReturn($em);

        $this->startShoppingListCheckout->expects(self::once())
            ->method('execute')
            ->with($currentShoppingList, true)
            ->willReturn(['checkout' => new Checkout(), 'redirectUrl' => 'https://test.test']);

        $this->eventDispatcher->expects(self::once())
            ->method('hasListeners')
            ->with(LoginOnCheckoutEvent::NAME)
            ->willReturn(false);

        $this->eventDispatcher->expects(self::never())
            ->method('dispatch');

        $this->listener->onCheckoutLogin(new LoginSuccessEvent(
            $this->authenticator,
            $this->createMock(Passport::class),
            $this->token,
            $this->request,
            null,
            'test'
        ));

        self::assertNull(ReflectionUtil::getPropertyValue($this->listener, 'currentShoppingList'));
    }
}
