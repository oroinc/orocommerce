<?php

namespace Oro\Bundle\PaymentTermBundle\Tests\Unit\Method\View;

use Oro\Bundle\CheckoutBundle\Entity\CheckoutInterface;
use Oro\Bundle\CustomerBundle\Entity\Customer;
use Oro\Bundle\PaymentBundle\Context\PaymentContextInterface;
use Oro\Bundle\PaymentTermBundle\Entity\PaymentTerm;
use Oro\Bundle\PaymentTermBundle\Method\Config\PaymentTermConfigInterface;
use Oro\Bundle\PaymentTermBundle\Method\View\PaymentTermView;
use Oro\Bundle\PaymentTermBundle\Provider\PaymentTermProvider;
use Oro\Component\Checkout\Entity\CheckoutSourceEntityInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

class PaymentTermViewTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var PaymentTermProvider|\PHPUnit\Framework\MockObject\MockObject
     */
    protected $paymentTermProvider;

    /**
     * @var TranslatorInterface
     */
    protected $translator;

    /**
     * @var PaymentTermView
     */
    protected $methodView;

    /**
     * @var PaymentTermConfigInterface|\PHPUnit\Framework\MockObject\MockObject
     */
    protected $paymentConfig;

    protected function setUp(): void
    {
        $this->paymentTermProvider = $this->getMockBuilder('Oro\Bundle\PaymentTermBundle\Provider\PaymentTermProvider')
            ->disableOriginalConstructor()
            ->getMock();

        $this->translator = $this->createMock(TranslatorInterface::class);
        $this->translator->expects($this->any())
            ->method('trans')
            ->willReturnCallback(
                static function (string $key) {
                    return sprintf('[trans]%s[/trans]', $key);
                }
            );

        $this->paymentConfig = $this->createMock(PaymentTermConfigInterface::class);

        $this->methodView = new PaymentTermView($this->paymentTermProvider, $this->translator, $this->paymentConfig);
    }

    protected function tearDown(): void
    {
        unset($this->methodView, $this->configManager, $this->translator, $this->paymentTermProvider);
    }

    public function testGetOptionsEmpty()
    {
        $customer = $this->createMock(Customer::class);

        /** @var PaymentContextInterface|\PHPUnit\Framework\MockObject\MockObject $context */
        $context = $this->createMock(PaymentContextInterface::class);
        $context->expects(static::any())
            ->method('getCustomer')
            ->willReturn($customer);

        $this->paymentTermProvider->expects($this->once())
            ->method('getPaymentTerm')
            ->with($customer)
            ->willReturn(null);

        $this->assertEquals([], $this->methodView->getOptions($context));
    }

    public function testGetOptions()
    {
        $paymentTerm = new PaymentTerm();
        $paymentTerm->setLabel('testLabel');

        $customer = $this->createMock(Customer::class);

        /** @var PaymentContextInterface|\PHPUnit\Framework\MockObject\MockObject $context */
        $context = $this->createMock(PaymentContextInterface::class);
        $context->expects(static::any())
            ->method('getCustomer')
            ->willReturn($customer);

        $this->paymentTermProvider->expects($this->once())
            ->method('getPaymentTerm')
            ->with($customer)
            ->willReturn(new PaymentTerm());

        $this->assertEquals(
            ['value' => '[trans]oro.paymentterm.payment_terms.label[/trans]'],
            $this->methodView->getOptions($context)
        );
    }

    public function testGetOptionsWithCheckout()
    {
        $paymentTerm = new PaymentTerm();
        $paymentTerm->setLabel('testLabel');

        $checkoutSourceEntity = $this->createMock(CheckoutSourceEntityInterface::class);
        $sourceEntity = $this->createMock(CheckoutInterface::class);
        $sourceEntity->expects($this->once())
            ->method('getSourceEntity')
            ->willReturn($checkoutSourceEntity);
        /** @var PaymentContextInterface|\PHPUnit\Framework\MockObject\MockObject $context */
        $context = $this->createMock(PaymentContextInterface::class);
        $context->expects(static::any())
            ->method('getSourceEntity')
            ->willReturn($sourceEntity);

        $this->paymentTermProvider->expects($this->once())
            ->method('getObjectPaymentTerm')
            ->with($checkoutSourceEntity)
            ->willReturn($paymentTerm);

        $this->assertEquals(
            ['value' => '[trans]oro.paymentterm.payment_terms.label[/trans]'],
            $this->methodView->getOptions($context)
        );
    }

    public function testGetOptionsNullCustomer()
    {
        /** @var PaymentContextInterface|\PHPUnit\Framework\MockObject\MockObject $context */
        $context = $this->createMock(PaymentContextInterface::class);
        $context->expects(static::once())
            ->method('getCustomer')
            ->willReturn(null);

        $this->paymentTermProvider->expects($this->never())
            ->method('getPaymentTerm');

        $this->assertEmpty($this->methodView->getOptions($context));
    }

    public function testGetBlock()
    {
        $this->assertEquals('_payment_methods_payment_term_widget', $this->methodView->getBlock());
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

    public function testGetPaymentMethodIdentifier()
    {
        $this->paymentConfig->expects($this->once())
            ->method('getPaymentMethodIdentifier')
            ->willReturn('identifier');

        $this->assertEquals('identifier', $this->methodView->getPaymentMethodIdentifier());
    }
}
