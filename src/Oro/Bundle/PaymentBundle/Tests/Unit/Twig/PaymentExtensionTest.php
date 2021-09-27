<?php

namespace Oro\Bundle\PaymentBundle\Tests\Unit\Twig;

use Oro\Bundle\PaymentBundle\Entity\PaymentTransaction;
use Oro\Bundle\PaymentBundle\Event\PaymentMethodConfigDataEvent;
use Oro\Bundle\PaymentBundle\Formatter\PaymentMethodLabelFormatter;
use Oro\Bundle\PaymentBundle\Formatter\PaymentMethodOptionsFormatter;
use Oro\Bundle\PaymentBundle\Formatter\PaymentStatusLabelFormatter;
use Oro\Bundle\PaymentBundle\Provider\PaymentStatusProvider;
use Oro\Bundle\PaymentBundle\Provider\PaymentTransactionProvider;
use Oro\Bundle\PaymentBundle\Twig\DTO\PaymentMethodObject;
use Oro\Bundle\PaymentBundle\Twig\PaymentExtension;
use Oro\Component\Testing\Unit\TwigExtensionTestCaseTrait;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

class PaymentExtensionTest extends \PHPUnit\Framework\TestCase
{
    use TwigExtensionTestCaseTrait;

    /** @var PaymentTransactionProvider|\PHPUnit\Framework\MockObject\MockObject */
    private $paymentTransactionProvider;

    /** @var PaymentMethodLabelFormatter|\PHPUnit\Framework\MockObject\MockObject */
    private $paymentMethodLabelFormatter;

    /** @var PaymentMethodOptionsFormatter|\PHPUnit\Framework\MockObject\MockObject */
    private $paymentMethodOptionsFormatter;

    /** @var EventDispatcherInterface|\PHPUnit\Framework\MockObject\MockObject */
    private $dispatcher;

    /** @var PaymentStatusLabelFormatter|\PHPUnit\Framework\MockObject\MockObject */
    private $paymentStatusLabelFormatter;

    /** @var PaymentStatusProvider|\PHPUnit\Framework\MockObject\MockObject */
    private $paymentStatusProvider;

    /** @var PaymentExtension */
    private $extension;

    protected function setUp(): void
    {
        $this->paymentTransactionProvider = $this->createMock(PaymentTransactionProvider::class);
        $this->paymentMethodLabelFormatter = $this->createMock(PaymentMethodLabelFormatter::class);
        $this->paymentMethodOptionsFormatter = $this->createMock(PaymentMethodOptionsFormatter::class);
        $this->dispatcher = $this->createMock(EventDispatcherInterface::class);
        $this->paymentStatusLabelFormatter = $this->createMock(PaymentStatusLabelFormatter::class);
        $this->paymentStatusProvider = $this->createMock(PaymentStatusProvider::class);

        $container = self::getContainerBuilder()
            ->add('oro_payment.provider.payment_transaction', $this->paymentTransactionProvider)
            ->add('oro_payment.formatter.payment_method_label', $this->paymentMethodLabelFormatter)
            ->add('oro_payment.formatter.payment_method_options', $this->paymentMethodOptionsFormatter)
            ->add(EventDispatcherInterface::class, $this->dispatcher)
            ->add('oro_payment.formatter.payment_status_label', $this->paymentStatusLabelFormatter)
            ->add('oro_payment.provider.payment_status', $this->paymentStatusProvider)
            ->getContainer($this);

        $this->extension = new PaymentExtension($container);
    }

    public function testGetPaymentMethods(): void
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

    public function testGetPaymentMethodConfigRenderDataDefault(): void
    {
        $methodName = 'method_1';

        $this->dispatcher->expects(self::once())
            ->method('dispatch')
            ->with(self::isInstanceOf(PaymentMethodConfigDataEvent::class), PaymentMethodConfigDataEvent::NAME)
            ->willReturnCallback(function (PaymentMethodConfigDataEvent $event) use ($methodName) {
                self::assertEquals($methodName, $event->getMethodIdentifier());
                $event->setTemplate('@OroPayment/PaymentMethodsConfigsRule/paymentMethodWithOptions.html.twig');

                return $event;
            });

        self::assertEquals(
            '@OroPayment/PaymentMethodsConfigsRule/paymentMethodWithOptions.html.twig',
            self::callTwigFunction($this->extension, 'oro_payment_method_config_template', [$methodName])
        );

        //test cache
        self::assertEquals(
            '@OroPayment/PaymentMethodsConfigsRule/paymentMethodWithOptions.html.twig',
            self::callTwigFunction($this->extension, 'oro_payment_method_config_template', [$methodName])
        );
    }

    public function testGetPaymentMethodConfigRenderData(): void
    {
        $methodName = 'method_1';
        $template = '@FooBar/template.html.twig';

        $this->dispatcher->expects(self::once())
            ->method('dispatch')
            ->with(self::isInstanceOf(PaymentMethodConfigDataEvent::class), PaymentMethodConfigDataEvent::NAME)
            ->willReturnCallback(
                function (PaymentMethodConfigDataEvent $event) use ($methodName, $template) {
                    self::assertEquals($methodName, $event->getMethodIdentifier());
                    $event->setTemplate($template);

                    return $event;
                }
            );

        self::assertEquals(
            $template,
            self::callTwigFunction($this->extension, 'oro_payment_method_config_template', [$methodName])
        );
    }

    public function testFormatPaymentStatusLabel(): void
    {
        $formattedValue = 'formattedValue';

        $this->paymentStatusLabelFormatter->expects($this->once())
            ->method('formatPaymentStatusLabel')
            ->with(PaymentStatusProvider::FULL)
            ->willReturn($formattedValue);

        $this->assertEquals(
            $formattedValue,
            self::callTwigFunction($this->extension, 'get_payment_status_label', [PaymentStatusProvider::FULL])
        );
    }

    public function testGetPaymentStatus(): void
    {
        $object = new \stdClass();
        $status = PaymentStatusProvider::FULL;

        $this->paymentStatusProvider->expects($this->once())
            ->method('getPaymentStatus')
            ->with($object)
            ->willReturn($status);

        $this->assertEquals(
            $status,
            self::callTwigFunction($this->extension, 'get_payment_status', [$object])
        );
    }
}
