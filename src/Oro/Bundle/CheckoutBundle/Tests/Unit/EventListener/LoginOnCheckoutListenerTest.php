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
use Oro\Bundle\CustomerBundle\Entity\CustomerUser;
use Oro\Bundle\ShoppingListBundle\Entity\ShoppingList;
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

        $this->request->request->add(['_checkout_id' => 1]);

        $this->listener->onInteractiveLogin(new InteractiveLoginEvent($this->request, $this->token));
    }

    public function testOnInteractiveLogin(): void
    {
        $customerUser = new CustomerUser();
        $checkout = new Checkout();
        ReflectionUtil::setId($checkout, 1);

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

        $this->request->request->add(['_checkout_id' => 1]);

        $this->listener->onInteractiveLogin(new InteractiveLoginEvent($this->request, $this->token));

        self::assertEquals(
            $checkout->getId(),
            ReflectionUtil::getPropertyValue($this->listener, 'guestCheckoutId')
        );
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
