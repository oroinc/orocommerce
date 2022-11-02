<?php

namespace Oro\Bundle\CheckoutBundle\Tests\Unit\EventListener;

use Oro\Bundle\CheckoutBundle\Entity\Checkout;
use Oro\Bundle\CheckoutBundle\Entity\CheckoutSource;
use Oro\Bundle\CheckoutBundle\Event\LoginOnCheckoutEvent;
use Oro\Bundle\CheckoutBundle\EventListener\LoginOnCheckoutListener;
use Oro\Bundle\CheckoutBundle\Manager\CheckoutManager;
use Oro\Bundle\ConfigBundle\Config\ConfigManager;
use Oro\Bundle\CustomerBundle\Entity\CustomerUser;
use Psr\Log\LoggerInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Http\Event\InteractiveLoginEvent;

class LoginOnCheckoutListenerTest extends \PHPUnit\Framework\TestCase
{
    private LoggerInterface|\PHPUnit\Framework\MockObject\MockObject $logger;

    private ConfigManager|\PHPUnit\Framework\MockObject\MockObject $configManager;

    private CheckoutManager|\PHPUnit\Framework\MockObject\MockObject $checkoutManager;

    private EventDispatcherInterface|\PHPUnit\Framework\MockObject\MockObject $eventDispatcher;

    private LoginOnCheckoutListener $listener;

    private Request $request;

    /**
     * {@inheritdoc}
     */
    protected function setUp(): void
    {
        $this->logger = $this->createMock(LoggerInterface::class);

        $this->configManager = $this->getMockBuilder(ConfigManager::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->checkoutManager = $this->getMockBuilder(CheckoutManager::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->eventDispatcher = $this->createMock(EventDispatcherInterface::class);

        $this->listener = new LoginOnCheckoutListener(
            $this->logger,
            $this->configManager,
            $this->checkoutManager,
            $this->eventDispatcher
        );

        $this->request = new Request();
    }

    private function getEvent(object $customerUser): InteractiveLoginEvent
    {
        $token = $this->createMock(TokenInterface::class);
        $token->expects(self::once())
            ->method('getUser')
            ->willReturn($customerUser);

        return new InteractiveLoginEvent($this->request, $token);
    }

    public function testOnInteractiveWrongToken(): void
    {
        $event = $this->getEvent(new \stdClass());
        $this->configManager->expects(self::never())->method('get');
        $this->listener->onInteractiveLogin($event);
    }

    public function testOnInteractiveReassignCustomerUser(): void
    {
        $customerUser = new CustomerUser();
        $event = $this->getEvent($customerUser);
        $this->checkoutManager->expects(self::once())
            ->method('reassignCustomerUser')
            ->with($customerUser);
        $this->configManager->expects(self::never())->method('get');
        $this->listener->onInteractiveLogin($event);
    }

    public function testOnInteractiveLoginConfigurationDisabled(): void
    {
        $event = $this->getEvent(new CustomerUser());
        $this->request->request->add(['_checkout_id' => 777]);
        $this->configManager->expects(self::once())
            ->method('get')
            ->with('oro_checkout.guest_checkout')
            ->willReturn(false);
        $this->checkoutManager->expects(self::never())->method('getCheckoutById');
        $this->listener->onInteractiveLogin($event);
    }

    public function testOnInteractiveLoginWrongCheckout(): void
    {
        $event = $this->getEvent(new CustomerUser());
        $this->request->request->add(['_checkout_id' => 777]);
        $this->configManager->expects(self::once())
            ->method('get')
            ->with('oro_checkout.guest_checkout')
            ->willReturn(true);

        $this->checkoutManager->expects(self::once())
            ->method('getCheckoutById')
            ->with(777)
            ->willReturn(null);

        $this->logger->expects(self::once())
            ->method('warning')
            ->with("Wrong checkout id - 777 passed during login from checkout");

        $this->listener->onInteractiveLogin($event);
    }

    public function testOnInteractiveLoginCheckoutAssigned(): void
    {
        $customerUser = new CustomerUser();
        $event = $this->getEvent($customerUser);
        $this->request->request->add(['_checkout_id' => 777]);
        $this->configManager->expects(self::once())
            ->method('get')
            ->with('oro_checkout.guest_checkout')
            ->willReturn(true);

        $checkout = new Checkout();

        $this->checkoutManager->expects(self::once())
            ->method('getCheckoutById')
            ->with(777)
            ->willReturn($checkout);

        $this->logger->expects(self::never())->method('warning');

        $this->checkoutManager->expects(self::once())
            ->method('updateCheckoutCustomerUser')
            ->with($checkout, $customerUser);

        $this->eventDispatcher->expects(self::never())
            ->method('dispatch');

        $this->listener->onInteractiveLogin($event);
    }

    public function testOnInteractiveLoginDispatchEvent(): void
    {
        $customerUser = new CustomerUser();
        $interactiveLoginEvent = $this->getEvent($customerUser);
        $this->request->request->add(['_checkout_id' => 777]);
        $this->configManager->expects(self::once())
            ->method('get')
            ->with('oro_checkout.guest_checkout')
            ->willReturn(true);

        $checkout = new Checkout();
        $checkoutSource = new CheckoutSource();
        $checkout->setSource($checkoutSource);

        $this->checkoutManager->expects(self::once())
            ->method('getCheckoutById')
            ->with(777)
            ->willReturn($checkout);

        $event = new LoginOnCheckoutEvent();
        $event->setSource($checkoutSource);
        $this->eventDispatcher->expects(self::once())
            ->method('hasListeners')
            ->with(LoginOnCheckoutEvent::NAME)
            ->willReturn(true);
        $this->eventDispatcher->expects(self::once())
            ->method('dispatch')
            ->with($event, LoginOnCheckoutEvent::NAME);

        $this->listener->onInteractiveLogin($interactiveLoginEvent);
    }
}
