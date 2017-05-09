<?php

namespace Oro\Bundle\AuthorizeNetBundle\Tests\Unit\Method\View;

use Oro\Bundle\AuthorizeNetBundle\Method\Config\AuthorizeNetConfigInterface;
use Oro\Bundle\PaymentBundle\Context\PaymentContextInterface;
use Oro\Bundle\AuthorizeNetBundle\Method\View\AuthorizeNetPaymentMethodView;
use Oro\Bundle\AuthorizeNetBundle\Form\Type\CreditCardType;
use Oro\Component\Testing\Unit\EntityTrait;
use Symfony\Component\Form\FormFactoryInterface;

class AuthorizeNetPaymentMethodViewTest extends \PHPUnit_Framework_TestCase
{
    use EntityTrait;
    const ALLOWED_CC_TYPES = ['visa', 'mastercard'];

    /** @var FormFactoryInterface|\PHPUnit_Framework_MockObject_MockObject */
    protected $formFactory;

    /** @var AuthorizeNetPaymentMethodView */
    protected $methodView;

    /** @var AuthorizeNetConfigInterface|\PHPUnit_Framework_MockObject_MockObject */
    protected $paymentConfig;

    protected function setUp()
    {
        $this->formFactory = $this->createMock(FormFactoryInterface::class);
        $this->paymentConfig = $this->createMock(AuthorizeNetConfigInterface::class);

        $this->methodView = new AuthorizeNetPaymentMethodView(
            $this->formFactory,
            $this->paymentConfig
        );
    }

    public function testGetOptions()
    {
        list($formView, $context) = $this->prepareMocks(false);

        $this->assertEquals(
            [
                'formView' => $formView,
                'creditCardComponentOptions' => [
                    'allowedCreditCards' => self::ALLOWED_CC_TYPES,
                    'clientKey' => 'client key',
                    'apiLoginID' => 'api login id',
                    'testMode' => true,
                ],
            ],
            $this->methodView->getOptions($context)
        );
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

    public function testGetBlock()
    {
        $this->assertEquals('_payment_methods_au_net_credit_card_widget', $this->methodView->getBlock());
    }

    public function testGetAllowedCreditCards()
    {
        $allowedCards = ['visa', 'mastercard'];

        $this->paymentConfig->expects($this->once())
            ->method('getAllowedCreditCards')
            ->willReturn($allowedCards);

        $this->assertEquals($allowedCards, $this->methodView->getAllowedCreditCards());
    }

    /**
     * @param $requireCvvEntryEnabled
     * @return array|\PHPUnit_Framework_MockObject_MockObject[]
     */
    protected function prepareMocks($requireCvvEntryEnabled)
    {
        $formView = $this->createMock('Symfony\Component\Form\FormView');
        $form = $this->createMock('Symfony\Component\Form\FormInterface');

        $form->expects($this->once())->method('createView')->willReturn($formView);

        $formOptions = ['requireCvvEntryEnabled' => $requireCvvEntryEnabled];

        $this->formFactory->expects($this->once())
            ->method('create')
            ->with(CreditCardType::NAME, null, $formOptions)
            ->willReturn($form);

        $this->paymentConfig->expects($this->once())
            ->method('getAllowedCreditCards')
            ->willReturn(self::ALLOWED_CC_TYPES);

        $this->paymentConfig->expects($this->once())
            ->method('getApiLoginId')
            ->willReturn('api login id');

        $this->paymentConfig->expects($this->once())
            ->method('getClientKey')
            ->willReturn('client key');

        $this->paymentConfig->expects($this->once())
            ->method('isTestMode')
            ->willReturn(true);

        /** @var PaymentContextInterface|\PHPUnit_Framework_MockObject_MockObject $context */
        $context = $this->createMock(PaymentContextInterface::class);

        return array($formView, $context);
    }
}
