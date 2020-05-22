<?php

namespace Oro\Bundle\PaymentBundle\Tests\Unit\Twig;

use Oro\Bundle\PaymentBundle\Formatter\PaymentStatusLabelFormatter;
use Oro\Bundle\PaymentBundle\Provider\PaymentStatusProvider;
use Oro\Bundle\PaymentBundle\Twig\PaymentStatusExtension;
use Oro\Component\Testing\Unit\TwigExtensionTestCaseTrait;

class PaymentStatusExtensionTest extends \PHPUnit\Framework\TestCase
{
    use TwigExtensionTestCaseTrait;

    /** @var PaymentStatusLabelFormatter|\PHPUnit\Framework\MockObject\MockObject */
    protected $paymentStatusLabelFormatter;

    /** @var PaymentStatusExtension */
    protected $extension;

    /** @var PaymentStatusProvider|\PHPUnit\Framework\MockObject\MockObject */
    protected $paymentStatusProvider;

    protected function setUp(): void
    {
        $this->paymentStatusLabelFormatter = $this->createMock(PaymentStatusLabelFormatter::class);
        $this->paymentStatusProvider = $this->createMock(PaymentStatusProvider::class);

        $container = self::getContainerBuilder()
            ->add('oro_payment.formatter.payment_status_label', $this->paymentStatusLabelFormatter)
            ->add('oro_payment.provider.payment_status', $this->paymentStatusProvider)
            ->getContainer($this);

        $this->extension = new PaymentStatusExtension($container);
    }

    public function testGetName()
    {
        $this->assertEquals(PaymentStatusExtension::PAYMENT_STATUS_EXTENSION_NAME, $this->extension->getName());
    }

    public function testFormatPaymentStatusLabel()
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

    public function testGetPaymentStatus()
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
