<?php

declare(strict_types=1);

namespace Oro\Bundle\PaymentBundle\Tests\Unit\Twig;

use Oro\Bundle\PaymentBundle\Entity\PaymentStatus;
use Oro\Bundle\PaymentBundle\Entity\PaymentTransaction;
use Oro\Bundle\PaymentBundle\Event\PaymentMethodConfigDataEvent;
use Oro\Bundle\PaymentBundle\Formatter\PaymentMethodLabelFormatter;
use Oro\Bundle\PaymentBundle\Formatter\PaymentMethodOptionsFormatter;
use Oro\Bundle\PaymentBundle\Formatter\PaymentStatusLabelFormatter;
use Oro\Bundle\PaymentBundle\Manager\PaymentStatusManager;
use Oro\Bundle\PaymentBundle\PaymentStatus\PaymentStatuses;
use Oro\Bundle\PaymentBundle\Provider\PaymentTransactionProvider;
use Oro\Bundle\PaymentBundle\Twig\DTO\PaymentMethodObject;
use Oro\Bundle\PaymentBundle\Twig\PaymentExtension;
use Oro\Component\Testing\Unit\TwigExtensionTestCaseTrait;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

final class PaymentExtensionTest extends TestCase
{
    use TwigExtensionTestCaseTrait;

    private PaymentTransactionProvider&MockObject $paymentTransactionProvider;
    private PaymentMethodLabelFormatter&MockObject $paymentMethodLabelFormatter;
    private PaymentMethodOptionsFormatter&MockObject $paymentMethodOptionsFormatter;
    private PaymentStatusLabelFormatter&MockObject $paymentStatusLabelFormatter;
    private PaymentStatusManager&MockObject $paymentStatusManager;
    private EventDispatcherInterface&MockObject $dispatcher;
    private PaymentExtension $extension;

    #[\Override]
    protected function setUp(): void
    {
        $this->paymentTransactionProvider = $this->createMock(PaymentTransactionProvider::class);
        $this->paymentMethodLabelFormatter = $this->createMock(PaymentMethodLabelFormatter::class);
        $this->paymentMethodOptionsFormatter = $this->createMock(PaymentMethodOptionsFormatter::class);
        $this->paymentStatusLabelFormatter = $this->createMock(PaymentStatusLabelFormatter::class);
        $this->paymentStatusManager = $this->createMock(PaymentStatusManager::class);
        $this->dispatcher = $this->createMock(EventDispatcherInterface::class);

        $container = self::getContainerBuilder()
            ->add(PaymentTransactionProvider::class, $this->paymentTransactionProvider)
            ->add(PaymentMethodLabelFormatter::class, $this->paymentMethodLabelFormatter)
            ->add(PaymentMethodOptionsFormatter::class, $this->paymentMethodOptionsFormatter)
            ->add(PaymentStatusLabelFormatter::class, $this->paymentStatusLabelFormatter)
            ->add(PaymentStatusManager::class, $this->paymentStatusManager)
            ->add(EventDispatcherInterface::class, $this->dispatcher)
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

        $this->paymentTransactionProvider->expects(self::once())
            ->method('getPaymentTransactions')
            ->with(self::identicalTo($entity))
            ->willReturn([$paymentTransaction]);
        $this->paymentMethodLabelFormatter->expects(self::once())
            ->method('formatPaymentMethodLabel')
            ->with($paymentMethodConstant, false)
            ->willReturn($label);
        $this->paymentMethodOptionsFormatter->expects(self::once())
            ->method('formatPaymentMethodOptions')
            ->with($paymentMethodConstant)
            ->willReturn([$option]);

        self::assertEquals(
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

        // test cache
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

        $this->paymentStatusLabelFormatter->expects(self::once())
            ->method('formatPaymentStatusLabel')
            ->with(PaymentStatuses::PAID_IN_FULL)
            ->willReturn($formattedValue);

        self::assertEquals(
            $formattedValue,
            self::callTwigFunction($this->extension, 'get_payment_status_label', [PaymentStatuses::PAID_IN_FULL])
        );
    }

    public function testGetPaymentStatus(): void
    {
        $object = new \stdClass();
        $status = PaymentStatuses::PAID_IN_FULL;

        $this->paymentStatusManager->expects(self::once())
            ->method('getPaymentStatus')
            ->with($object)
            ->willReturn((new PaymentStatus())->setPaymentStatus(PaymentStatuses::PAID_IN_FULL));

        self::assertEquals(
            $status,
            self::callTwigFunction($this->extension, 'get_payment_status', [$object])
        );
    }
}
