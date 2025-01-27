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

/**
 * @SuppressWarnings(PHPMD.TooManyPublicMethods)
 */
class PaymentTermViewTest extends \PHPUnit\Framework\TestCase
{
    /** @var PaymentTermProvider|\PHPUnit\Framework\MockObject\MockObject */
    private $paymentTermProvider;

    /** @var PaymentTermConfigInterface|\PHPUnit\Framework\MockObject\MockObject */
    private $paymentConfig;

    /** @var PaymentTermView */
    private $methodView;

    #[\Override]
    protected function setUp(): void
    {
        $this->paymentTermProvider = $this->createMock(PaymentTermProvider::class);
        $this->paymentConfig = $this->createMock(PaymentTermConfigInterface::class);

        $translator = $this->createMock(TranslatorInterface::class);
        $translator->expects(self::any())
            ->method('trans')
            ->willReturnCallback(function (string $key) {
                return sprintf('[trans]%s[/trans]', $key);
            });

        $this->methodView = new PaymentTermView($this->paymentTermProvider, $translator, $this->paymentConfig);
    }

    public function testGetFrontendApiOptionsEmpty(): void
    {
        $customer = $this->createMock(Customer::class);

        $context = $this->createMock(PaymentContextInterface::class);
        $context->expects(self::once())
            ->method('getCustomer')
            ->willReturn($customer);

        $this->paymentTermProvider->expects(self::once())
            ->method('getPaymentTerm')
            ->with($customer)
            ->willReturn(null);

        self::assertEquals(
            ['paymentTerm' => null],
            $this->methodView->getFrontendApiOptions($context)
        );
    }

    public function testGetFrontendApiOptions(): void
    {
        $paymentTerm = new PaymentTerm();
        $paymentTerm->setLabel('testLabel');

        $customer = $this->createMock(Customer::class);

        $context = $this->createMock(PaymentContextInterface::class);
        $context->expects(self::once())
            ->method('getCustomer')
            ->willReturn($customer);

        $this->paymentTermProvider->expects(self::once())
            ->method('getPaymentTerm')
            ->with($customer)
            ->willReturn($paymentTerm);

        self::assertEquals(
            ['paymentTerm' => 'testLabel'],
            $this->methodView->getFrontendApiOptions($context)
        );
    }

    public function testGetFrontendApiOptionsWithCheckoutSource(): void
    {
        $paymentTerm = new PaymentTerm();
        $paymentTerm->setLabel('testLabel');

        $checkoutSourceEntity = $this->createMock(CheckoutSourceEntityInterface::class);
        $sourceEntity = $this->createMock(CheckoutInterface::class);
        $sourceEntity->expects(self::once())
            ->method('getSourceEntity')
            ->willReturn($checkoutSourceEntity);
        $context = $this->createMock(PaymentContextInterface::class);
        $context->expects(self::once())
            ->method('getSourceEntity')
            ->willReturn($sourceEntity);

        $this->paymentTermProvider->expects(self::once())
            ->method('getObjectPaymentTerm')
            ->with($checkoutSourceEntity)
            ->willReturn($paymentTerm);

        self::assertEquals(
            ['paymentTerm' => 'testLabel'],
            $this->methodView->getFrontendApiOptions($context)
        );
    }

    public function testGetFrontendApiOptionsWithoutCheckoutSource(): void
    {
        $paymentTerm = new PaymentTerm();
        $paymentTerm->setLabel('testLabel');

        $customer = $this->createMock(Customer::class);

        $sourceEntity = $this->createMock(CheckoutInterface::class);
        $sourceEntity->expects(self::once())
            ->method('getSourceEntity')
            ->willReturn(null);
        $context = $this->createMock(PaymentContextInterface::class);
        $context->expects(self::once())
            ->method('getSourceEntity')
            ->willReturn($sourceEntity);
        $context->expects(self::once())
            ->method('getCustomer')
            ->willReturn($customer);

        $this->paymentTermProvider->expects(self::never())
            ->method('getObjectPaymentTerm');

        $this->paymentTermProvider->expects(self::once())
            ->method('getPaymentTerm')
            ->with($customer)
            ->willReturn($paymentTerm);

        self::assertEquals(
            ['paymentTerm' => 'testLabel'],
            $this->methodView->getFrontendApiOptions($context)
        );
    }

    public function testGetFrontendApiOptionsNullCustomer(): void
    {
        $context = $this->createMock(PaymentContextInterface::class);
        $context->expects(self::once())
            ->method('getCustomer')
            ->willReturn(null);

        $this->paymentTermProvider->expects(self::never())
            ->method('getPaymentTerm');

        self::assertEquals(
            ['paymentTerm' => null],
            $this->methodView->getFrontendApiOptions($context)
        );
    }

    public function testGetOptionsEmpty(): void
    {
        $customer = $this->createMock(Customer::class);

        $context = $this->createMock(PaymentContextInterface::class);
        $context->expects(self::once())
            ->method('getCustomer')
            ->willReturn($customer);

        $this->paymentTermProvider->expects(self::once())
            ->method('getPaymentTerm')
            ->with($customer)
            ->willReturn(null);

        self::assertEquals([], $this->methodView->getOptions($context));
    }

    public function testGetOptions(): void
    {
        $paymentTerm = new PaymentTerm();
        $paymentTerm->setLabel('testLabel');

        $customer = $this->createMock(Customer::class);

        $context = $this->createMock(PaymentContextInterface::class);
        $context->expects(self::once())
            ->method('getCustomer')
            ->willReturn($customer);

        $this->paymentTermProvider->expects(self::once())
            ->method('getPaymentTerm')
            ->with($customer)
            ->willReturn($paymentTerm);

        self::assertEquals(
            ['value' => '[trans]oro.paymentterm.payment_terms.label[/trans]'],
            $this->methodView->getOptions($context)
        );
    }

    public function testGetOptionsWithCheckoutSource(): void
    {
        $paymentTerm = new PaymentTerm();
        $paymentTerm->setLabel('testLabel');

        $checkoutSourceEntity = $this->createMock(CheckoutSourceEntityInterface::class);
        $sourceEntity = $this->createMock(CheckoutInterface::class);
        $sourceEntity->expects(self::once())
            ->method('getSourceEntity')
            ->willReturn($checkoutSourceEntity);
        $context = $this->createMock(PaymentContextInterface::class);
        $context->expects(self::once())
            ->method('getSourceEntity')
            ->willReturn($sourceEntity);

        $this->paymentTermProvider->expects(self::once())
            ->method('getObjectPaymentTerm')
            ->with($checkoutSourceEntity)
            ->willReturn($paymentTerm);

        self::assertEquals(
            ['value' => '[trans]oro.paymentterm.payment_terms.label[/trans]'],
            $this->methodView->getOptions($context)
        );
    }

    public function testGetOptionsWithoutCheckoutSource(): void
    {
        $paymentTerm = new PaymentTerm();
        $paymentTerm->setLabel('testLabel');

        $customer = $this->createMock(Customer::class);

        $sourceEntity = $this->createMock(CheckoutInterface::class);
        $sourceEntity->expects(self::once())
            ->method('getSourceEntity')
            ->willReturn(null);
        $context = $this->createMock(PaymentContextInterface::class);
        $context->expects(self::once())
            ->method('getSourceEntity')
            ->willReturn($sourceEntity);
        $context->expects(self::once())
            ->method('getCustomer')
            ->willReturn($customer);

        $this->paymentTermProvider->expects(self::never())
            ->method('getObjectPaymentTerm');

        $this->paymentTermProvider->expects(self::once())
            ->method('getPaymentTerm')
            ->with($customer)
            ->willReturn($paymentTerm);

        self::assertEquals(
            ['value' => '[trans]oro.paymentterm.payment_terms.label[/trans]'],
            $this->methodView->getOptions($context)
        );
    }

    public function testGetOptionsNullCustomer(): void
    {
        $context = $this->createMock(PaymentContextInterface::class);
        $context->expects(self::once())
            ->method('getCustomer')
            ->willReturn(null);

        $this->paymentTermProvider->expects(self::never())
            ->method('getPaymentTerm');

        self::assertEmpty($this->methodView->getOptions($context));
    }

    public function testGetBlock(): void
    {
        self::assertEquals('_payment_methods_payment_term_widget', $this->methodView->getBlock());
    }

    public function testGetLabel(): void
    {
        $this->paymentConfig->expects(self::once())
            ->method('getLabel')
            ->willReturn('label');

        self::assertEquals('label', $this->methodView->getLabel());
    }

    public function testGetShortLabel(): void
    {
        $this->paymentConfig->expects(self::once())
            ->method('getShortLabel')
            ->willReturn('short label');

        self::assertEquals('short label', $this->methodView->getShortLabel());
    }

    public function testGetPaymentMethodIdentifier(): void
    {
        $this->paymentConfig->expects(self::once())
            ->method('getPaymentMethodIdentifier')
            ->willReturn('identifier');

        self::assertEquals('identifier', $this->methodView->getPaymentMethodIdentifier());
    }
}
