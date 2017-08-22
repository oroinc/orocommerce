<?php

namespace Oro\Bundle\CheckoutBundle\Tests\Unit\EventListener;

use Oro\Bundle\CheckoutBundle\EventListener\CustomerUserRegisterListener;
use Oro\Bundle\CustomerBundle\Entity\CustomerUser;
use Oro\Bundle\CustomerBundle\Event\CustomerUserRegisterEvent;
use Oro\Bundle\CustomerBundle\Manager\LoginManager;

use Symfony\Component\HttpFoundation\Request;

class CustomerUserRegisterListenerTest extends \PHPUnit_Framework_TestCase
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
     * {@inheritdoc}
     */
    protected function setUp()
    {
        $this->request = new Request();
        $this->loginManager = $this->createMock(LoginManager::class);
        $this->listener = new CustomerUserRegisterListener($this->request, $this->loginManager);
    }

    public function testOnCustomerUserRegisterWithoutCheckoutRegistration()
    {
        $event = new CustomerUserRegisterEvent(new CustomerUser());
        $this->loginManager->expects($this->never())->method('logInUser');
        $this->listener->onCustomerUserRegister($event);
    }

    public function testOnCustomerUserRegister()
    {
        $customerUser = new CustomerUser();
        $event = new CustomerUserRegisterEvent($customerUser);
        $this->request->request->add(['_checkout_registration' => 1]);
        $this->loginManager->expects($this->once())
            ->method('logInUser')
            ->with('frontend_secure', $customerUser);
        $this->listener->onCustomerUserRegister($event);
    }
}
