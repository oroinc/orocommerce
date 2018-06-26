<?php

namespace Oro\Bundle\PaymentBundle\Tests\Unit\Twig;

use Oro\Bundle\PaymentBundle\Formatter\PaymentStatusLabelFormatter;
use Oro\Bundle\PaymentBundle\Twig\PaymentStatusExtension;
use Oro\Component\Testing\Unit\TwigExtensionTestCaseTrait;

class PaymentStatusExtensionTest extends \PHPUnit\Framework\TestCase
{
    use TwigExtensionTestCaseTrait;

    /** @var PaymentStatusLabelFormatter|\PHPUnit\Framework\MockObject\MockObject */
    protected $paymentStatusLabelFormatter;

    /** @var PaymentStatusExtension */
    protected $extension;

    public function setUp()
    {
        $this->paymentStatusLabelFormatter = $this->getMockBuilder(PaymentStatusLabelFormatter::class)
            ->disableOriginalConstructor()
            ->getMock();

        $container = self::getContainerBuilder()
            ->add('oro_payment.formatter.payment_status_label', $this->paymentStatusLabelFormatter)
            ->getContainer($this);

        $this->extension = new PaymentStatusExtension($container);
    }

    public function testGetName()
    {
        $this->assertEquals(PaymentStatusExtension::PAYMENT_STATUS_EXTENSION_NAME, $this->extension->getName());
    }
}
