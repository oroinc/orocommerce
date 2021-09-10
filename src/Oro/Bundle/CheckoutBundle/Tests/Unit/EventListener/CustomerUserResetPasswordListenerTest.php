<?php

namespace Oro\Bundle\CheckoutBundle\Tests\Unit\EventListener;

use Oro\Bundle\CheckoutBundle\EventListener\CustomerUserResetPasswordListener;
use Oro\Bundle\CustomerBundle\Entity\CustomerUser;
use Oro\Bundle\CustomerBundle\Event\CustomerUserEmailSendEvent;
use Oro\Bundle\CustomerBundle\Mailer\Processor;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;

class CustomerUserResetPasswordListenerTest extends \PHPUnit\Framework\TestCase
{
    private CustomerUserResetPasswordListener $listener;

    private Request $request;

    private RequestStack|\PHPUnit\Framework\MockObject\MockObject $requestStack;

    /**
     * {@inheritdoc}
     */
    protected function setUp(): void
    {
        $this->request = new Request();
        $this->requestStack = $this->createMock(RequestStack::class);
        $this->listener = new CustomerUserResetPasswordListener($this->requestStack);
    }

    public function testOnCustomerUserEmailSendNoRequestParams(): void
    {
        $this->mockMasterRequest();
        $event = new CustomerUserEmailSendEvent(new CustomerUser(), 'some_template', []);
        $this->listener->onCustomerUserEmailSend($event);
        self::assertEquals('some_template', $event->getEmailTemplate());
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

    private function mockMasterRequest(): void
    {
        $this->requestStack
            ->expects(self::any())
            ->method('getMainRequest')
            ->willReturn($this->request);
    }
}
