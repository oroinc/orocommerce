<?php

namespace OroB2B\src\OroB2B\Bundle\PaymentBundle\Tests\Unit\Twig;

use Oro\Bundle\ConfigBundle\Config\ConfigManager;
use OroB2B\Bundle\PaymentBundle\Entity\PaymentTerm;
use OroB2B\Bundle\PaymentBundle\Entity\PaymentTransaction;
use OroB2B\Bundle\PaymentBundle\Provider\PaymentTransactionProvider;
use OroB2B\Bundle\PaymentBundle\Twig\PaymentMethodExtension;

class PaymentMethodExtensionTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var  PaymentTransactionProvider|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $paymentTransactionProvider;

    /**
     * @var ConfigManager|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $configManager;

    /**
     * @var PaymentMethodExtension
     */
    protected $extension;

    public function setUp()
    {
        $this->paymentTransactionProvider = $this
            ->getMockBuilder('OroB2B\Bundle\PaymentBundle\Provider\PaymentTransactionProvider')
            ->disableOriginalConstructor()
            ->getMock();
        $this->configManager = $this
            ->getMockBuilder('Oro\Bundle\ConfigBundle\Config\ConfigManager')
            ->disableOriginalConstructor()
            ->getMock();
        $this->extension = new PaymentMethodExtension($this->paymentTransactionProvider, $this->configManager);
    }

    public function testGetFunctions()
    {
        $this->assertEquals(
            [
                new \Twig_SimpleFunction('get_payment_methods', [$this->extension, 'getPaymentMethods'])
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
        $paymentMethodConstant = 'SomePaymentMethod';
        $paymentTransaction = new PaymentTransaction();
        $paymentTransaction->setPaymentMethod($paymentMethodConstant);
        $this->paymentTransactionProvider
            ->expects($this->once())
            ->method('getPaymentTransactions')
            ->with($entity)
            ->willReturn([$paymentTransaction]);
        $label = 'label';
        $this->configManager
            ->expects($this->once())
            ->method('get')
            ->with(sprintf('orob2b_payment.%s_label', $paymentMethodConstant))
            ->willReturn($label);
        $this->assertEquals($this->extension->getPaymentMethods($entity), [$label]);
    }
}
