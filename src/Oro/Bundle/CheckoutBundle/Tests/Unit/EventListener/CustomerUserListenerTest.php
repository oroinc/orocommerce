<?php

namespace Oro\Bundle\CheckoutBundle\Tests\Unit\EventListener;

use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;

use Oro\Bundle\CheckoutBundle\EventListener\CustomerUserListener;
use Oro\Bundle\CheckoutBundle\Manager\CheckoutManager;
use Oro\Bundle\ConfigBundle\Config\ConfigManager;
use Oro\Bundle\CustomerBundle\Entity\CustomerUser;
use Oro\Bundle\CustomerBundle\Manager\LoginManager;
use Oro\Bundle\FormBundle\Event\FormHandler\AfterFormProcessEvent;

class CustomerUserListenerTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var CustomerUserListener
     */
    private $listener;

    /**
     * @var LoginManager|\PHPUnit_Framework_MockObject_MockObject
     */
    private $loginManager;

    /**
     * @var Request
     */
    private $request;

    /**
     * @var ConfigManager|\PHPUnit_Framework_MockObject_MockObject
     */
    private $configManager;

    /**
     * @var CheckoutManager|\PHPUnit_Framework_MockObject_MockObject
     */
    private $checkoutManager;

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
        $this->configManager = $this->createMock(ConfigManager::class);
        $this->checkoutManager = $this->createMock(CheckoutManager::class);
        $this->listener = new CustomerUserListener(
            $requestStack,
            $this->loginManager,
            $this->configManager,
            $this->checkoutManager
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
            ->with('frontend_secure', $customerUser);
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

    public function testAfterFlushConfigurationDisabled()
    {
        $customerUser = new CustomerUser();
        $customerUser->setConfirmed(false);
        $form = $this->createMock(FormInterface::class);
        $event = new AfterFormProcessEvent($form, $customerUser);
        $this->request->request->add(['_checkout_registration' => 1]);
        $this->request->request->add(['_checkout_id' => 1]);
        $this->loginManager->expects($this->never())->method('logInUser');
        $this->checkoutManager->expects($this->never())->method('assignRegisteredCustomerUserToCheckout');
        $this->configManager->expects($this->once())
            ->method('get')
            ->with('oro_checkout.allow_checkout_without_email_confirmation')
            ->willReturn(false);

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

        $this->configManager->expects($this->once())
            ->method('get')
            ->with('oro_checkout.allow_checkout_without_email_confirmation')
            ->willReturn(true);

        $this->checkoutManager->expects($this->once())
            ->method('assignRegisteredCustomerUserToCheckout')
            ->with($customerUser, 777);

        $this->listener->afterFlush($event);
    }
}
