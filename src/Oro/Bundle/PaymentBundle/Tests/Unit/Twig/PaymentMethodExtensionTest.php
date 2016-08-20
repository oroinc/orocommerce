<?php

namespace Oro\src\Oro\Bundle\PaymentBundle\Tests\Unit\Twig;

use Oro\Bundle\PaymentBundle\Entity\PaymentTerm;
use Oro\Bundle\PaymentBundle\Entity\PaymentTransaction;
use Oro\Bundle\PaymentBundle\Formatter\PaymentMethodLabelFormatter;
use Oro\Bundle\PaymentBundle\Provider\PaymentTransactionProvider;
use Oro\Bundle\PaymentBundle\Twig\PaymentMethodExtension;

class PaymentMethodExtensionTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var  PaymentTransactionProvider|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $paymentTransactionProvider;

    /**
     * @var  PaymentMethodLabelFormatter|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $paymentMethodLabelFormatter;

    /**
     * @var PaymentMethodExtension
     */
    protected $extension;

    public function setUp()
    {
        $this->paymentTransactionProvider = $this
            ->getMockBuilder('Oro\Bundle\PaymentBundle\Provider\PaymentTransactionProvider')
            ->disableOriginalConstructor()
            ->getMock();
        $this->paymentMethodLabelFormatter = $this
            ->getMockBuilder('Oro\Bundle\PaymentBundle\Formatter\PaymentMethodLabelFormatter')
            ->disableOriginalConstructor()
            ->getMock();
        $this->extension = new PaymentMethodExtension(
            $this->paymentTransactionProvider,
            $this->paymentMethodLabelFormatter
        );
    }

    public function testGetFunctions()
    {
        $this->assertEquals(
            [
                new \Twig_SimpleFunction(
                    'get_payment_methods',
                    [$this->extension, 'getPaymentMethods']
                ),
                new \Twig_SimpleFunction(
                    'get_payment_method_label',
                    [$this->paymentMethodLabelFormatter, 'formatPaymentMethodLabel']
                ),
                new \Twig_SimpleFunction(
                    'get_payment_method_admin_label',
                    [$this->paymentMethodLabelFormatter, 'formatPaymentMethodAdminLabel'],
                    ['is_safe' => ['html']]
                )
            ],
            $this->extension->getFunctions()
        );
    }

    public function testGetName()
    {
        $this->assertEquals(PaymentMethodExtension::PAYMENT_METHOD_EXTENSION_NAME, $this->extension->getName());
    }

    public function testGetPaymentMethods()
    {
        $entity = new PaymentTerm();
        $label = 'label';
        $paymentMethodConstant = 'payment_term';
        $paymentTransaction = new PaymentTransaction();
        $paymentTransaction->setPaymentMethod($paymentMethodConstant);
        $this->paymentTransactionProvider
            ->expects($this->once())
            ->method('getPaymentTransactions')
            ->with($entity)
            ->willReturn([$paymentTransaction]);

        $this->paymentMethodLabelFormatter
            ->expects($this->once())
            ->method('formatPaymentMethodLabel')
            ->with($paymentMethodConstant, false)
            ->willReturn($label);

        $this->assertEquals($this->extension->getPaymentMethods($entity), [$label]);
    }
}
