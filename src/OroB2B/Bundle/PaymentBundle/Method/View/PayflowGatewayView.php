<?php

namespace OroB2B\Bundle\PaymentBundle\Method\View;

use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

use Oro\Bundle\ConfigBundle\Config\ConfigManager;

use OroB2B\Bundle\PaymentBundle\DependencyInjection\Configuration;
use OroB2B\Bundle\PaymentBundle\Entity\PaymentTransaction;
use OroB2B\Bundle\PaymentBundle\Form\Type\CreditCardType;
use OroB2B\Bundle\PaymentBundle\Method\PayflowGateway;
use OroB2B\Bundle\PaymentBundle\PayPal\Payflow\Option\Account;
use OroB2B\Bundle\PaymentBundle\PayPal\Payflow\Response\Response;
use OroB2B\Bundle\PaymentBundle\Provider\PaymentTransactionProvider;
use OroB2B\Bundle\PaymentBundle\Traits\ConfigTrait;

class PayflowGatewayView implements PaymentMethodViewInterface
{
    use ConfigTrait;

    /** @var FormFactoryInterface */
    protected $formFactory;

    /** @var OptionsResolver */
    private $optionsResolver;

    /** @var PaymentTransactionProvider */
    protected $paymentTransactionProvider;

    /**
     * @param FormFactoryInterface $formFactory
     * @param ConfigManager $configManager
     * @param PaymentTransactionProvider $paymentTransactionProvider
     */
    public function __construct(
        FormFactoryInterface $formFactory,
        ConfigManager $configManager,
        PaymentTransactionProvider $paymentTransactionProvider
    ) {
        $this->formFactory = $formFactory;
        $this->configManager = $configManager;
        $this->paymentTransactionProvider = $paymentTransactionProvider;
    }

    /** {@inheritdoc} */
    public function getOptions(array $context = [])
    {
        $contextOptions = $this->getOptionsResolver()->resolve($context);

        $formView = $this->formFactory->create(CreditCardType::NAME)->createView();

        $viewOptions = [
            'formView' => $formView,
            'allowedCreditCards' => $this->getAllowedCreditCards(),
        ];

        if ($this->isZeroAmountAuthorizationEnabled()) {
            $authorizeTransaction = $this->paymentTransactionProvider
                ->getActiveValidatePaymentTransaction($contextOptions['entity'], $this->getPaymentMethodType());

            if ($authorizeTransaction) {
                $viewOptions['authorizeTransaction'] = $authorizeTransaction->getId();
                $viewOptions['acct'] = $this->getLast4($authorizeTransaction);
            }
        }

        return $viewOptions;
    }

    /**
     * @param PaymentTransaction $paymentTransaction
     * @return string|null
     */
    protected function getLast4(PaymentTransaction $paymentTransaction)
    {
        $response = new Response($paymentTransaction->getResponse());

        $acct = $response->getOffset(Account::ACCT);

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
        return (int)$this->getConfigValue(Configuration::PAYFLOW_GATEWAY_SORT_ORDER_KEY);
    }

    /** {@inheritdoc} */
    public function getPaymentMethodType()
    {
        return PayflowGateway::TYPE;
    }

    /** {@inheritdoc} */
    public function getLabel()
    {
        return (string)$this->getConfigValue(Configuration::PAYFLOW_GATEWAY_LABEL_KEY);
    }

    /**
     * @return array
     */
    public function getAllowedCreditCards()
    {
        return (array)$this->getConfigValue(Configuration::PAYFLOW_GATEWAY_ALLOWED_CC_TYPES_KEY);
    }

    /**
     * @return OptionsResolver
     */
    public function getOptionsResolver()
    {
        if (!$this->optionsResolver) {
            $this->optionsResolver = new OptionsResolver();
            $this->optionsResolver
                ->setRequired('entity')
                ->addAllowedTypes('entity', ['object']);
        }

        return $this->optionsResolver;
    }

    /**
     * @return bool
     */
    protected function isZeroAmountAuthorizationEnabled()
    {
        return (bool)$this->getConfigValue(Configuration::PAYFLOW_GATEWAY_ZERO_AMOUNT_AUTHORIZATION_KEY);
    }
}
