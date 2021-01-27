<?php

namespace Oro\Bundle\PaymentBundle\Tests\Unit\Twig;

use Oro\Bundle\PaymentBundle\Entity\PaymentTransaction;
use Oro\Bundle\PaymentBundle\Event\PaymentMethodConfigDataEvent;
use Oro\Bundle\PaymentBundle\Formatter\PaymentMethodLabelFormatter;
use Oro\Bundle\PaymentBundle\Formatter\PaymentMethodOptionsFormatter;
use Oro\Bundle\PaymentBundle\Provider\PaymentTransactionProvider;
use Oro\Bundle\PaymentBundle\Twig\DTO\PaymentMethodObject;
use Oro\Bundle\PaymentBundle\Twig\PaymentMethodExtension;
use Oro\Component\Testing\Unit\TwigExtensionTestCaseTrait;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

class PaymentMethodExtensionTest extends \PHPUnit\Framework\TestCase
{
    use TwigExtensionTestCaseTrait;

    /** @var PaymentTransactionProvider|\PHPUnit\Framework\MockObject\MockObject */
    protected $paymentTransactionProvider;

    /** @var PaymentMethodLabelFormatter|\PHPUnit\Framework\MockObject\MockObject */
    protected $paymentMethodLabelFormatter;

    /** @var EventDispatcherInterface|\PHPUnit\Framework\MockObject\MockObject */
    protected $dispatcher;

    /** @var PaymentMethodOptionsFormatter|\PHPUnit\Framework\MockObject\MockObject */
    protected $paymentMethodOptionsFormatter;

    /** @var PaymentMethodExtension */
    protected $extension;

    protected function setUp(): void
    {
        $this->paymentTransactionProvider = $this->getMockBuilder(PaymentTransactionProvider::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->paymentMethodLabelFormatter = $this->getMockBuilder(PaymentMethodLabelFormatter::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->dispatcher = $this->getMockBuilder(EventDispatcherInterface::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->paymentMethodOptionsFormatter = $this->createMock(PaymentMethodOptionsFormatter::class);

        $container = self::getContainerBuilder()
            ->add('oro_payment.provider.payment_transaction', $this->paymentTransactionProvider)
            ->add('oro_payment.formatter.payment_method_label', $this->paymentMethodLabelFormatter)
            ->add('oro_payment.formatter.payment_method_options', $this->paymentMethodOptionsFormatter)
            ->add('event_dispatcher', $this->dispatcher)
            ->getContainer($this);

        $this->extension = new PaymentMethodExtension($container);
    }

    public function testGetPaymentMethods()
    {
        $entity = new \stdClass();
        $label = 'label';
        $option = 'some option';
        $paymentMethodConstant = 'payment_method';
        $paymentTransaction = new PaymentTransaction();
        $paymentTransaction->setPaymentMethod($paymentMethodConstant);

        $this->paymentTransactionProvider->expects($this->once())
            ->method('getPaymentTransactions')
            ->with(self::identicalTo($entity))
            ->willReturn([$paymentTransaction]);
        $this->paymentMethodLabelFormatter->expects($this->once())
            ->method('formatPaymentMethodLabel')
            ->with($paymentMethodConstant, false)
            ->willReturn($label);
        $this->paymentMethodOptionsFormatter->expects($this->once())
            ->method('formatPaymentMethodOptions')
            ->with($paymentMethodConstant)
            ->willReturn([$option]);

        $this->assertEquals(
            [new PaymentMethodObject($label, [$option])],
            self::callTwigFunction($this->extension, 'get_payment_methods', [$entity])
        );
    }

    public function testGetPaymentMethodConfigRenderDataDefault()
    {
        $methodName = 'method_1';

        $this->dispatcher->expects(static::once())
            ->method('dispatch')
            ->with(static::isInstanceOf(PaymentMethodConfigDataEvent::class), PaymentMethodConfigDataEvent::NAME)
            ->willReturnCallback(function (PaymentMethodConfigDataEvent $event, $name) use ($methodName) {
                static::assertEquals($methodName, $event->getMethodIdentifier());
                $event->setTemplate(PaymentMethodExtension::DEFAULT_METHOD_CONFIG_TEMPLATE);
            });

        self::assertEquals(
            PaymentMethodExtension::DEFAULT_METHOD_CONFIG_TEMPLATE,
            self::callTwigFunction($this->extension, 'oro_payment_method_config_template', [$methodName])
        );

        //test cache
        self::assertEquals(
            PaymentMethodExtension::DEFAULT_METHOD_CONFIG_TEMPLATE,
            self::callTwigFunction($this->extension, 'oro_payment_method_config_template', [$methodName])
        );
    }

    public function testGetPaymentMethodConfigRenderData()
    {
        $methodName = 'method_1';
        $template = 'Bundle:template.html.twig';

        $this->dispatcher->expects(static::once())
            ->method('dispatch')
            ->with(static::isInstanceOf(PaymentMethodConfigDataEvent::class), PaymentMethodConfigDataEvent::NAME)
            ->willReturnCallback(
                function (PaymentMethodConfigDataEvent $event, $name) use ($methodName, $template) {
                    static::assertEquals($methodName, $event->getMethodIdentifier());
                    $event->setTemplate($template);
                }
            );

        self::assertEquals(
            $template,
            self::callTwigFunction($this->extension, 'oro_payment_method_config_template', [$methodName])
        );
    }
}
