<?php

namespace Oro\Bundle\PayPalBundle\Tests\Unit\Method\View;

use Oro\Bundle\PaymentBundle\Context\PaymentContextInterface;
use Oro\Bundle\PaymentBundle\Entity\PaymentTransaction;
use Oro\Bundle\PaymentBundle\Provider\PaymentTransactionProvider;
use Oro\Bundle\PayPalBundle\Form\Type\CreditCardType;
use Oro\Bundle\PayPalBundle\Method\Config\PayPalCreditCardConfigInterface;
use Oro\Bundle\PayPalBundle\Method\View\PayPalCreditCardPaymentMethodView;
use Oro\Component\Testing\Unit\EntityTrait;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormView;

class PayPalCreditCardPaymentMethodViewTest extends \PHPUnit\Framework\TestCase
{
    use EntityTrait;

    private const ALLOWED_CC_TYPES = ['visa', 'mastercard'];

    /** @var FormFactoryInterface|\PHPUnit\Framework\MockObject\MockObject */
    private $formFactory;

    /** @var PaymentTransactionProvider|\PHPUnit\Framework\MockObject\MockObject */
    private $paymentTransactionProvider;

    /** @var PayPalCreditCardConfigInterface|\PHPUnit\Framework\MockObject\MockObject */
    private $paymentConfig;

    /** @var PayPalCreditCardPaymentMethodView */
    private $methodView;

    protected function setUp(): void
    {
        $this->formFactory = $this->createMock(FormFactoryInterface::class);
        $this->paymentTransactionProvider = $this->createMock(PaymentTransactionProvider::class);
        $this->paymentConfig = $this->createMock(PayPalCreditCardConfigInterface::class);

        $this->methodView = new PayPalCreditCardPaymentMethodView(
            $this->formFactory,
            $this->paymentConfig,
            $this->paymentTransactionProvider
        );
    }

    public function testGetOptionsWithoutZeroAmount()
    {
        $this->paymentTransactionProvider->expects($this->never())
            ->method('getActiveValidatePaymentTransaction');

        [$formView, $context] = $this->prepareMocks(false, true);

        $this->assertEquals(
            [
                'formView' => $formView,
                'creditCardComponentOptions' => [
                    'allowedCreditCards' => self::ALLOWED_CC_TYPES,
                ]
            ],
            $this->methodView->getOptions($context)
        );
    }

    public function testGetOptionsWithZeroAmountWithoutTransaction()
    {
        $this->paymentTransactionProvider->expects($this->once())
            ->method('getActiveValidatePaymentTransaction')
            ->willReturn(null);

        [$formView, $context] = $this->prepareMocks(true, true);

        $this->assertEquals(
            [
                'formView' => $formView,
                'creditCardComponentOptions' => [
                    'allowedCreditCards' => self::ALLOWED_CC_TYPES,
                ]
            ],
            $this->methodView->getOptions($context)
        );
    }

    public function testGetOptions()
    {
        $paymentTransaction = new PaymentTransaction();
        $paymentTransaction->setResponse(['ACCT' => '1111']);

        $this->paymentTransactionProvider->expects($this->once())
            ->method('getActiveValidatePaymentTransaction')
            ->willReturn($paymentTransaction);

        [$formView, $context] = $this->prepareMocks(true, true);

        $this->assertEquals(
            [
                'formView' => $formView,
                'creditCardComponent' => 'oropaypal/js/app/components/authorized-credit-card-component',
                'creditCardComponentOptions' => [
                    'acct' => '1111',
                    'saveForLaterUse' => false,
                    'allowedCreditCards' => self::ALLOWED_CC_TYPES,
                ],
            ],
            $this->methodView->getOptions($context)
        );
    }

    public function testGetOptionsWithLaterUse()
    {
        $paymentTransaction = new PaymentTransaction();
        $paymentTransaction
            ->setResponse(['ACCT' => '1111'])
            ->setTransactionOptions(['saveForLaterUse' => true]);

        $this->paymentTransactionProvider->expects($this->once())
            ->method('getActiveValidatePaymentTransaction')
            ->willReturn($paymentTransaction);

        [$formView, $context] = $this->prepareMocks(true, true);

        $this->assertEquals(
            [
                'formView' => $formView,
                'creditCardComponent' => 'oropaypal/js/app/components/authorized-credit-card-component',
                'creditCardComponentOptions' => [
                    'acct' => '1111',
                    'saveForLaterUse' => true,
                    'allowedCreditCards' => self::ALLOWED_CC_TYPES,
                ],
            ],
            $this->methodView->getOptions($context)
        );
    }

    public function testGetOptionsWithAuthForRequiredAmount()
    {
        $paymentTransaction = new PaymentTransaction();
        $paymentTransaction
            ->setResponse(['ACCT' => '1111']);

        $this->paymentTransactionProvider->expects($this->once())
            ->method('getActiveValidatePaymentTransaction')
            ->willReturn($paymentTransaction);

        [$formView, $context] = $this->prepareMocks(true, false);

        $this->assertEquals(
            [
                'formView' => $formView,
                'creditCardComponent' => 'oropaypal/js/app/components/authorized-credit-card-component',
                'creditCardComponentOptions' => [
                    'acct' => '1111',
                    'saveForLaterUse' => false,
                    'allowedCreditCards' => self::ALLOWED_CC_TYPES,
                ],
            ],
            $this->methodView->getOptions($context)
        );
    }

    public function testGetBlock()
    {
        $this->assertEquals('_payment_methods_paypal_credit_card_widget', $this->methodView->getBlock());
    }

    public function testGetAllowedCreditCards()
    {
        $allowedCards = ['visa', 'mastercard'];

        $this->paymentConfig->expects($this->once())
            ->method('getAllowedCreditCards')
            ->willReturn($allowedCards);

        $this->assertEquals($allowedCards, $this->methodView->getAllowedCreditCards());
    }

    private function prepareMocks(bool $zeroAmountAuthEnabled, bool $requireCvvEntryEnabled): array
    {
        $formView = $this->createMock(FormView::class);
        $form = $this->createMock(FormInterface::class);

        $form->expects($this->once())
            ->method('createView')
            ->willReturn($formView);

        $formOptions = [
            'zeroAmountAuthorizationEnabled' => $zeroAmountAuthEnabled,
            'requireCvvEntryEnabled' => $requireCvvEntryEnabled,
        ];

        $this->formFactory->expects($this->once())
            ->method('create')
            ->with(CreditCardType::class, null, $formOptions)
            ->willReturn($form);

        $this->paymentConfig->expects($this->once())
            ->method('isZeroAmountAuthorizationEnabled')
            ->willReturn($zeroAmountAuthEnabled);

        $this->paymentConfig->expects($this->once())
            ->method('isRequireCvvEntryEnabled')
            ->willReturn($requireCvvEntryEnabled);

        $this->paymentConfig->expects($this->once())
            ->method('getAllowedCreditCards')
            ->willReturn(self::ALLOWED_CC_TYPES);

        $context = $this->createMock(PaymentContextInterface::class);

        return [$formView, $context];
    }

    public function testGetPaymentMethodIdentifier()
    {
        $this->paymentConfig->expects($this->once())
            ->method('getPaymentMethodIdentifier')
            ->willReturn('identifier');

        $this->assertEquals('identifier', $this->methodView->getPaymentMethodIdentifier());
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
