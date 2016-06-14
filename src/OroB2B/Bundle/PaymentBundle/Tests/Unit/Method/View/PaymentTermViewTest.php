<?php

namespace OroB2B\Bundle\PaymentBundle\Tests\Unit\Method\View;

use Symfony\Component\Translation\TranslatorInterface;


use OroB2B\Bundle\PaymentBundle\Method\View\PaymentTermView;
use OroB2B\Bundle\PaymentBundle\Entity\PaymentTerm;
use OroB2B\Bundle\PaymentBundle\DependencyInjection\Configuration;
use OroB2B\Bundle\PaymentBundle\Provider\PaymentTermProvider;
use OroB2B\Bundle\PaymentBundle\Tests\Unit\Method\ConfigTestTrait;

class PaymentTermViewTest extends \PHPUnit_Framework_TestCase
{
    use ConfigTestTrait;

    /** @var PaymentTermProvider|\PHPUnit_Framework_MockObject_MockObject */
    protected $paymentTermProvider;

    /**  @var TranslatorInterface|\PHPUnit_Framework_MockObject_MockObject */
    protected $translator;

    /** @var PaymentTermView */
    protected $methodView;

    protected function setUp()
    {
        $this->paymentTermProvider = $this->getMockBuilder('OroB2B\Bundle\PaymentBundle\Provider\PaymentTermProvider')
            ->disableOriginalConstructor()
            ->getMock();

        $this->translator = $this->getMock('Symfony\Component\Translation\TranslatorInterface');

        $this->configManager = $this->getMockBuilder('Oro\Bundle\ConfigBundle\Config\ConfigManager')
            ->disableOriginalConstructor()
            ->getMock();

        $this->methodView = new PaymentTermView($this->paymentTermProvider, $this->translator, $this->configManager);
    }

    protected function tearDown()
    {
        unset($this->methodView, $this->configManager, $this->translator, $this->paymentTermProvider);
    }

    public function testGetOptionsEmpty()
    {
        $this->paymentTermProvider->expects($this->once())
            ->method('getCurrentPaymentTerm')
            ->willReturn(null);

        $this->translator->expects($this->never())
            ->method('trans');

        $this->assertEquals([], $this->methodView->getOptions());
    }

    public function testGetOptions()
    {
        $paymentTerm = new PaymentTerm();
        $paymentTerm->setLabel('testLabel');

        $this->paymentTermProvider->expects($this->once())
            ->method('getCurrentPaymentTerm')
            ->willReturn($paymentTerm);

        $this->translator->expects($this->once())
            ->method('trans')
            ->with('orob2b.payment.payment_terms.label', ['%paymentTerm%' => $paymentTerm->getLabel()])
            ->willReturn('translatedValue');

        $this->assertEquals(['value' => 'translatedValue'], $this->methodView->getOptions());
    }

    public function testGetBlock()
    {
        $this->assertEquals('_payment_methods_payment_term_widget', $this->methodView->getBlock());
    }

    public function testGetOrder()
    {
        $order = '100';
        $this->setConfig($this->once(), Configuration::PAYMENT_TERM_SORT_ORDER_KEY, $order);
        $this->assertEquals((int)$order, $this->methodView->getOrder());
    }

    public function testGetPaymentMethodType()
    {
        $this->assertEquals('payment_term', $this->methodView->getPaymentMethodType());
    }

    public function testGetLabel()
    {
        $this->setConfig($this->once(), Configuration::PAYMENT_TERM_LABEL_KEY, 'testValue');
        $this->assertEquals('testValue', $this->methodView->getLabel());
    }

    public function testGetShortLabel()
    {
        $this->setConfig($this->once(), Configuration::PAYMENT_TERM_SHORT_LABEL_KEY, 'testValue');
        $this->assertEquals('testValue', $this->methodView->getShortLabel());
    }
}
