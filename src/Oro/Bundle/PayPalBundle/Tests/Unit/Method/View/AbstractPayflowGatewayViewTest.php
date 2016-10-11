<?php

namespace Oro\Bundle\PayPalBundle\Tests\Unit\Method\View;

use Symfony\Component\Form\FormFactoryInterface;

use Oro\Component\Testing\Unit\EntityTrait;
use Oro\Bundle\PayPalBundle\Method\Config\PayflowGatewayConfigInterface;
use Oro\Bundle\PayPalBundle\Method\View\PayflowGatewayView;
use Oro\Bundle\PayPalBundle\Form\Type\CreditCardType;
use Oro\Bundle\PaymentBundle\Entity\PaymentTransaction;
use Oro\Bundle\PaymentBundle\Method\View\PaymentMethodViewInterface;
use Oro\Bundle\PaymentBundle\Provider\PaymentTransactionProvider;

abstract class AbstractPayflowGatewayViewTest extends \PHPUnit_Framework_TestCase
{
    use EntityTrait;

    /** @var FormFactoryInterface|\PHPUnit_Framework_MockObject_MockObject */
    protected $formFactory;

    /** @var PaymentMethodViewInterface|PayflowGatewayView */
    protected $methodView;

    /** @var  PaymentTransactionProvider|\PHPUnit_Framework_MockObject_MockObject */
    protected $paymentTransactionProvider;

    /** @var PayflowGatewayConfigInterface|\PHPUnit_Framework_MockObject_MockObject */
    protected $paymentConfig;

    protected function setUp()
    {
        $this->formFactory = $this->getMockBuilder('Symfony\Component\Form\FormFactoryInterface')
            ->disableOriginalConstructor()
            ->getMock();

        $this->paymentTransactionProvider = $this
            ->getMockBuilder('Oro\Bundle\PaymentBundle\Provider\PaymentTransactionProvider')
            ->disableOriginalConstructor()
            ->getMock();

        $this->paymentConfig =
            $this->getMock('Oro\Bundle\PayPalBundle\Method\Config\PayflowGatewayConfigInterface');

        $this->methodView = $this->getMethodView();
    }

    /**
     * @return PaymentMethodViewInterface
     */
    abstract protected function getMethodView();

    /**
     * @return string
     */
    abstract protected function getZeroAmountKey();

    /**
     * @return string
     */
    abstract protected function getRequireCvvEntryKey();

    /**
     * @return string
     */
    abstract protected function getAuthForRequiredAmountKey();

    /**
     * @return string
     */
    abstract protected function getAllowedCCTypesKey();

    protected function tearDown()
    {
        unset(
            $this->methodView,
            $this->paymentConfig,
            $this->formFactory,
            $this->paymentTransactionProvider
        );
    }

    public function testGetOptionsWithoutZeroAmount()
    {
        $formView = $this->getMock('Symfony\Component\Form\FormView');
        $form = $this->getMock('Symfony\Component\Form\FormInterface');

        $form->expects($this->once())->method('createView')->willReturn($formView);

        $zeroAmountAuthEnabled = false;
        $requireCvvEntryEnabled = true;
        $allowedCCTypes = ['visa', 'mastercard'];

        $formOptions = [
            'zeroAmountAuthorizationEnabled' => $zeroAmountAuthEnabled,
            'requireCvvEntryEnabled' => $requireCvvEntryEnabled,
        ];

        $this->formFactory->expects($this->once())
            ->method('create')
            ->with(CreditCardType::NAME, null, $formOptions)
            ->willReturn($form);

        $this->paymentConfig->expects($this->once())
            ->method('isZeroAmountAuthorizationEnabled')
            ->willReturn($zeroAmountAuthEnabled);

        $this->paymentConfig->expects($this->once())
            ->method('isRequireCvvEntryEnabled')
            ->willReturn($requireCvvEntryEnabled);

        $this->paymentConfig->expects($this->once())
            ->method('getAllowedCreditCards')
            ->willReturn($allowedCCTypes);

        $this->paymentTransactionProvider->expects($this->never())->method('getActiveValidatePaymentTransaction');

        $this->assertEquals(
            [
                'formView' => $formView,
                'creditCardComponentOptions' => [
                    'allowedCreditCards' => $allowedCCTypes,
                ]
            ],
            $this->methodView->getOptions()
        );
    }

    public function testGetOptionsWithZeroAmountWithoutTransaction()
    {
        $formView = $this->getMock('Symfony\Component\Form\FormView');
        $form = $this->getMock('Symfony\Component\Form\FormInterface');

        $form->expects($this->once())->method('createView')->willReturn($formView);

        $zeroAmountAuthEnabled = true;
        $requireCvvEntryEnabled = true;
        $allowedCCTypes = ['visa', 'mastercard'];

        $formOptions = [
            'zeroAmountAuthorizationEnabled' => $zeroAmountAuthEnabled,
            'requireCvvEntryEnabled' => $requireCvvEntryEnabled,
        ];

        $this->formFactory->expects($this->once())
            ->method('create')
            ->with(CreditCardType::NAME, null, $formOptions)
            ->willReturn($form);

        $this->paymentConfig->expects($this->once())
            ->method('isZeroAmountAuthorizationEnabled')
            ->willReturn($zeroAmountAuthEnabled);

        $this->paymentConfig->expects($this->once())
            ->method('isRequireCvvEntryEnabled')
            ->willReturn($requireCvvEntryEnabled);

        $this->paymentConfig->expects($this->once())
            ->method('getAllowedCreditCards')
            ->willReturn($allowedCCTypes);

        $this->paymentTransactionProvider->expects($this->once())->method('getActiveValidatePaymentTransaction')
            ->willReturn(null);

        $this->assertEquals(
            [
                'formView' => $formView,
                'creditCardComponentOptions' => [
                    'allowedCreditCards' => $allowedCCTypes,
                ]
            ],
            $this->methodView->getOptions()
        );
    }

    public function testGetOptions()
    {
        $formView = $this->getMock('Symfony\Component\Form\FormView');
        $form = $this->getMock('Symfony\Component\Form\FormInterface');

        $form->expects($this->once())->method('createView')->willReturn($formView);

        $zeroAmountAuthEnabled = true;
        $requireCvvEntryEnabled = true;
        $allowedCCTypes = ['visa', 'mastercard'];

        $formOptions = [
            'zeroAmountAuthorizationEnabled' => $zeroAmountAuthEnabled,
            'requireCvvEntryEnabled' => $requireCvvEntryEnabled,
        ];

        $this->formFactory->expects($this->once())
            ->method('create')
            ->with(CreditCardType::NAME, null, $formOptions)
            ->willReturn($form);

        $this->paymentConfig->expects($this->once())
            ->method('isZeroAmountAuthorizationEnabled')
            ->willReturn($zeroAmountAuthEnabled);

        $this->paymentConfig->expects($this->once())
            ->method('isRequireCvvEntryEnabled')
            ->willReturn($requireCvvEntryEnabled);

        $this->paymentConfig->expects($this->once())
            ->method('getAllowedCreditCards')
            ->willReturn($allowedCCTypes);

        $paymentTransaction = new PaymentTransaction();
        $paymentTransaction->setResponse(['ACCT' => '1111']);

        $this->paymentTransactionProvider->expects($this->once())->method('getActiveValidatePaymentTransaction')
            ->willReturn($paymentTransaction);

        $this->assertEquals(
            [
                'formView' => $formView,
                'creditCardComponent' => 'oropaypal/js/app/components/authorized-credit-card-component',
                'creditCardComponentOptions' => [
                    'acct' => '1111',
                    'saveForLaterUse' => false,
                    'allowedCreditCards' => $allowedCCTypes,
                ],
            ],
            $this->methodView->getOptions()
        );
    }

    public function testGetOptionsWithLaterUse()
    {
        $formView = $this->getMock('Symfony\Component\Form\FormView');
        $form = $this->getMock('Symfony\Component\Form\FormInterface');

        $form->expects($this->once())->method('createView')->willReturn($formView);

        $zeroAmountAuthEnabled = true;
        $requireCvvEntryEnabled = true;
        $allowedCCTypes = ['visa', 'mastercard'];

        $formOptions = [
            'zeroAmountAuthorizationEnabled' => $zeroAmountAuthEnabled,
            'requireCvvEntryEnabled' => $requireCvvEntryEnabled,
        ];

        $this->formFactory->expects($this->once())
            ->method('create')
            ->with(CreditCardType::NAME, null, $formOptions)
            ->willReturn($form);

        $this->paymentConfig->expects($this->once())
            ->method('isZeroAmountAuthorizationEnabled')
            ->willReturn($zeroAmountAuthEnabled);

        $this->paymentConfig->expects($this->once())
            ->method('isRequireCvvEntryEnabled')
            ->willReturn($requireCvvEntryEnabled);

        $this->paymentConfig->expects($this->once())
            ->method('getAllowedCreditCards')
            ->willReturn($allowedCCTypes);

        $paymentTransaction = new PaymentTransaction();
        $paymentTransaction
            ->setResponse(['ACCT' => '1111'])
            ->setTransactionOptions(['saveForLaterUse' => true]);

        $this->paymentTransactionProvider->expects($this->once())->method('getActiveValidatePaymentTransaction')
            ->willReturn($paymentTransaction);

        $this->assertEquals(
            [
                'formView' => $formView,
                'creditCardComponent' => 'oropaypal/js/app/components/authorized-credit-card-component',
                'creditCardComponentOptions' => [
                    'acct' => '1111',
                    'saveForLaterUse' => true,
                    'allowedCreditCards' => $allowedCCTypes,
                ],
            ],
            $this->methodView->getOptions()
        );
    }

    public function testGetOptionsWithAuthForRequiredAmount()
    {
        $formView = $this->getMock('Symfony\Component\Form\FormView');
        $form = $this->getMock('Symfony\Component\Form\FormInterface');

        $form->expects($this->once())->method('createView')->willReturn($formView);

        $zeroAmountAuthEnabled = true;
        $requireCvvEntryEnabled = false;
        $allowedCCTypes = ['visa', 'mastercard'];

        $formOptions = [
            'zeroAmountAuthorizationEnabled' => $zeroAmountAuthEnabled,
            'requireCvvEntryEnabled' => $requireCvvEntryEnabled,
        ];

        $this->formFactory->expects($this->once())
            ->method('create')
            ->with(CreditCardType::NAME, null, $formOptions)
            ->willReturn($form);

        $this->paymentConfig->expects($this->once())
            ->method('isZeroAmountAuthorizationEnabled')
            ->willReturn($zeroAmountAuthEnabled);

        $this->paymentConfig->expects($this->once())
            ->method('isRequireCvvEntryEnabled')
            ->willReturn($requireCvvEntryEnabled);

        $this->paymentConfig->expects($this->once())
            ->method('getAllowedCreditCards')
            ->willReturn($allowedCCTypes);

        $paymentTransaction = new PaymentTransaction();
        $paymentTransaction
            ->setResponse(['ACCT' => '1111']);

        $this->paymentTransactionProvider->expects($this->once())->method('getActiveValidatePaymentTransaction')
            ->willReturn($paymentTransaction);

        $this->assertEquals(
            [
                'formView' => $formView,
                'creditCardComponent' => 'oropaypal/js/app/components/authorized-credit-card-component',
                'creditCardComponentOptions' => [
                    'acct' => '1111',
                    'saveForLaterUse' => false,
                    'allowedCreditCards' => $allowedCCTypes,
                ],
            ],
            $this->methodView->getOptions()
        );
    }

    public function testGetBlock()
    {
        $this->assertEquals('_payment_methods_credit_card_widget', $this->methodView->getBlock());
    }

    public function testGetAllowedCreditCards()
    {
        $allowedCards = ['visa', 'mastercard'];

        $this->paymentConfig->expects($this->once())
            ->method('getAllowedCreditCards')
            ->willReturn($allowedCards);

        $this->assertEquals($allowedCards, $this->methodView->getAllowedCreditCards());
    }
}
