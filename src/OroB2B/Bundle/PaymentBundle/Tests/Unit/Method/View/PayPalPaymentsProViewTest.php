<?php

namespace OroB2B\Bundle\PaymentBundle\Tests\Unit\Method\View;

use Symfony\Component\Form\FormFactoryInterface;

use Oro\Component\Testing\Unit\EntityTrait;

use OroB2B\Bundle\PaymentBundle\Entity\PaymentTransaction;
use OroB2B\Bundle\PaymentBundle\PayPal\Payflow\Option\Account;
use OroB2B\Bundle\PaymentBundle\Method\PayPalPaymentsPro;
use OroB2B\Bundle\PaymentBundle\Provider\PayflowGatewayPaymentTransactionProvider;
use OroB2B\Bundle\PaymentBundle\Form\Type\CreditCardType;
use OroB2B\Bundle\PaymentBundle\Method\View\PayPalPaymentsProView;
use OroB2B\Bundle\PaymentBundle\DependencyInjection\Configuration;
use OroB2B\Bundle\PaymentBundle\Tests\Unit\Method\ConfigTestTrait;

class PayPalPaymentsProViewTest extends \PHPUnit_Framework_TestCase
{
    use ConfigTestTrait;
    use EntityTrait;

    /** @var FormFactoryInterface|\PHPUnit_Framework_MockObject_MockObject */
    protected $formFactory;

    /** @var PayPalPaymentsProView */
    protected $methodView;

    /** @var  PayflowGatewayPaymentTransactionProvider|\PHPUnit_Framework_MockObject_MockObject */
    protected $payflowGatewayPaymentTransactionProvider;

    protected function setUp()
    {
        $this->formFactory = $this->getMockBuilder('Symfony\Component\Form\FormFactoryInterface')
            ->disableOriginalConstructor()
            ->getMock();

        $this->configManager = $this->getMockBuilder('Oro\Bundle\ConfigBundle\Config\ConfigManager')
            ->disableOriginalConstructor()
            ->getMock();
        
        $this->payflowGatewayPaymentTransactionProvider = $this
            ->getMockBuilder('OroB2B\Bundle\PaymentBundle\Provider\PayflowGatewayPaymentTransactionProvider')
            ->disableOriginalConstructor()
            ->getMock();

        $this->methodView = new PayPalPaymentsProView(
            $this->formFactory,
            $this->configManager,
            $this->payflowGatewayPaymentTransactionProvider
        );
    }

    protected function tearDown()
    {
        unset(
            $this->methodView,
            $this->configManager,
            $this->formFactory,
            $this->payflowGatewayPaymentTransactionProvider
        );
    }

    /**
     * @dataProvider optionsProvider
     * @param array $data
     */
    public function testGetOptions($data)
    {
        $formView = $this->getMock('Symfony\Component\Form\FormView');

        $form = $this->getMock('Symfony\Component\Form\FormInterface');
        $form->expects($this->once())
            ->method('createView')
            ->willReturn($formView);

        $this->formFactory->expects($this->once())
            ->method('create')
            ->with(CreditCardType::NAME)
            ->willReturn($form);

        $this->configManager->expects($this->exactly(2))
            ->method('get')
            ->withConsecutive($data['configsData'][0], $data['configsData'][1])
            ->willReturnOnConsecutiveCalls($data['returnConfigs'][0], $data['returnConfigs'][1]);

        $expected = [
            'formView' => $formView,
            'allowedCreditCards' => $data['allowedCards'],
        ];

        $entity = new \stdClass();

        $transactionEntity = null;

        if ($data['transactionEntityOptions'] !== null) {
            /** @var PaymentTransaction $transactionEntity */
            $transactionEntity = $this->getEntity(
                'OroB2B\Bundle\PaymentBundle\Entity\PaymentTransaction',
                $data['transactionEntityOptions']
            );
        }

        if ($data['zero_amount']) {
            $this->payflowGatewayPaymentTransactionProvider->expects($this->once())
                ->method('getZeroAmountTransaction')
                ->with($entity, PayPalPaymentsPro::TYPE)
                ->willReturn($transactionEntity);

            if ($transactionEntity) {
                $expected = array_merge(
                    $expected,
                    [
                        'authorizeTransaction' => $transactionEntity->getId(),
                        'acct' => $data['last4']
                    ]
                );
            }
        }

        $this->assertEquals($expected, $this->methodView->getOptions(['entity' => $entity]));
    }

    /**
     * @return array
     */
    public function optionsProvider()
    {
        return [
            [
                [
                    'allowedCards' => ['visa', 'mastercard'],
                    'configsData' => [
                        [
                            $this->getConfigKey(Configuration::PAYPAL_PAYMENTS_PRO_ALLOWED_CC_TYPES_KEY)
                        ],
                        [
                             $this->getConfigKey(Configuration::PAYPAL_PAYMENTS_PRO_ZERO_AMOUNT_AUTHORIZATION_KEY)
                        ],
                    ],
                    'zero_amount' => true,
                    'returnConfigs' => [
                        ['visa', 'mastercard'],
                        true
                    ],
                    'transactionEntityOptions' => [
                        'id' => 5,
                        'response' => [Account::ACCT => '3211234']
                    ],
                    'last4' => '1234'
                ]
            ],
        ];
    }

    public function testGetOrder()
    {
        $order = '100';
        $this->setConfig($this->once(), Configuration::PAYPAL_PAYMENTS_PRO_SORT_ORDER_KEY, $order);

        $this->assertSame((int)$order, $this->methodView->getOrder());
    }

    public function testGetPaymentMethodType()
    {
        $this->assertEquals('paypal_payments_pro', $this->methodView->getPaymentMethodType());
    }

    public function testGetLabel()
    {
        $this->setConfig($this->once(), Configuration::PAYPAL_PAYMENTS_PRO_LABEL_KEY, 'testValue');
        $this->assertEquals('testValue', $this->methodView->getLabel());
    }

    public function testGetAllowedCreditCards()
    {
        $allowedCards = ['visa', 'mastercard'];
        $this->setConfig($this->once(), Configuration::PAYPAL_PAYMENTS_PRO_ALLOWED_CC_TYPES_KEY, $allowedCards);
        $this->assertEquals($allowedCards, $this->methodView->getAllowedCreditCards());
    }
}
