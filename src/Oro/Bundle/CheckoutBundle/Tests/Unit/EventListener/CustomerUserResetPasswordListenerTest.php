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
    /**
     * @var CustomerUserResetPasswordListener
     */
    private $listener;

    /**
     * @var Request
     */
    private $request;

    /**
     * @var RequestStack|\PHPUnit\Framework\MockObject\MockObject
     */
    private $requestStack;

    /**
     * {@inheritdoc}
     */
    protected function setUp(): void
    {
        $this->request = new Request();
        $this->requestStack = $this->createMock(RequestStack::class);
        $this->listener = new CustomerUserResetPasswordListener($this->requestStack);
    }

    public function testOnCustomerUserEmailSendNoRequestParams()
    {
        $this->mockMasterRequest();
        $event = new CustomerUserEmailSendEvent(new CustomerUser(), 'some_template', []);
        $this->listener->onCustomerUserEmailSend($event);
        $this->assertEquals('some_template', $event->getEmailTemplate());
    }

    public function testOnCustomerUserEmailSendWrongTemplate()
    {
        $this->mockMasterRequest();
        $event = new CustomerUserEmailSendEvent(new CustomerUser(), 'some_template', []);
        $this->request->request->add(['_checkout_forgot_password' => 1]);
        $this->request->request->add(['_checkout_id' => 777]);
        $this->listener->onCustomerUserEmailSend($event);
        $this->assertEquals('some_template', $event->getEmailTemplate());
    }

    public function testOnCustomerUserEmailSend()
    {
        $this->mockMasterRequest();
        $event = new CustomerUserEmailSendEvent(new CustomerUser(), Processor::RESET_PASSWORD_EMAIL_TEMPLATE_NAME, []);
        $this->request->request->add(['_checkout_forgot_password' => 1]);
        $this->request->request->add(['_checkout_id' => 777]);
        $this->listener->onCustomerUserEmailSend($event);
        $this->assertSame(
            CustomerUserResetPasswordListener::CHECKOUT_RESET_PASSWORD_EMAIL_TEMPLATE_NAME,
            $event->getEmailTemplate()
        );
        $params['redirectParams'] = json_encode([
             'route' => 'oro_checkout_frontend_checkout',
             'params' => [
                 'id' => 777
             ]
         ]);
        $this->assertEquals($params, $event->getEmailTemplateParams());
    }

    private function mockMasterRequest()
    {
        $this->requestStack
            ->expects($this->any())
            ->method('getMasterRequest')
            ->willReturn($this->request);
    }
}
