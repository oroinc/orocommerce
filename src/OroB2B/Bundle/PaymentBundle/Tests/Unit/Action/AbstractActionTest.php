<?php

namespace OroB2B\Bundle\PaymentBundle\Tests\Unit\Action;

use Symfony\Component\Routing\RouterInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

use Oro\Component\Action\Model\ContextAccessor;

use OroB2B\Bundle\PaymentBundle\Method\PaymentMethodRegistry;
use OroB2B\Bundle\PaymentBundle\PayPal\Payflow\Option;
use OroB2B\Bundle\PaymentBundle\Provider\PaymentTransactionProvider;
use OroB2B\Bundle\PaymentBundle\Action\AbstractPaymentMethodAction;

abstract class AbstractActionTest extends \PHPUnit_Framework_TestCase
{
    /** @var ContextAccessor|\PHPUnit_Framework_MockObject_MockObject */
    protected $contextAccessor;

    /** @var PaymentMethodRegistry|\PHPUnit_Framework_MockObject_MockObject */
    protected $paymentMethodRegistry;

    /** @var PaymentTransactionProvider|\PHPUnit_Framework_MockObject_MockObject */
    protected $paymentTransactionProvider;

    /** @var RouterInterface|\PHPUnit_Framework_MockObject_MockObject */
    protected $router;

    /** @var AbstractPaymentMethodAction */
    protected $action;

    protected function setUp()
    {
        $this->contextAccessor = $this->getMock('Oro\Component\Action\Model\ContextAccessor');

        $this->paymentMethodRegistry = $this->getMock('OroB2B\Bundle\PaymentBundle\Method\PaymentMethodRegistry');

        $this->paymentTransactionProvider = $this
            ->getMockBuilder('OroB2B\Bundle\PaymentBundle\Provider\PaymentTransactionProvider')
            ->disableOriginalConstructor()
            ->getMock();

        /** @var RouterInterface|\PHPUnit_Framework_MockObject_MockObject $router */
        $this->router = $this->getMock('Symfony\Component\Routing\RouterInterface');

        $this->action = $this->getAction();

        /** @var EventDispatcherInterface|\PHPUnit_Framework_MockObject_MockObject $dispatcher */
        $dispatcher = $this->getMockBuilder('Symfony\Component\EventDispatcher\EventDispatcher')
            ->disableOriginalConstructor()
            ->getMock();

        $this->action->setDispatcher($dispatcher);
    }

    protected function tearDown()
    {
        unset($this->action);
        unset($this->contextAccessor);
        unset($this->paymentMethodRegistry);
        unset($this->paymentTransactionProvider);
        unset($this->router);
    }

    /**
     * @return AbstractPaymentMethodAction
     */
    abstract protected function getAction();
}
