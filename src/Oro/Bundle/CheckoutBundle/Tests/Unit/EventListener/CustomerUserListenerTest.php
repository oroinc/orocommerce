<?php

namespace Oro\Bundle\CheckoutBundle\Tests\Unit\EventListener;

use Oro\Bundle\CheckoutBundle\Entity\Checkout;
use Oro\Bundle\CheckoutBundle\EventListener\CustomerUserListener;
use Oro\Bundle\CheckoutBundle\Manager\CheckoutManager;
use Oro\Bundle\CheckoutBundle\Tests\Unit\Model\Action\CheckoutSourceStub;
use Oro\Bundle\ConfigBundle\Config\ConfigManager;
use Oro\Bundle\CustomerBundle\Entity\CustomerUser;
use Oro\Bundle\CustomerBundle\Entity\CustomerVisitor;
use Oro\Bundle\CustomerBundle\Event\CustomerUserEmailSendEvent;
use Oro\Bundle\CustomerBundle\Mailer\Processor;
use Oro\Bundle\CustomerBundle\Security\Firewall\AnonymousCustomerUserAuthenticationListener;
use Oro\Bundle\CustomerBundle\Security\LoginManager;
use Oro\Bundle\FormBundle\Event\FormHandler\AfterFormProcessEvent;
use Oro\Bundle\ShoppingListBundle\Tests\Unit\Entity\Stub\ShoppingListStub;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;

class CustomerUserListenerTest extends \PHPUnit\Framework\TestCase
{
    const FIREWALL_NAME= 'test_firewall';

    private CustomerUserListener $listener;

    private LoginManager|\PHPUnit\Framework\MockObject\MockObject $loginManager;

    private Request $request;

    private CheckoutManager|\PHPUnit\Framework\MockObject\MockObject $checkoutManager;

    private ConfigManager|\PHPUnit\Framework\MockObject\MockObject $configManager;

    /**
     * {@inheritdoc}
     */
    protected function setUp(): void
    {
        $this->request = new Request();
        $requestStack = $this->createMock(RequestStack::class);
        $requestStack
            ->expects(self::any())
            ->method('getMainRequest')
            ->willReturn($this->request);
        $this->loginManager = $this->createMock(LoginManager::class);
        $this->checkoutManager = $this->createMock(CheckoutManager::class);
        $this->configManager = $this->createMock(ConfigManager::class);
        $this->listener = new CustomerUserListener(
            $requestStack,
            $this->checkoutManager,
            $this->configManager,
            $this->loginManager,
            self::FIREWALL_NAME
        );
    }

    public function testAfterFlushWithoutCheckoutRegistration(): void
    {
        $form = $this->createMock(FormInterface::class);
        $event = new AfterFormProcessEvent($form, new CustomerUser());
        $this->loginManager->expects(self::never())->method('logInUser');
        $this->listener->afterFlush($event);
    }

    public function testAfterFlushLogin(): void
    {
        $customerUser = new CustomerUser();
        $form = $this->createMock(FormInterface::class);
        $event = new AfterFormProcessEvent($form, $customerUser);
        $this->request->request->add(['_checkout_registration' => 1]);
        $this->loginManager->expects(self::once())
            ->method('logInUser')
            ->with(self::FIREWALL_NAME, $customerUser);
        $this->listener->afterFlush($event);
    }

    public function testAfterFlushCheckoutIdEmpty(): void
    {
        $customerUser = new CustomerUser();
        $customerUser->setConfirmed(false);
        $form = $this->createMock(FormInterface::class);
        $event = new AfterFormProcessEvent($form, $customerUser);
        $this->request->request->add(['_checkout_registration' => 1]);
        $this->loginManager->expects(self::never())->method('logInUser');
        $this->checkoutManager->expects(self::never())->method('assignRegisteredCustomerUserToCheckout');

        $this->listener->afterFlush($event);
    }

    public function testAfterFlushCheckoutReassigned(): void
    {
        $customerUser = new CustomerUser();
        $customerUser->setConfirmed(false);
        $form = $this->createMock(FormInterface::class);
        $event = new AfterFormProcessEvent($form, $customerUser);
        $this->request->request->add(['_checkout_registration' => 1]);
        $this->request->request->add(['_checkout_id' => 777]);
        $this->request->cookies->set(
            AnonymousCustomerUserAuthenticationListener::COOKIE_NAME,
            base64_encode(json_encode([1, 'visitor_session_1'], JSON_THROW_ON_ERROR))
        );
        $this->loginManager->expects(self::never())->method('logInUser');

        $shoppingList = new ShoppingListStub();
        $shoppingList->addVisitor((new CustomerVisitor())->setSessionId('visitor_session_1'));

        $this->checkoutManager->expects(self::once())
            ->method('getCheckoutById')
            ->with(777)
            ->willReturn(
                (new Checkout())->setSource((new CheckoutSourceStub())->setShoppingList($shoppingList))
            );

        $this->checkoutManager->expects(self::once())
            ->method('assignRegisteredCustomerUserToCheckout')
            ->with($customerUser, 777);

        $this->listener->afterFlush($event);
    }

    /**
     * @dataProvider notOwnCheckoutDataProvider
     */
    public function testAfterFlushCheckoutOfAnotherVisitorIsNotReassigned(
        ?Checkout $checkout,
        mixed $visitorCookie
    ): void {
        $customerUser = new CustomerUser();
        $customerUser->setConfirmed(false);
        $form = $this->createMock(FormInterface::class);
        $event = new AfterFormProcessEvent($form, $customerUser);
        $this->request->request->add(['_checkout_registration' => 1]);
        $this->request->request->add(['_checkout_id' => 777]);
        if (null !== $visitorCookie) {
            $this->request->cookies->set(
                AnonymousCustomerUserAuthenticationListener::COOKIE_NAME,
                base64_encode(json_encode($visitorCookie, JSON_THROW_ON_ERROR))
            );
        }

        $this->checkoutManager->expects(self::once())
            ->method('getCheckoutById')
            ->with(777)
            ->willReturn($checkout);

        $this->checkoutManager->expects(self::never())
            ->method('assignRegisteredCustomerUserToCheckout');

        $this->listener->afterFlush($event);
    }

    public function notOwnCheckoutDataProvider(): array
    {
        $shoppingListOfVisitor1 = new ShoppingListStub();
        $shoppingListOfVisitor1->addVisitor((new CustomerVisitor())->setSessionId('visitor_session_1'));
        $checkoutOfVisitor1 = (new Checkout())
            ->setSource((new CheckoutSourceStub())->setShoppingList($shoppingListOfVisitor1));

        $checkoutWithoutVisitor = (new Checkout())
            ->setSource((new CheckoutSourceStub())->setShoppingList(new ShoppingListStub()));

        return [
            'checkout of another visitor' => [$checkoutOfVisitor1, [2, 'visitor_session_2']],
            'checkout without a visitor' => [$checkoutWithoutVisitor, [1, 'visitor_session_1']],
            'request without a visitor' => [$checkoutOfVisitor1, null],
            'checkout not found' => [null, [1, 'visitor_session_1']],
            'cookie holding a bare session id' => [$checkoutOfVisitor1, 'visitor_session_1'],
        ];
    }

    public function testOnCustomerUserEmailSendNoRequestParams(): void
    {
        $event = new CustomerUserEmailSendEvent(new CustomerUser(), 'some_template', []);
        $this->configManager->expects(self::never())->method('get');
        $this->listener->onCustomerUserEmailSend($event);
        self::assertSame('some_template', $event->getEmailTemplate());
    }

    public function testOnCustomerUserEmailSendConfigDisabled(): void
    {
        $event = new CustomerUserEmailSendEvent(new CustomerUser(), 'some_template', []);
        $this->request->request->add(['_checkout_registration' => 1]);
        $this->request->request->add(['_checkout_id' => 777]);
        $this->configManager->expects(self::once())
            ->method('get')
            ->with('oro_checkout.allow_checkout_without_email_confirmation')
            ->willReturn(true);
        $this->listener->onCustomerUserEmailSend($event);
        self::assertSame('some_template', $event->getEmailTemplate());
    }

    public function testOnCustomerUserEmailSendWrongTemplate(): void
    {
        $event = new CustomerUserEmailSendEvent(new CustomerUser(), 'some_template', []);
        $this->request->request->add(['_checkout_registration' => 1]);
        $this->request->request->add(['_checkout_id' => 777]);
        $this->configManager->expects(self::once())
            ->method('get')
            ->with('oro_checkout.allow_checkout_without_email_confirmation')
            ->willReturn(false);
        $this->listener->onCustomerUserEmailSend($event);
        self::assertSame('some_template', $event->getEmailTemplate());
    }

    public function testOnCustomerUserEmailSend(): void
    {
        $event = new CustomerUserEmailSendEvent(new CustomerUser(), Processor::CONFIRMATION_EMAIL_TEMPLATE_NAME, []);
        $this->request->request->add(['_checkout_registration' => 1]);
        $this->request->request->add(['_checkout_id' => 777]);
        $this->configManager->expects(self::once())
            ->method('get')
            ->with('oro_checkout.allow_checkout_without_email_confirmation')
            ->willReturn(false);
        $this->listener->onCustomerUserEmailSend($event);
        self::assertSame('checkout_registration_confirmation', $event->getEmailTemplate());
        $params['redirectParams'] =  json_encode([
            'route' => 'oro_checkout_frontend_checkout',
            'params' => [
                'id' => 777,
                'transition' => 'back_to_billing_address'
            ]
        ]);
        self::assertSame($params, $event->getEmailTemplateParams());
    }
}
