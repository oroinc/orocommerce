<?php

namespace Oro\Bundle\CheckoutBundle\Tests\Unit\EventListener;

use Oro\Bundle\CheckoutBundle\EventListener\BeforeCustomerUserRegisterListener;
use Oro\Bundle\CustomerBundle\Event\BeforeCustomerUserRegisterEvent;

use Symfony\Component\HttpFoundation\Request;

class BeforeCustomerUserRegisterListenerTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var BeforeCustomerUserRegisterListener
     */
    private $listener;

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

        $this->listener = new BeforeCustomerUserRegisterListener($this->request);
    }

    public function testBeforeCustomerUserRegisterNoCheckoutId()
    {
        $event = new BeforeCustomerUserRegisterEvent();

        $this->listener->beforeCustomerUserRegister($event);
        $this->assertNull($event->getRedirect());
    }

    public function testBeforeCustomerUserRegisterNoRegistrationFlag()
    {
        $event = new BeforeCustomerUserRegisterEvent();
        $this->request->request->add(['_checkout_id' => 1]);

        $this->listener->beforeCustomerUserRegister($event);
        $this->assertNull($event->getRedirect());
    }

    public function testBeforeCustomerUserRegister()
    {
        $event = new BeforeCustomerUserRegisterEvent();
        $this->request->request->add(['_checkout_id' => 1, '_checkout_registration' => 1]);

        $this->listener->beforeCustomerUserRegister($event);
        $this->assertSame(
            [
                'route' => 'oro_checkout_frontend_checkout',
                'parameters' => ['id' => 1, 'transition' => 'back_to_billing_address']
            ],
            $event->getRedirect()
        );
    }
}
