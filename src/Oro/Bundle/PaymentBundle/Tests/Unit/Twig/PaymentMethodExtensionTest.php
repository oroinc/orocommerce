<?php

namespace Oro\Bundle\PaymentBundle\Tests\Unit\Twig;

use Symfony\Component\EventDispatcher\EventDispatcherInterface;

use Oro\Bundle\PaymentBundle\Entity\PaymentTransaction;
use Oro\Bundle\PaymentBundle\Event\PaymentMethodConfigDataEvent;
use Oro\Bundle\PaymentBundle\Formatter\PaymentMethodLabelFormatter;
use Oro\Bundle\PaymentBundle\Provider\PaymentTransactionProvider;
use Oro\Bundle\PaymentBundle\Twig\PaymentMethodExtension;
use Oro\Component\Testing\Unit\TwigExtensionTestCaseTrait;

class PaymentMethodExtensionTest extends \PHPUnit_Framework_TestCase
{
    use TwigExtensionTestCaseTrait;

    /** @var PaymentTransactionProvider|\PHPUnit_Framework_MockObject_MockObject */
    protected $paymentTransactionProvider;

    /** @var PaymentMethodLabelFormatter|\PHPUnit_Framework_MockObject_MockObject */
    protected $paymentMethodLabelFormatter;

    /** @var EventDispatcherInterface|\PHPUnit_Framework_MockObject_MockObject */
    protected $dispatcher;

    /** @var PaymentMethodExtension */
    protected $extension;

    public function setUp()
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

        $container = self::getContainerBuilder()
            ->add('oro_payment.provider.payment_transaction', $this->paymentTransactionProvider)
            ->add('oro_payment.formatter.payment_method_label', $this->paymentMethodLabelFormatter)
            ->add('event_dispatcher', $this->dispatcher)
            ->getContainer($this);

        $this->extension = new PaymentMethodExtension($container);
    }

    public function testGetPaymentMethods()
    {
        $entity = new \stdClass();
        $label = 'label';
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

        $this->assertEquals(
            [$label],
            self::callTwigFunction($this->extension, 'get_payment_methods', [$entity])
        );
    }

    public function testGetPaymentMethodConfigRenderDataDefault()
    {
        $methodName = 'method_1';

        $this->dispatcher->expects(static::once())
            ->method('dispatch')
            ->with(PaymentMethodConfigDataEvent::NAME)
            ->willReturnCallback(function ($name, PaymentMethodConfigDataEvent $event) use ($methodName) {
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
            ->with(PaymentMethodConfigDataEvent::NAME)
            ->willReturnCallback(
                function ($name, PaymentMethodConfigDataEvent $event) use ($methodName, $template) {
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
