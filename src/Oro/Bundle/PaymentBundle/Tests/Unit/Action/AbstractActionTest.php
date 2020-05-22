<?php

namespace Oro\Bundle\PaymentBundle\Tests\Unit\Action;

use Oro\Bundle\PaymentBundle\Action\AbstractPaymentMethodAction;
use Oro\Bundle\PaymentBundle\Method\Provider\PaymentMethodProviderInterface;
use Oro\Bundle\PaymentBundle\Provider\PaymentTransactionProvider;
use Oro\Component\ConfigExpression\ContextAccessor;
use Psr\Log\LoggerInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\Routing\RouterInterface;

abstract class AbstractActionTest extends \PHPUnit\Framework\TestCase
{
    /** @var ContextAccessor|\PHPUnit\Framework\MockObject\MockObject */
    protected $contextAccessor;

    /** @var PaymentMethodProviderInterface|\PHPUnit\Framework\MockObject\MockObject */
    protected $paymentMethodProvider;

    /** @var PaymentTransactionProvider|\PHPUnit\Framework\MockObject\MockObject */
    protected $paymentTransactionProvider;

    /** @var RouterInterface|\PHPUnit\Framework\MockObject\MockObject */
    protected $router;

    /** @var AbstractPaymentMethodAction */
    protected $action;

    /** @var EventDispatcherInterface|\PHPUnit\Framework\MockObject\MockObject $dispatcher */
    protected $dispatcher;

    /** @var LoggerInterface|\PHPUnit\Framework\MockObject\MockObject $dispatcher */
    protected $logger;

    protected function setUp(): void
    {
        $this->contextAccessor = $this->createMock('Oro\Component\ConfigExpression\ContextAccessor');

        $this->paymentMethodProvider = $this->createMock(PaymentMethodProviderInterface::class);

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

    /**
     * @return AbstractPaymentMethodAction
     */
    abstract protected function getAction();
}
