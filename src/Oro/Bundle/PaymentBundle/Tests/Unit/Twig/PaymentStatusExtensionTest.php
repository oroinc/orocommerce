<?php

namespace Oro\Bundle\PaymentBundle\Tests\Unit\Twig;

use Oro\Bundle\PaymentBundle\Formatter\PaymentStatusLabelFormatter;
use Oro\Bundle\PaymentBundle\Twig\PaymentStatusExtension;

class PaymentStatusExtensionTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var  PaymentStatusLabelFormatter|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $paymentStatusLabelFormatter;

    /**
     * @var PaymentStatusExtension
     */
    protected $extension;

    public function setUp()
    {
        $this->paymentStatusLabelFormatter = $this
            ->getMockBuilder(PaymentStatusLabelFormatter::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->extension = new PaymentStatusExtension($this->paymentStatusLabelFormatter);
    }

    public function testGetFunctions()
    {
        $this->assertEquals(
            [
                new \Twig_SimpleFunction(
                    'get_payment_status_label',
                    [$this->paymentStatusLabelFormatter, 'formatPaymentStatusLabel'],
                    ['is_safe' => ['html']]
                )
            ],
            $this->extension->getFunctions()
        );
    }

    public function testGetName()
    {
        $this->assertEquals(PaymentStatusExtension::PAYMENT_STATUS_EXTENSION_NAME, $this->extension->getName());
    }
}
