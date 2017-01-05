<?php

namespace Oro\Bundle\PaymentBundle\Tests\Unit\Action;

use Oro\Bundle\PaymentBundle\Action\AbstractPaymentMethodAction;
use Oro\Bundle\PaymentBundle\Method\PaymentMethodProvidersRegistry;

use Oro\Bundle\PaymentBundle\Provider\PaymentTransactionProvider;
use Oro\Component\ConfigExpression\ContextAccessor;

use Psr\Log\LoggerInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\Routing\RouterInterface;

abstract class AbstractActionTest extends \PHPUnit_Framework_TestCase
{
    /** @var ContextAccessor|\PHPUnit_Framework_MockObject_MockObject */
    protected $contextAccessor;

    /** @var PaymentMethodProvidersRegistry|\PHPUnit_Framework_MockObject_MockObject */
    protected $paymentMethodProvidersRegistry;

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
        $this->contextAccessor = $this->createMock('Oro\Component\ConfigExpression\ContextAccessor');

        $this->paymentMethodProvidersRegistry = $this->createMock(PaymentMethodProvidersRegistry::class);

        $this->paymentTransactionProvider = $this
            ->getMockBuilder('Oro\Bundle\PaymentBundle\Provider\PaymentTransactionProvider')
            ->disableOriginalConstructor()
            ->getMock();

        $this->router = $this->createMock('Symfony\Component\Routing\RouterInterface');

        $this->action = $this->getAction();

        $this->logger = $this->createMock('Psr\Log\LoggerInterface');
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
            $this->paymentMethodProvidersRegistry,
            $this->paymentTransactionProvider,
            $this->router
        );
    }

    /**
     * @return AbstractPaymentMethodAction
     */
    abstract protected function getAction();
}
