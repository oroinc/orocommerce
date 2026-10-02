<?php

declare(strict_types=1);

namespace Oro\Bundle\CheckoutBundle\Tests\Unit\EventListener;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\CheckoutBundle\Entity\Checkout;
use Oro\Bundle\CheckoutBundle\EventListener\LoginOnCheckoutOrderLimitListener;
use Oro\Bundle\CheckoutBundle\Manager\CheckoutManager;
use Oro\Bundle\CheckoutBundle\Provider\CheckoutIdByTargetPathRequestProvider;
use Oro\Bundle\CheckoutBundle\Provider\OrderLimitProviderInterface;
use Oro\Bundle\CheckoutBundle\Tests\Unit\Model\Action\CheckoutSourceStub;
use Oro\Bundle\ConfigBundle\Config\ConfigManager;
use Oro\Bundle\CustomerBundle\Entity\CustomerUser;
use Oro\Bundle\ShoppingListBundle\Entity\ShoppingList;
use Oro\Component\Testing\ReflectionUtil;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Http\Authenticator\InteractiveAuthenticatorInterface;
use Symfony\Component\Security\Http\Authenticator\Passport\Passport;
use Symfony\Component\Security\Http\Event\LoginSuccessEvent;

final class LoginOnCheckoutOrderLimitListenerTest extends TestCase
{
    private ConfigManager&MockObject $configManager;
    private CheckoutManager&MockObject $checkoutManager;
    private RouterInterface&MockObject $router;
    private CheckoutIdByTargetPathRequestProvider&MockObject $checkoutIdByTargetPathRequestProvider;
    private OrderLimitProviderInterface&MockObject $orderLimitProvider;
    private ManagerRegistry&MockObject $registry;
    private InteractiveAuthenticatorInterface&MockObject $authenticator;
    private TokenInterface&MockObject $token;

    private Request $request;
    private CustomerUser $customerUser;
    private LoginOnCheckoutOrderLimitListener $listener;

    #[\Override]
    protected function setUp(): void
    {
        $this->configManager = $this->createMock(ConfigManager::class);
        $this->checkoutManager = $this->createMock(CheckoutManager::class);
        $this->router = $this->createMock(RouterInterface::class);
        $this->checkoutIdByTargetPathRequestProvider = $this->createMock(CheckoutIdByTargetPathRequestProvider::class);
        $this->orderLimitProvider = $this->createMock(OrderLimitProviderInterface::class);
        $this->registry = $this->createMock(ManagerRegistry::class);
        $this->authenticator = $this->createMock(InteractiveAuthenticatorInterface::class);
        $this->token = $this->createMock(TokenInterface::class);

        $this->customerUser = new CustomerUser();
        ReflectionUtil::setId($this->customerUser, 1);

        $this->request = new Request();
        $this->request->request->add(['_checkout_id' => 1]);

        $this->listener = new LoginOnCheckoutOrderLimitListener(
            $this->configManager,
            $this->checkoutManager,
            $this->router,
            $this->checkoutIdByTargetPathRequestProvider,
            $this->orderLimitProvider,
            $this->registry
        );
    }

    public function testOwnCheckoutIsRemovedWhenOrderLimitsAreNotMet(): void
    {
        $checkout = (new Checkout())
            ->setSource((new CheckoutSourceStub())->setShoppingList(new ShoppingList()));
        $checkout->setCustomerUser($this->customerUser);
        ReflectionUtil::setId($checkout, 1);

        $this->configManager->expects(self::once())
            ->method('get')
            ->with('oro_checkout.guest_checkout')
            ->willReturn(true);

        $this->authenticator->expects(self::once())
            ->method('isInteractive')
            ->willReturn(true);

        $this->token->expects(self::any())
            ->method('getUser')
            ->willReturn($this->customerUser);

        $this->checkoutManager->expects(self::once())
            ->method('getCheckoutById')
            ->with(1)
            ->willReturn($checkout);

        $this->orderLimitProvider->expects(self::once())
            ->method('isMinimumOrderAmountMet')
            ->willReturn(false);

        $entityManager = $this->createMock(EntityManagerInterface::class);
        $entityManager->expects(self::once())
            ->method('remove')
            ->with($checkout);
        $entityManager->expects(self::once())
            ->method('flush');

        $this->registry->expects(self::once())
            ->method('getManagerForClass')
            ->willReturn($entityManager);

        $this->router->expects(self::once())
            ->method('generate')
            ->willReturn('/customer/shoppinglist/update/1');

        $event = new LoginSuccessEvent(
            $this->authenticator,
            $this->createMock(Passport::class),
            $this->token,
            $this->request,
            null,
            'test'
        );
        $this->listener->onCheckoutLogin($event);

        self::assertInstanceOf(RedirectResponse::class, $event->getResponse());
        self::assertTrue($event->isPropagationStopped());
    }

    public function testCheckoutOfAnotherCustomerUserIsLeftAlone(): void
    {
        $anotherCustomerUser = new CustomerUser();
        ReflectionUtil::setId($anotherCustomerUser, 2);

        $checkout = (new Checkout())
            ->setSource((new CheckoutSourceStub())->setShoppingList(new ShoppingList()));
        $checkout->setCustomerUser($anotherCustomerUser);
        ReflectionUtil::setId($checkout, 1);

        $this->configManager->expects(self::once())
            ->method('get')
            ->with('oro_checkout.guest_checkout')
            ->willReturn(true);

        $this->authenticator->expects(self::once())
            ->method('isInteractive')
            ->willReturn(true);

        $this->token->expects(self::any())
            ->method('getUser')
            ->willReturn($this->customerUser);

        $this->checkoutManager->expects(self::once())
            ->method('getCheckoutById')
            ->with(1)
            ->willReturn($checkout);

        $this->orderLimitProvider->expects(self::never())
            ->method('isMinimumOrderAmountMet');

        $this->registry->expects(self::never())
            ->method('getManagerForClass');

        $event = new LoginSuccessEvent(
            $this->authenticator,
            $this->createMock(Passport::class),
            $this->token,
            $this->request,
            null,
            'test'
        );
        $this->listener->onCheckoutLogin($event);

        self::assertNull($event->getResponse());
        self::assertFalse($event->isPropagationStopped());
    }

    public function testOwnerlessCheckoutIsLeftAlone(): void
    {
        $checkout = (new Checkout())
            ->setSource((new CheckoutSourceStub())->setShoppingList(new ShoppingList()));
        ReflectionUtil::setId($checkout, 1);

        $this->configManager->expects(self::once())
            ->method('get')
            ->with('oro_checkout.guest_checkout')
            ->willReturn(true);

        $this->authenticator->expects(self::once())
            ->method('isInteractive')
            ->willReturn(true);

        $this->token->expects(self::any())
            ->method('getUser')
            ->willReturn($this->customerUser);

        $this->checkoutManager->expects(self::once())
            ->method('getCheckoutById')
            ->with(1)
            ->willReturn($checkout);

        $this->orderLimitProvider->expects(self::never())
            ->method('isMinimumOrderAmountMet');

        $this->registry->expects(self::never())
            ->method('getManagerForClass');

        $event = new LoginSuccessEvent(
            $this->authenticator,
            $this->createMock(Passport::class),
            $this->token,
            $this->request,
            null,
            'test'
        );
        $this->listener->onCheckoutLogin($event);

        self::assertNull($event->getResponse());
    }

    public function testNotFoundCheckoutIsLeftAlone(): void
    {
        $this->configManager->expects(self::once())
            ->method('get')
            ->with('oro_checkout.guest_checkout')
            ->willReturn(true);

        $this->authenticator->expects(self::once())
            ->method('isInteractive')
            ->willReturn(true);

        $this->token->expects(self::any())
            ->method('getUser')
            ->willReturn($this->customerUser);

        $this->checkoutManager->expects(self::once())
            ->method('getCheckoutById')
            ->with(1)
            ->willReturn(null);

        $this->orderLimitProvider->expects(self::never())
            ->method('isMinimumOrderAmountMet');

        $this->registry->expects(self::never())
            ->method('getManagerForClass');

        $event = new LoginSuccessEvent(
            $this->authenticator,
            $this->createMock(Passport::class),
            $this->token,
            $this->request,
            null,
            'test'
        );
        $this->listener->onCheckoutLogin($event);

        self::assertNull($event->getResponse());
    }

    public function testOwnCheckoutIsKeptWhenOrderLimitsAreMet(): void
    {
        $checkout = (new Checkout())
            ->setSource((new CheckoutSourceStub())->setShoppingList(new ShoppingList()));
        $checkout->setCustomerUser($this->customerUser);
        ReflectionUtil::setId($checkout, 1);

        $this->configManager->expects(self::once())
            ->method('get')
            ->with('oro_checkout.guest_checkout')
            ->willReturn(true);

        $this->authenticator->expects(self::once())
            ->method('isInteractive')
            ->willReturn(true);

        $this->token->expects(self::any())
            ->method('getUser')
            ->willReturn($this->customerUser);

        $this->checkoutManager->expects(self::once())
            ->method('getCheckoutById')
            ->with(1)
            ->willReturn($checkout);

        $this->orderLimitProvider->expects(self::once())
            ->method('isMinimumOrderAmountMet')
            ->willReturn(true);

        $this->orderLimitProvider->expects(self::once())
            ->method('isMaximumOrderAmountMet')
            ->willReturn(true);

        $this->registry->expects(self::never())
            ->method('getManagerForClass');

        $event = new LoginSuccessEvent(
            $this->authenticator,
            $this->createMock(Passport::class),
            $this->token,
            $this->request,
            null,
            'test'
        );
        $this->listener->onCheckoutLogin($event);

        self::assertNull($event->getResponse());
    }
}
