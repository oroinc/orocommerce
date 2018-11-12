<?php

namespace Oro\Bundle\CheckoutBundle\Tests\Unit\EventListener;

use Oro\Bundle\CheckoutBundle\EventListener\CustomerUserListener;
use Oro\Bundle\CheckoutBundle\Manager\CheckoutManager;
use Oro\Bundle\ConfigBundle\Config\ConfigManager;
use Oro\Bundle\CustomerBundle\Entity\CustomerUser;
use Oro\Bundle\CustomerBundle\Event\CustomerUserEmailSendEvent;
use Oro\Bundle\CustomerBundle\Mailer\Processor;
use Oro\Bundle\CustomerBundle\Security\LoginManager;
use Oro\Bundle\FormBundle\Event\FormHandler\AfterFormProcessEvent;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;

class CustomerUserListenerTest extends \PHPUnit\Framework\TestCase
{
    const FIREWALL_NAME= 'test_firewall';

    /**
     * @var CustomerUserListener
     */
    private $listener;

    /**
     * @var LoginManager|\PHPUnit\Framework\MockObject\MockObject
     */
    private $loginManager;

    /**
     * @var Request
     */
    private $request;

    /**
     * @var CheckoutManager|\PHPUnit\Framework\MockObject\MockObject
     */
    private $checkoutManager;

    /**
     * @var ConfigManager|\PHPUnit\Framework\MockObject\MockObject
     */
    private $configManager;

    /**
     * {@inheritdoc}
     */
    protected function setUp()
    {
        $this->request = new Request();
        $requestStack = $this->createMock(RequestStack::class);
        $requestStack
            ->expects($this->any())
            ->method('getMasterRequest')
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

    public function testAfterFlushWithoutCheckoutRegistration()
    {
        $form = $this->createMock(FormInterface::class);
        $event = new AfterFormProcessEvent($form, new CustomerUser());
        $this->loginManager->expects($this->never())->method('logInUser');
        $this->listener->afterFlush($event);
    }

    public function testAfterFlushLogin()
    {
        $customerUser = new CustomerUser();
        $form = $this->createMock(FormInterface::class);
        $event = new AfterFormProcessEvent($form, $customerUser);
        $this->request->request->add(['_checkout_registration' => 1]);
        $this->loginManager->expects($this->once())
            ->method('logInUser')
            ->with(self::FIREWALL_NAME, $customerUser);
        $this->listener->afterFlush($event);
    }

    public function testAfterFlushCheckoutIdEmpty()
    {
        $customerUser = new CustomerUser();
        $customerUser->setConfirmed(false);
        $form = $this->createMock(FormInterface::class);
        $event = new AfterFormProcessEvent($form, $customerUser);
        $this->request->request->add(['_checkout_registration' => 1]);
        $this->loginManager->expects($this->never())->method('logInUser');
        $this->checkoutManager->expects($this->never())->method('assignRegisteredCustomerUserToCheckout');

        $this->listener->afterFlush($event);
    }

    public function testAfterFlushCheckoutReassigned()
    {
        $customerUser = new CustomerUser();
        $customerUser->setConfirmed(false);
        $form = $this->createMock(FormInterface::class);
        $event = new AfterFormProcessEvent($form, $customerUser);
        $this->request->request->add(['_checkout_registration' => 1]);
        $this->request->request->add(['_checkout_id' => 777]);
        $this->loginManager->expects($this->never())->method('logInUser');

        $this->checkoutManager->expects($this->once())
            ->method('assignRegisteredCustomerUserToCheckout')
            ->with($customerUser, 777);

        $this->listener->afterFlush($event);
    }

    public function testOnCustomerUserEmailSendNoRequestParams()
    {
        $event = new CustomerUserEmailSendEvent(new CustomerUser(), 'some_template', []);
        $this->configManager->expects($this->never())->method('get');
        $this->listener->onCustomerUserEmailSend($event);
        $this->assertSame('some_template', $event->getEmailTemplate());
    }

    public function testOnCustomerUserEmailSendConfigDisabled()
    {
        $event = new CustomerUserEmailSendEvent(new CustomerUser(), 'some_template', []);
        $this->request->request->add(['_checkout_registration' => 1]);
        $this->request->request->add(['_checkout_id' => 777]);
        $this->configManager->expects($this->once())
            ->method('get')
            ->with('oro_checkout.allow_checkout_without_email_confirmation')
            ->willReturn(true);
        $this->listener->onCustomerUserEmailSend($event);
        $this->assertSame('some_template', $event->getEmailTemplate());
    }

    public function testOnCustomerUserEmailSendWrongTemplate()
    {
        $event = new CustomerUserEmailSendEvent(new CustomerUser(), 'some_template', []);
        $this->request->request->add(['_checkout_registration' => 1]);
        $this->request->request->add(['_checkout_id' => 777]);
        $this->configManager->expects($this->once())
            ->method('get')
            ->with('oro_checkout.allow_checkout_without_email_confirmation')
            ->willReturn(false);
        $this->listener->onCustomerUserEmailSend($event);
        $this->assertSame('some_template', $event->getEmailTemplate());
    }

    public function testOnCustomerUserEmailSend()
    {
        $event = new CustomerUserEmailSendEvent(new CustomerUser(), Processor::CONFIRMATION_EMAIL_TEMPLATE_NAME, []);
        $this->request->request->add(['_checkout_registration' => 1]);
        $this->request->request->add(['_checkout_id' => 777]);
        $this->configManager->expects($this->once())
            ->method('get')
            ->with('oro_checkout.allow_checkout_without_email_confirmation')
            ->willReturn(false);
        $this->listener->onCustomerUserEmailSend($event);
        $this->assertSame('checkout_registration_confirmation', $event->getEmailTemplate());
        $params['redirectParams'] =  json_encode([
            'route' => 'oro_checkout_frontend_checkout',
            'params' => [
                'id' => 777,
                'transition' => 'continue_checkout_as_registered_user'
            ]
        ]);
        $this->assertSame($params, $event->getEmailTemplateParams());
    }
}
