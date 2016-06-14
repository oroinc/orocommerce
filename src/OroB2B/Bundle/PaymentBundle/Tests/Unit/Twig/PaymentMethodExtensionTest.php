<?php

namespace OroB2B\src\OroB2B\Bundle\PaymentBundle\Tests\Unit\Twig;

use OroB2B\Bundle\PaymentBundle\Entity\PaymentTerm;
use OroB2B\Bundle\PaymentBundle\Entity\PaymentTransaction;
use OroB2B\Bundle\PaymentBundle\Method\View\PaymentMethodViewInterface;
use OroB2B\Bundle\PaymentBundle\Method\View\PaymentMethodViewRegistry;
use OroB2B\Bundle\PaymentBundle\Provider\PaymentTransactionProvider;
use OroB2B\Bundle\PaymentBundle\Twig\PaymentMethodExtension;

class PaymentMethodExtensionTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var  PaymentTransactionProvider|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $paymentTransactionProvider;

    /**
     * @var PaymentMethodViewRegistry|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $paymentMethodViewRegistry;

    /**
     * @var PaymentMethodViewInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $paymentMethodView;

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
        $this->paymentMethodViewRegistry = $this
            ->getMockBuilder('OroB2B\Bundle\PaymentBundle\Method\View\PaymentMethodViewRegistry')
            ->disableOriginalConstructor()
            ->getMock();
        $this->extension = new PaymentMethodExtension(
            $this->paymentTransactionProvider,
            $this->paymentMethodViewRegistry
        );
        $this->paymentMethodView = $this
            ->getMockBuilder('OroB2B\Bundle\PaymentBundle\Method\View\PaymentMethodViewInterface')
            ->disableOriginalConstructor()
            ->getMock();
    }

    public function testGetFunctions()
    {
        $this->assertEquals(
            [
                new \Twig_SimpleFunction('get_payment_methods', [$this->extension, 'getPaymentMethods']),
                new \Twig_SimpleFunction('get_payment_method_label', [$this->extension, 'getPaymentMethodLabel'])
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
        $this->paymentMethodViewRegistry
            ->expects($this->once())
            ->method('getPaymentMethodView')
            ->with($paymentMethodConstant)
            ->willReturn($this->paymentMethodView);
        $this->paymentMethodView
            ->expects($this->once())
            ->method('getLabel')
            ->willReturn($label);

        $this->assertEquals($this->extension->getPaymentMethods($entity), [$label]);
    }

    public function testGetPaymentMethodLabel()
    {
        $label = 'label';
        $paymentMethodConstant = 'payment_term';
        $paymentMethodNotExistsConstant = 'not_exists_method';
        $this->paymentMethodViewRegistry
            ->expects($this->at(0))
            ->method('getPaymentMethodView')
            ->with($paymentMethodConstant)
            ->willReturn($this->paymentMethodView);
        $this->paymentMethodViewRegistry
            ->expects($this->at(1))
            ->method('getPaymentMethodView')
            ->with($paymentMethodNotExistsConstant)
            ->willThrowException(new \InvalidArgumentException());
        $this->paymentMethodViewRegistry
            ->expects($this->at(2))
            ->method('getPaymentMethodView')
            ->with($paymentMethodConstant)
            ->willReturn($this->paymentMethodView);

        $this->paymentMethodView
            ->expects($this->once())
            ->method('getLabel')
            ->willReturn($label);

        $this->paymentMethodView
            ->expects($this->once(1))
            ->method('getShortLabel')
            ->willReturn($label);

        $this->assertEquals($this->extension->getPaymentMethodLabel($paymentMethodConstant), $label);
        $this->assertEquals($this->extension->getPaymentMethodLabel($paymentMethodNotExistsConstant), '');
        $this->assertEquals($this->extension->getPaymentMethodLabel($paymentMethodConstant, false), $label);
    }
}
