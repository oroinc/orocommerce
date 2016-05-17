<?php

namespace OroB2B\Bundle\PaymentBundle\Method\View;

use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;
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

    /** @var DoctrineHelper */
    protected $doctrineHelper;

    /**
     * @param FormFactoryInterface $formFactory
     * @param ConfigManager $configManager
     * @param PaymentTransactionProvider $paymentTransactionProvider
     * @param DoctrineHelper $doctrineHelper
     */
    public function __construct(
        FormFactoryInterface $formFactory,
        ConfigManager $configManager,
        PaymentTransactionProvider $paymentTransactionProvider,
        DoctrineHelper $doctrineHelper
    ) {
        $this->formFactory = $formFactory;
        $this->configManager = $configManager;
        $this->paymentTransactionProvider = $paymentTransactionProvider;
        $this->doctrineHelper = $doctrineHelper;
    }

    /** {@inheritdoc} */
    public function getOptions(array $context = [])
    {
        $contextOptions = $this->getOptionsResolver()->resolve($context);

        $formOptions = [
            'zeroAmountAuthorizationEnabled' => $this->isZeroAmountAuthorizationEnabled()
        ];

        $formView = $this->formFactory->create(CreditCardType::NAME, null, $formOptions)->createView();

        $viewOptions = [
            'formView' => $formView,
            'allowedCreditCards' => $this->getAllowedCreditCards(),
        ];

        if ($this->isZeroAmountAuthorizationEnabled()) {
            $validateTransaction = $this->paymentTransactionProvider
                ->getActiveValidatePaymentTransaction($contextOptions['entity'], $this->getPaymentMethodType());

            if ($validateTransaction) {
                $entityClass = $this->doctrineHelper->getEntityClass($contextOptions['entity']);
                $entityIdentifier = $this->doctrineHelper->getSingleEntityIdentifier($contextOptions['entity']);

                $currentValidation = $validateTransaction->getEntityClass() === $entityClass &&
                    $validateTransaction->getEntityIdentifier() === $entityIdentifier;

                $transactionOptions = $validateTransaction->getTransactionOptions();
                $saveForLaterUse = isset($transactionOptions['saveForLaterUse']) ?
                    $transactionOptions['saveForLaterUse'] : false;

                $viewOptions['creditCardComponent'] =
                    'orob2bpayment/js/app/components/authorized-credit-card-component';

                $viewOptions['creditCardComponentOptions'] = [
                    'acct' => $this->getLast4($validateTransaction),
//                    'currentValidation' => $currentValidation,
                    'saveForLaterUse' => $saveForLaterUse
                ];
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
