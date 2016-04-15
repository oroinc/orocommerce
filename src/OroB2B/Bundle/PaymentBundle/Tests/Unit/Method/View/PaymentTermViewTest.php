<?php

namespace OroB2B\Bundle\PaymentBundle\Tests\Unit\Method\View;

use Symfony\Component\Translation\TranslatorInterface;

use Oro\Bundle\ConfigBundle\Config\ConfigManager;

use OroB2B\Bundle\PaymentBundle\Method\View\PaymentTermView;
use OroB2B\Bundle\PaymentBundle\Method\PaymentTerm as PaymentTermMethod;
use OroB2B\Bundle\PaymentBundle\Entity\PaymentTerm;
use OroB2B\Bundle\PaymentBundle\DependencyInjection\Configuration;
use OroB2B\Bundle\PaymentBundle\DependencyInjection\OroB2BPaymentExtension;
use OroB2B\Bundle\PaymentBundle\Provider\PaymentTermProvider;

class PaymentTermViewTest extends \PHPUnit_Framework_TestCase
{
    /** @var ConfigManager|\PHPUnit_Framework_MockObject_MockObject */
    protected $configManager;

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

    public function testGetOptionsEmpty()
    {
        $this->paymentTermProvider->expects($this->once())
            ->method('getCurrentPaymentTerm')
            ->willReturn(null);

        $this->translator->expects($this->never())
            ->method('trans');

        $this->assertEquals(
            [],
            $this->methodView->getOptions()
        );
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
            ->with('orob2b.payment.payment_terms.label', ['%paymentTerm%' => 'testLabel'])
            ->willReturn('translatedValue');

        $this->assertEquals(
            [
                'value' => 'translatedValue'
            ],
            $this->methodView->getOptions()
        );
    }

    public function testGetBlock()
    {
        $this->assertEquals('_payment_methods_payment_term_widget', $this->methodView->getBlock());
    }

    public function testGetOrder()
    {
        $order = 100;
        $this->setConfig($this->once(), Configuration::PAYMENT_TERM_SORT_ORDER_KEY, $order);

        $this->assertEquals($order, $this->methodView->getOrder());
    }

    public function testGetPaymentMethodType()
    {
        $this->assertEquals(PaymentTermMethod::TYPE, $this->methodView->getPaymentMethodType());
    }

    public function testGetLabel()
    {
        $this->setConfig($this->once(), Configuration::PAYMENT_TERM_LABEL_KEY, 'testValue');
        $this->assertEquals('testValue', $this->methodView->getLabel());
    }

    /**
     * @param mixed $expects
     * @param string $key
     * @param mixed $value
     */
    protected function setConfig($expects, $key, $value)
    {
        $this->configManager->expects($expects)
            ->method('get')
            ->with($this->getConfigKey($key))
            ->willReturn($value);
    }

    /**
     * @param string $key
     * @return string
     */
    protected function getConfigKey($key)
    {
        return OroB2BPaymentExtension::ALIAS . ConfigManager::SECTION_MODEL_SEPARATOR . $key;
    }

}
