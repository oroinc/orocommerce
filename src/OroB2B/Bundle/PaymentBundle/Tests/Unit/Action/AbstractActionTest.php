<?php

namespace OroB2B\Bundle\PaymentBundle\Tests\Unit\Action;

use Psr\Log\LoggerInterface;

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

    /** @var EventDispatcherInterface|\PHPUnit_Framework_MockObject_MockObject $dispatcher */
    protected $dispatcher;

    /** @var LoggerInterface|\PHPUnit_Framework_MockObject_MockObject $dispatcher */
    protected $logger;

    protected function setUp()
    {
        $this->contextAccessor = $this->getMock('Oro\Component\Action\Model\ContextAccessor');

        $this->paymentMethodRegistry = $this->getMock('OroB2B\Bundle\PaymentBundle\Method\PaymentMethodRegistry');

        $this->paymentTransactionProvider = $this
            ->getMockBuilder('OroB2B\Bundle\PaymentBundle\Provider\PaymentTransactionProvider')
            ->disableOriginalConstructor()
            ->getMock();

        $this->router = $this->getMock('Symfony\Component\Routing\RouterInterface');

        $this->action = $this->getAction();

        $this->logger = $this->getMock('Psr\Log\LoggerInterface');
        $this->action->setLogger($this->logger);

        $this->dispatcher = $this->getMockBuilder('Symfony\Component\EventDispatcher\EventDispatcherInterface')
            ->disableOriginalConstructor()
            ->getMock();

        $this->action->setDispatcher($this->dispatcher);
    }

    protected function tearDown()
    {
        unset(
            $this->action,
            $this->dispatcher,
            $this->contextAccessor,
            $this->paymentMethodRegistry,
            $this->paymentTransactionProvider,
            $this->router
        );
    }

    /**
     * @return AbstractPaymentMethodAction
     */
    abstract protected function getAction();
}
