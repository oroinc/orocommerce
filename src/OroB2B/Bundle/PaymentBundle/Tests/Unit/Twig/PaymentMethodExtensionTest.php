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

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject|TranslatorInterface
     */
    protected $translator;


    public function setUp()
    {
        $this->translator = $this->getMock('Symfony\Component\Translation\TranslatorInterface');
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
            $this->paymentMethodViewRegistry,
            $this->translator
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
                new \Twig_SimpleFunction('get_payment_method_label', [$this->extension, 'getPaymentMethodLabel']),
                new \Twig_SimpleFunction(
                    'get_payment_method_admin_label',
                    [$this->extension, 'getPaymentMethodAdminLabel']
                )
            ],
            $this->extension->getFunctions()
        );
    }

    public function testGetName()
    {
        $this->assertEquals(PaymentMethodExtension::PAYMENT_METHOD_EXTENSION_NAME, $this->extension->getName());
    }

    /**
     * @param string $paymentMethod
     * @param string $returnLabel
     * @param bool $isShort
     */
    public function paymentMethodLabelMock($paymentMethod, $returnLabel, $isShort = true)
    {
        $this->paymentMethodViewRegistry
            ->expects($this->once())
            ->method('getPaymentMethodView')
            ->with($paymentMethod)
            ->willReturn($this->paymentMethodView);
        $this->paymentMethodView
            ->expects($this->once())
            ->method($isShort?'getShortLabel':'getLabel')
            ->willReturn($returnLabel);
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
        $this->paymentMethodLabelMock($paymentMethodConstant, $label, false);

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

    /**
     * @dataProvider paymentProvider
     * @param string $paymentMethod
     * @param string $paymentMethodLabel
     * @param string $paymentMethodShortLabel
     * @param string $expectedResult
     */
    public function testGetPaymentMethodAdminLabel(
        $paymentMethod,
        $paymentMethodLabel,
        $paymentMethodShortLabel,
        $expectedResult
    ) {
        $this->translator
            ->expects($this->once())
            ->method('trans')
            ->with('orob2b.payment.admin.'.$paymentMethod.'.label')
            ->willReturn($paymentMethodLabel);

        $this->paymentMethodLabelMock($paymentMethod, $paymentMethodShortLabel);

        $this->assertEquals($this->extension->getPaymentMethodAdminLabel($paymentMethod), $expectedResult);
    }

    /**
     * @return array
     */
    public function paymentProvider()
    {
        return [
            [
                '$paymentMethod' => 'payment_method',
                '$paymentMethodLabel' => 'Payment Method',
                '$paymentMethodShortLabel' => 'Payment Method Short',
                '$expectedResult' => 'Payment Method (Payment Method Short)',
            ],
            [
                '$paymentMethod' => 'payment_method',
                '$paymentMethodLabel' => 'Payment Method',
                '$paymentMethodShortLabel' => 'Payment Method',
                '$expectedResult' => 'Payment Method',
            ],
        ];
    }
}
