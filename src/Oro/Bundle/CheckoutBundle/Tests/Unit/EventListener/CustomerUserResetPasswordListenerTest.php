<?php

namespace Oro\Bundle\CheckoutBundle\Tests\Unit\EventListener;

use Oro\Bundle\CheckoutBundle\EventListener\CustomerUserResetPasswordListener;
use Oro\Bundle\CustomerBundle\Async\PasswordResetRequestContext;
use Oro\Bundle\CustomerBundle\Entity\CustomerUser;
use Oro\Bundle\CustomerBundle\Event\CustomerUserEmailSendEvent;
use Oro\Bundle\CustomerBundle\Event\PasswordResetRequestContextCollectEvent;
use Oro\Bundle\CustomerBundle\Mailer\Processor;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;

class CustomerUserResetPasswordListenerTest extends \PHPUnit\Framework\TestCase
{
    private CustomerUserResetPasswordListener $listener;

    private Request $request;

    private RequestStack|\PHPUnit\Framework\MockObject\MockObject $requestStack;

    private PasswordResetRequestContext $passwordResetRequestContext;

    /**
     * {@inheritdoc}
     */
    protected function setUp(): void
    {
        $this->request = new Request();
        $this->requestStack = $this->createMock(RequestStack::class);
        $this->passwordResetRequestContext = new PasswordResetRequestContext();
        $this->listener = new CustomerUserResetPasswordListener($this->requestStack);
        $this->listener->setPasswordResetRequestContext($this->passwordResetRequestContext);
    }

    public function testOnCustomerUserEmailSendNoRequestParams(): void
    {
        $this->mockMasterRequest();
        $event = new CustomerUserEmailSendEvent(new CustomerUser(), 'some_template', []);
        $this->listener->onCustomerUserEmailSend($event);
        self::assertEquals('some_template', $event->getEmailTemplate());
    }

    public function testOnCustomerUserEmailSendWhenNoRequest(): void
    {
        $this->requestStack->expects(self::any())
            ->method('getMainRequest')
            ->willReturn(null);
        $event = new CustomerUserEmailSendEvent(
            new CustomerUser(),
            Processor::RESET_PASSWORD_EMAIL_TEMPLATE_NAME,
            []
        );
        $this->listener->onCustomerUserEmailSend($event);
        self::assertEquals(Processor::RESET_PASSWORD_EMAIL_TEMPLATE_NAME, $event->getEmailTemplate());
        self::assertEquals([], $event->getEmailTemplateParams());
    }

    public function testOnCustomerUserEmailSendWrongTemplate(): void
    {
        $this->mockMasterRequest();
        $event = new CustomerUserEmailSendEvent(new CustomerUser(), 'some_template', []);
        $this->request->request->add(['_checkout_forgot_password' => 1]);
        $this->request->request->add(['_checkout_id' => 777]);
        $this->listener->onCustomerUserEmailSend($event);
        self::assertEquals('some_template', $event->getEmailTemplate());
    }

    public function testOnCustomerUserEmailSend(): void
    {
        $this->mockMasterRequest();
        $event = new CustomerUserEmailSendEvent(new CustomerUser(), Processor::RESET_PASSWORD_EMAIL_TEMPLATE_NAME, []);
        $this->request->request->add(['_checkout_forgot_password' => 1]);
        $this->request->request->add(['_checkout_id' => 777]);
        $this->listener->onCustomerUserEmailSend($event);
        self::assertSame(
            CustomerUserResetPasswordListener::CHECKOUT_RESET_PASSWORD_EMAIL_TEMPLATE_NAME,
            $event->getEmailTemplate()
        );
        $params['redirectParams'] = json_encode([
             'route' => 'oro_checkout_frontend_checkout',
             'params' => [
                 'id' => 777
             ]
         ]);
        self::assertEquals($params, $event->getEmailTemplateParams());
    }

    public function testOnCustomerUserEmailSendWhenNoRequestButCheckoutParamsInContext(): void
    {
        $this->requestStack->expects(self::any())
            ->method('getMainRequest')
            ->willReturn(null);
        $this->passwordResetRequestContext->setRequestParameters([
            '_checkout_forgot_password' => '1',
            '_checkout_id' => '777',
        ]);

        $event = new CustomerUserEmailSendEvent(new CustomerUser(), Processor::RESET_PASSWORD_EMAIL_TEMPLATE_NAME, []);
        $this->listener->onCustomerUserEmailSend($event);

        self::assertSame(
            CustomerUserResetPasswordListener::CHECKOUT_RESET_PASSWORD_EMAIL_TEMPLATE_NAME,
            $event->getEmailTemplate()
        );
        self::assertEquals(
            [
                'redirectParams' => json_encode([
                    'route' => 'oro_checkout_frontend_checkout',
                    'params' => [
                        'id' => '777'
                    ]
                ])
            ],
            $event->getEmailTemplateParams()
        );
    }

    public function testOnPasswordResetRequestContextCollect(): void
    {
        $this->request->request->add(
            ['_checkout_forgot_password' => '1', '_checkout_id' => '777', '_token' => 'test']
        );
        $event = new PasswordResetRequestContextCollectEvent($this->request);

        $this->listener->onPasswordResetRequestContextCollect($event);

        self::assertEquals(
            ['_checkout_forgot_password' => '1', '_checkout_id' => '777'],
            $event->getRequestParameters()
        );
    }

    public function testOnPasswordResetRequestContextCollectWhenNoCheckoutParams(): void
    {
        $event = new PasswordResetRequestContextCollectEvent($this->request);

        $this->listener->onPasswordResetRequestContextCollect($event);

        self::assertSame([], $event->getRequestParameters());
    }

    private function mockMasterRequest(): void
    {
        $this->requestStack
            ->expects(self::any())
            ->method('getMainRequest')
            ->willReturn($this->request);
    }
}
