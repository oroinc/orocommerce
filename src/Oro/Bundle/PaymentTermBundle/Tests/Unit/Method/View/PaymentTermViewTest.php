<?php

namespace Oro\Bundle\PaymentTermBundle\Tests\Unit\Method\View;

use Symfony\Bundle\FrameworkBundle\Tests\Templating\Helper\Fixtures\StubTranslator;
use Symfony\Component\Translation\TranslatorInterface;

use Oro\Bundle\PaymentTermBundle\Method\Config\PaymentTermConfigInterface;
use Oro\Bundle\PaymentTermBundle\Method\View\PaymentTermView;
use Oro\Bundle\PaymentTermBundle\Entity\PaymentTerm;
use Oro\Bundle\PaymentTermBundle\Provider\PaymentTermProvider;

class PaymentTermViewTest extends \PHPUnit_Framework_TestCase
{
    /** @var PaymentTermProvider|\PHPUnit_Framework_MockObject_MockObject */
    protected $paymentTermProvider;

    /** @var TranslatorInterface */
    protected $translator;

    /** @var PaymentTermView */
    protected $methodView;

    /** @var PaymentTermConfigInterface|\PHPUnit_Framework_MockObject_MockObject */
    protected $paymentConfig;

    protected function setUp()
    {
        $this->paymentTermProvider = $this->getMockBuilder('Oro\Bundle\PaymentTermBundle\Provider\PaymentTermProvider')
            ->disableOriginalConstructor()
            ->getMock();

        $this->translator = new StubTranslator();

        $this->paymentConfig = $this->getMock(PaymentTermConfigInterface::class);

        $this->methodView = new PaymentTermView($this->paymentTermProvider, $this->translator, $this->paymentConfig);
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

        $this->assertEquals([], $this->methodView->getOptions());
    }

    public function testGetOptions()
    {
        $paymentTerm = new PaymentTerm();
        $paymentTerm->setLabel('testLabel');

        $this->paymentTermProvider->expects($this->once())
            ->method('getCurrentPaymentTerm')
            ->willReturn($paymentTerm);

        $this->assertEquals(
            ['value' => '[trans]oro.paymentterm.payment_terms.label[/trans]'],
            $this->methodView->getOptions()
        );
    }

    public function testGetBlock()
    {
        $this->assertEquals('_payment_methods_payment_term_widget', $this->methodView->getBlock());
    }

    public function testGetOrder()
    {
        $order = '100';

        $this->paymentConfig->expects($this->once())
            ->method('getOrder')
            ->willReturn((int)$order);

        $this->assertEquals((int)$order, $this->methodView->getOrder());
    }

    public function testGetPaymentMethodType()
    {
        $this->assertEquals('payment_term', $this->methodView->getPaymentMethodType());
    }

    public function testGetLabel()
    {
        $this->paymentConfig->expects($this->once())
            ->method('getLabel')
            ->willReturn('label');

        $this->assertEquals('label', $this->methodView->getLabel());
    }

    public function testGetShortLabel()
    {
        $this->paymentConfig->expects($this->once())
            ->method('getShortLabel')
            ->willReturn('short label');

        $this->assertEquals('short label', $this->methodView->getShortLabel());
    }
}
