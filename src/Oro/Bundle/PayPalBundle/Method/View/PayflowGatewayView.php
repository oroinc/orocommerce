<?php

namespace Oro\Bundle\PayPalBundle\Method\View;

use Symfony\Component\Form\FormFactoryInterface;

use Oro\Bundle\PayPalBundle\Method\Config\PayflowGatewayConfigInterface;
use Oro\Bundle\PayPalBundle\Method\PayflowGateway;
use Oro\Bundle\PayPalBundle\PayPal\Payflow\Gateway\Option as GatewayOption;
use Oro\Bundle\PayPalBundle\PayPal\Payflow\Response\Response;
use Oro\Bundle\PayPalBundle\Form\Type\CreditCardType;

use OroB2B\Bundle\PaymentBundle\Entity\PaymentTransaction;
use OroB2B\Bundle\PaymentBundle\Provider\PaymentTransactionProvider;
use OroB2B\Bundle\PaymentBundle\Method\View\PaymentMethodViewInterface;

class PayflowGatewayView implements PaymentMethodViewInterface
{
    /** @var FormFactoryInterface */
    protected $formFactory;

    /** @var PaymentTransactionProvider */
    protected $paymentTransactionProvider;

    /** @var PayflowGatewayConfigInterface */
    protected $config;

    /**
     * @param FormFactoryInterface $formFactory
     * @param PayflowGatewayConfigInterface $config
     * @param PaymentTransactionProvider $paymentTransactionProvider
     */
    public function __construct(
        FormFactoryInterface $formFactory,
        PayflowGatewayConfigInterface $config,
        PaymentTransactionProvider $paymentTransactionProvider
    ) {
        $this->formFactory = $formFactory;
        $this->config = $config;
        $this->paymentTransactionProvider = $paymentTransactionProvider;
    }

    /** {@inheritdoc} */
    public function getOptions(array $context = [])
    {
        $isZeroAmountAuthorizationEnabled = $this->config->isZeroAmountAuthorizationEnabled();

        $formOptions = [
            'zeroAmountAuthorizationEnabled' => $isZeroAmountAuthorizationEnabled,
            'requireCvvEntryEnabled' => $this->config->isRequireCvvEntryEnabled(),
        ];

        $formView = $this->formFactory->create(CreditCardType::NAME, null, $formOptions)->createView();

        $viewOptions = [
            'formView' => $formView,
            'creditCardComponentOptions' => [
                'allowedCreditCards' => $this->getAllowedCreditCards(),
            ],
        ];

        if (!$isZeroAmountAuthorizationEnabled) {
            return $viewOptions;
        }

        $validateTransaction = $this->paymentTransactionProvider
            ->getActiveValidatePaymentTransaction($this->getPaymentMethodType());

        if (!$validateTransaction) {
            return $viewOptions;
        }

        $transactionOptions = $validateTransaction->getTransactionOptions();

        $viewOptions['creditCardComponent'] = 'oropaypal/js/app/components/authorized-credit-card-component';

        $viewOptions['creditCardComponentOptions'] = array_merge($viewOptions['creditCardComponentOptions'], [
            'acct' => $this->getLast4($validateTransaction),
            'saveForLaterUse' => !empty($transactionOptions['saveForLaterUse']),
        ]);

        return $viewOptions;
    }

    /**
     * @param PaymentTransaction $paymentTransaction
     * @return string|null
     */
    protected function getLast4(PaymentTransaction $paymentTransaction)
    {
        $response = new Response($paymentTransaction->getResponse());

        $acct = $response->getOffset(GatewayOption\Account::ACCT);

        return substr($acct, -4);
    }

    /** {@inheritdoc} */
    public function getBlock()
    {
        return '_payment_methods_credit_card_widget';
    }

    /** {@inheritdoc} */
    public function getOrder()
    {
        return $this->config->getOrder();
    }

    /** {@inheritdoc} */
    public function getPaymentMethodType()
    {
        return PayflowGateway::TYPE;
    }

    /** {@inheritdoc} */
    public function getLabel()
    {
        return $this->config->getLabel();
    }

    /** {@inheritdoc} */
    public function getShortLabel()
    {
        return $this->config->getShortLabel();
    }

    /**
     * @return array
     */
    public function getAllowedCreditCards()
    {
        return $this->config->getAllowedCreditCards();
    }
}
