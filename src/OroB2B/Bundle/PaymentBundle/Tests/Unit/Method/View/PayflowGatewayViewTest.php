<?php

namespace OroB2B\Bundle\PaymentBundle\Tests\Unit\Method\View;

use Symfony\Component\Form\FormFactoryInterface;

use Oro\Component\Testing\Unit\EntityTrait;
use Oro\Bundle\ConfigBundle\Config\ConfigManager;

use OroB2B\Bundle\PaymentBundle\Entity\PaymentTransaction;
use OroB2B\Bundle\PaymentBundle\Method\PayflowGateway;
use OroB2B\Bundle\PaymentBundle\PayPal\Payflow\Option\Account;
use OroB2B\Bundle\PaymentBundle\Provider\PaymentTransactionProvider;
use OroB2B\Bundle\PaymentBundle\Method\View\PayflowGatewayView;
use OroB2B\Bundle\PaymentBundle\Form\Type\CreditCardType;
use OroB2B\Bundle\PaymentBundle\DependencyInjection\Configuration;
use OroB2B\Bundle\PaymentBundle\Tests\Unit\Method\ConfigTestTrait;

class PayflowGatewayViewTest extends \PHPUnit_Framework_TestCase
{
    use ConfigTestTrait;
    use EntityTrait;

    /** @var ConfigManager|\PHPUnit_Framework_MockObject_MockObject */
    protected $configManager;

    /** @var FormFactoryInterface|\PHPUnit_Framework_MockObject_MockObject */
    protected $formFactory;

    /** @var PayflowGatewayView */
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

        $this->methodView = new PayflowGatewayView(
            $this->formFactory,
            $this->configManager,
            $this->paymentTransactionProvider
        );
    }

    protected function tearDown()
    {
        unset(
            $this->methodView,
            $this->configManager,
            $this->formFactory,
            $this->paymentTransactionProvider
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
        
        $expected = [
            'formView' => $formView,
            'allowedCreditCards' => $data['allowedCards'],
        ];

        $this->configManager->expects($this->exactly(2))
            ->method('get')
            ->withConsecutive($data['configsData'][0], $data['configsData'][1])
            ->willReturnOnConsecutiveCalls($data['returnConfigs'][0], $data['returnConfigs'][1]);
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
            $this->paymentTransactionProvider->expects($this->once())
                ->method('getActiveValidatePaymentTransaction')
                ->with($entity, PayflowGateway::TYPE)
                ->willReturn($transactionEntity);

            if ($transactionEntity) {
                $expected = array_merge(
                    $expected,
                    [
                        'creditCardComponent' => 'orob2bpayment/js/app/components/authorized-credit-card-component',
                        'creditCardComponentOptions' => [
                            'acct' => $data['last4']
                        ]
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
                            $this->getConfigKey(Configuration::PAYFLOW_GATEWAY_ALLOWED_CC_TYPES_KEY)
                        ],
                        [
                            $this->getConfigKey(Configuration::PAYFLOW_GATEWAY_ZERO_AMOUNT_AUTHORIZATION_KEY)
                        ],
                    ],
                    'zero_amount' => true,
                    'returnConfigs' => [
                        ['visa', 'mastercard'],
                        true
                    ],
                    'transactionEntityOptions' => [
                        'id' => 5,
                        'response' => [Account::ACCT => '1234567']
                    ],
                    'last4' => '4567'
                ]
            ],
        ];
    }

    public function testGetBlock()
    {
        $this->assertEquals('_payment_methods_credit_card_widget', $this->methodView->getBlock());
    }

    public function testGetOrder()
    {
        $order = '100';
        $this->setConfig($this->once(), Configuration::PAYFLOW_GATEWAY_SORT_ORDER_KEY, $order);

        $this->assertSame((int)$order, $this->methodView->getOrder());
    }

    public function testGetPaymentMethodType()
    {
        $this->assertEquals('payflow_gateway', $this->methodView->getPaymentMethodType());
    }

    public function testGetLabel()
    {
        $this->setConfig($this->once(), Configuration::PAYFLOW_GATEWAY_LABEL_KEY, 'testValue');
        $this->assertEquals('testValue', $this->methodView->getLabel());
    }
}
