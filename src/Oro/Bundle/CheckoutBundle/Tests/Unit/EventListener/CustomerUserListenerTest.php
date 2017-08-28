<?php

namespace Oro\Bundle\CheckoutBundle\Tests\Unit\EventListener;

use Oro\Bundle\CheckoutBundle\EventListener\CustomerUserRegisterListener;
use Oro\Bundle\CheckoutBundle\Manager\CheckoutManager;
use Oro\Bundle\ConfigBundle\Config\ConfigManager;
use Oro\Bundle\CustomerBundle\Entity\CustomerUser;
use Oro\Bundle\CustomerBundle\Event\CustomerUserRegisterEvent;
use Oro\Bundle\CustomerBundle\Manager\LoginManager;

use Symfony\Component\HttpFoundation\Request;

class CustomerUserListenerTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var CustomerUserRegisterListener
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
        $this->loginManager = $this->createMock(LoginManager::class);
        $this->configManager = $this->createMock(ConfigManager::class);
        $this->checkoutManager = $this->createMock(CheckoutManager::class);
        $this->listener = new CustomerUserRegisterListener(
            $this->request,
            $this->loginManager,
            $this->configManager,
            $this->checkoutManager
        );
    }

    public function testOnCustomerUserRegisterWithoutCheckoutRegistration()
    {
        $event = new CustomerUserRegisterEvent(new CustomerUser());
        $this->loginManager->expects($this->never())->method('logInUser');
        $this->listener->onCustomerUserRegister($event);
    }

    public function testOnCustomerUserRegisterLogin()
    {
        $customerUser = new CustomerUser();
        $event = new CustomerUserRegisterEvent($customerUser);
        $this->request->request->add(['_checkout_registration' => 1]);
        $this->loginManager->expects($this->once())
            ->method('logInUser')
            ->with('frontend_secure', $customerUser);
        $this->listener->onCustomerUserRegister($event);
    }

    public function testOnCustomerUserRegisterCheckoutIdEmpty()
    {
        $customerUser = new CustomerUser();
        $customerUser->setConfirmed(false);
        $event = new CustomerUserRegisterEvent($customerUser);
        $this->request->request->add(['_checkout_registration' => 1]);
        $this->loginManager->expects($this->never())->method('logInUser');
        $this->checkoutManager->expects($this->never())->method('assignRegisteredCustomerUserToCheckout');

        $this->listener->onCustomerUserRegister($event);
    }

    public function testOnCustomerUserRegisterConfigurationDisabled()
    {
        $customerUser = new CustomerUser();
        $customerUser->setConfirmed(false);
        $event = new CustomerUserRegisterEvent($customerUser);
        $this->request->request->add(['_checkout_registration' => 1]);
        $this->request->request->add(['_checkout_id' => 1]);
        $this->loginManager->expects($this->never())->method('logInUser');
        $this->checkoutManager->expects($this->never())->method('assignRegisteredCustomerUserToCheckout');
        $this->configManager->expects($this->once())
            ->method('get')
            ->with('oro_checkout.allow_checkout_without_email_confirmation')
            ->willReturn(false);

        $this->listener->onCustomerUserRegister($event);
    }

    public function testOnCustomerUserRegisterCheckoutReassigned()
    {
        $customerUser = new CustomerUser();
        $customerUser->setConfirmed(false);
        $event = new CustomerUserRegisterEvent($customerUser);
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

        $this->listener->onCustomerUserRegister($event);
    }
}
