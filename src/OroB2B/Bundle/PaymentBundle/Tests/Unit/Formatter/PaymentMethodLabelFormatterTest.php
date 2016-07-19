<?php

namespace OroB2B\src\OroB2B\Bundle\PaymentBundle\Tests\Unit\Twig;

use OroB2B\Bundle\PaymentBundle\Method\View\PaymentMethodViewInterface;
use OroB2B\Bundle\PaymentBundle\Method\View\PaymentMethodViewRegistry;
use OroB2B\Bundle\PaymentBundle\Formatter\PaymentMethodLabelFormatter;

use Symfony\Component\Translation\TranslatorInterface;

class PaymentMethodLabelFormatterTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var PaymentMethodViewRegistry|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $paymentMethodViewRegistry;

    /**
     * @var PaymentMethodViewInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $paymentMethodView;

    /**
     * @var PaymentMethodLabelFormatter
     */
    protected $formatter;

    /**
     * @var TranslatorInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $translator;


    public function setUp()
    {
        $this->translator = $this->getMock('Symfony\Component\Translation\TranslatorInterface');
        $this->paymentMethodViewRegistry = $this
            ->getMockBuilder('OroB2B\Bundle\PaymentBundle\Method\View\PaymentMethodViewRegistry')
            ->disableOriginalConstructor()
            ->getMock();
        $this->paymentMethodView = $this
            ->getMockBuilder('OroB2B\Bundle\PaymentBundle\Method\View\PaymentMethodViewInterface')
            ->disableOriginalConstructor()
            ->getMock();
        $this->formatter = new PaymentMethodLabelFormatter(
            $this->paymentMethodViewRegistry,
            $this->translator
        );
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
            ->method($isShort ? 'getShortLabel' : 'getLabel')
            ->willReturn($returnLabel);
    }

    public function testFormatPaymentMethodLabel()
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

        $this->assertEquals($this->formatter->formatPaymentMethodLabel($paymentMethodConstant), $label);
        $this->assertEquals($this->formatter->formatPaymentMethodLabel($paymentMethodNotExistsConstant), '');
        $this->assertEquals($this->formatter->formatPaymentMethodLabel($paymentMethodConstant, false), $label);
    }

    /**
     * @dataProvider paymentProvider
     * @param string $paymentMethod
     * @param string $paymentMethodLabel
     * @param string $paymentMethodShortLabel
     * @param string $expectedResult
     */
    public function testFormatPaymentMethodAdminLabel(
        $paymentMethod,
        $paymentMethodLabel,
        $paymentMethodShortLabel,
        $expectedResult
    ) {
        $this->translator
            ->expects($this->once())
            ->method('trans')
            ->with('orob2b.payment.admin.' . $paymentMethod . '.label')
            ->willReturn($paymentMethodLabel);

        $this->paymentMethodLabelMock($paymentMethod, $paymentMethodShortLabel);

        $this->assertEquals($this->formatter->formatPaymentMethodAdminLabel($paymentMethod), $expectedResult);
    }

    /**
     * @return array
     */
    public function paymentProvider()
    {
        return [
            [
                '$paymentMethod'           => 'payment_method',
                '$paymentMethodLabel'      => 'Payment Method',
                '$paymentMethodShortLabel' => 'Payment Method Short',
                '$expectedResult'          => 'Payment Method (Payment Method Short)',
            ],
            [
                '$paymentMethod'           => 'payment_method',
                '$paymentMethodLabel'      => 'Payment Method',
                '$paymentMethodShortLabel' => 'Payment Method',
                '$expectedResult'          => 'Payment Method',
            ],
        ];
    }
}
