<?php

namespace OroB2B\Bundle\PaymentBundle\Tests\Unit\Method\View;

use Symfony\Component\Form\FormFactoryInterface;

use Oro\Component\Testing\Unit\EntityTrait;
use OroB2B\Bundle\PaymentBundle\Entity\PaymentTransaction;
use OroB2B\Bundle\PaymentBundle\Form\Type\CreditCardType;
use OroB2B\Bundle\PaymentBundle\Method\View\PayflowGatewayView;
use OroB2B\Bundle\PaymentBundle\Method\View\PaymentMethodViewInterface;
use OroB2B\Bundle\PaymentBundle\Provider\PaymentTransactionProvider;
use OroB2B\Bundle\PaymentBundle\Tests\Unit\Method\ConfigTestTrait;

abstract class AbstractPayflowGatewayViewTest extends \PHPUnit_Framework_TestCase
{
    use ConfigTestTrait;
    use EntityTrait;

    /** @var FormFactoryInterface|\PHPUnit_Framework_MockObject_MockObject */
    protected $formFactory;

    /** @var PaymentMethodViewInterface|PayflowGatewayView */
    protected $methodView;

    /** @var  PaymentTransactionProvider|\PHPUnit_Framework_MockObject_MockObject */
    protected $paymentTransactionProvider;

    protected function setUp()
    {
        $this->formFactory = $this->getMockBuilder('Symfony\Component\Form\FormFactoryInterface')
            ->disableOriginalConstructor()
            ->getMock();

        $this->configManager = $this->getMockBuilder('Oro\Bundle\ConfigBundle\Config\ConfigManager')
            ->disableOriginalConstructor()
            ->getMock();

        $this->paymentTransactionProvider = $this
            ->getMockBuilder('OroB2B\Bundle\PaymentBundle\Provider\PaymentTransactionProvider')
            ->disableOriginalConstructor()
            ->getMock();

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
    abstract protected function getAuthForRequiredAmountKey();

    /**
     * @return string
     */
    abstract protected function getAllowedCCTypesKey();

    protected function tearDown()
    {
        unset(
            $this->methodView,
            $this->configManager,
            $this->formFactory,
            $this->paymentTransactionProvider
        );
    }

    public function testGetOptionsWithoutZeroAmount()
    {
        $formView = $this->getMock('Symfony\Component\Form\FormView');
        $form = $this->getMock('Symfony\Component\Form\FormInterface');

        $form->expects($this->once())->method('createView')->willReturn($formView);

        $this->formFactory->expects($this->once())
            ->method('create')
            ->with(CreditCardType::NAME)
            ->willReturn($form);

        $this->configManager->expects($this->exactly(2))
            ->method('get')
            ->withConsecutive(
                [
                    $this->getConfigKey($this->getZeroAmountKey()),
                ],
                [
                    $this->getConfigKey($this->getAllowedCCTypesKey()),
                ]
            )
            ->willReturnOnConsecutiveCalls(false, ['visa', 'mastercard']);

        $this->paymentTransactionProvider->expects($this->never())->method('getActiveValidatePaymentTransaction');

        $this->assertEquals(
            [
                'formView' => $formView,
                'creditCardComponentOptions' => [
                    'allowedCreditCards' => ['visa', 'mastercard'],
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

        $this->formFactory->expects($this->once())
            ->method('create')
            ->with(CreditCardType::NAME)
            ->willReturn($form);

        $this->configManager->expects($this->exactly(2))
            ->method('get')
            ->withConsecutive(
                [
                    $this->getConfigKey($this->getZeroAmountKey()),
                ],
                [
                    $this->getConfigKey($this->getAllowedCCTypesKey()),
                ]
            )
            ->willReturnOnConsecutiveCalls(true, ['visa', 'mastercard']);

        $this->paymentTransactionProvider->expects($this->once())->method('getActiveValidatePaymentTransaction')
            ->willReturn(null);

        $this->assertEquals(
            [
                'formView' => $formView,
                'creditCardComponentOptions' => [
                    'allowedCreditCards' => ['visa', 'mastercard'],
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

        $this->formFactory->expects($this->once())
            ->method('create')
            ->with(CreditCardType::NAME)
            ->willReturn($form);

        $this->configManager->expects($this->exactly(3))
            ->method('get')
            ->withConsecutive(
                [
                    $this->getConfigKey($this->getZeroAmountKey()),
                ],
                [
                    $this->getConfigKey($this->getAllowedCCTypesKey()),
                ],
                [
                    $this->getConfigKey($this->getAuthForRequiredAmountKey()),
                ]
            )
            ->willReturnOnConsecutiveCalls(true, ['visa', 'mastercard'], false);

        $paymentTransaction = new PaymentTransaction();
        $paymentTransaction->setResponse(['ACCT' => '1111']);

        $this->paymentTransactionProvider->expects($this->once())->method('getActiveValidatePaymentTransaction')
            ->willReturn($paymentTransaction);

        $this->assertEquals(
            [
                'formView' => $formView,
                'creditCardComponent' => 'orob2bpayment/js/app/components/authorized-credit-card-component',
                'creditCardComponentOptions' => [
                    'acct' => '1111',
                    'saveForLaterUse' => false,
                    'authorizationForRequiredAmount' => false,
                    'allowedCreditCards' => ['visa', 'mastercard'],
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

        $this->formFactory->expects($this->once())
            ->method('create')
            ->with(CreditCardType::NAME)
            ->willReturn($form);

        $this->configManager->expects($this->exactly(3))
            ->method('get')
            ->withConsecutive(
                [
                    $this->getConfigKey($this->getZeroAmountKey()),
                ],
                [
                    $this->getConfigKey($this->getAllowedCCTypesKey()),
                ],
                [
                    $this->getConfigKey($this->getAuthForRequiredAmountKey()),
                ]
            )
            ->willReturnOnConsecutiveCalls(true, ['visa', 'mastercard'], false);

        $paymentTransaction = new PaymentTransaction();
        $paymentTransaction
            ->setResponse(['ACCT' => '1111'])
            ->setTransactionOptions(['saveForLaterUse' => true]);

        $this->paymentTransactionProvider->expects($this->once())->method('getActiveValidatePaymentTransaction')
            ->willReturn($paymentTransaction);

        $this->assertEquals(
            [
                'formView' => $formView,
                'creditCardComponent' => 'orob2bpayment/js/app/components/authorized-credit-card-component',
                'creditCardComponentOptions' => [
                    'acct' => '1111',
                    'saveForLaterUse' => true,
                    'authorizationForRequiredAmount' => false,
                    'allowedCreditCards' => ['visa', 'mastercard'],
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

        $this->formFactory->expects($this->once())
            ->method('create')
            ->with(CreditCardType::NAME)
            ->willReturn($form);

        $this->configManager->expects($this->exactly(3))
            ->method('get')
            ->withConsecutive(
                [
                    $this->getConfigKey($this->getZeroAmountKey()),
                ],
                [
                    $this->getConfigKey($this->getAllowedCCTypesKey()),
                ],
                [
                    $this->getConfigKey($this->getAuthForRequiredAmountKey()),
                ]
            )
            ->willReturnOnConsecutiveCalls(true, ['visa', 'mastercard'], true);

        $paymentTransaction = new PaymentTransaction();
        $paymentTransaction
            ->setResponse(['ACCT' => '1111']);

        $this->paymentTransactionProvider->expects($this->once())->method('getActiveValidatePaymentTransaction')
            ->willReturn($paymentTransaction);

        $this->assertEquals(
            [
                'formView' => $formView,
                'creditCardComponent' => 'orob2bpayment/js/app/components/authorized-credit-card-component',
                'creditCardComponentOptions' => [
                    'acct' => '1111',
                    'saveForLaterUse' => false,
                    'authorizationForRequiredAmount' => true,
                    'allowedCreditCards' => ['visa', 'mastercard'],
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
        $this->setConfig($this->once(), $this->getAllowedCCTypesKey(), $allowedCards);
        $this->assertEquals($allowedCards, $this->methodView->getAllowedCreditCards());
    }
}
