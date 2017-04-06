<?php

namespace Oro\Bundle\AuthorizeNetBundle\Method\View;

use Oro\Bundle\AuthorizeNetBundle\Form\Type\CreditCardType;
use Oro\Bundle\AuthorizeNetBundle\Method\Config\AuthorizeNetConfigInterface;
use Oro\Bundle\PaymentBundle\Context\PaymentContextInterface;
use Oro\Bundle\PaymentBundle\Entity\PaymentTransaction;
use Oro\Bundle\PaymentBundle\Method\View\PaymentMethodViewInterface;
use Oro\Bundle\PaymentBundle\Provider\PaymentTransactionProvider;
use Symfony\Component\Form\FormFactoryInterface;

class AuthorizeNetPaymentMethodView implements PaymentMethodViewInterface
{
    /**
     * @var FormFactoryInterface
     */
    protected $formFactory;

    /**
     * @var PaymentTransactionProvider
     */
    protected $paymentTransactionProvider;

    /**
     * @var AuthorizeNetConfigInterface
     */
    protected $config;

    /**
     * @param FormFactoryInterface            $formFactory
     * @param AuthorizeNetConfigInterface     $config
     * @param PaymentTransactionProvider      $paymentTransactionProvider
     */
    public function __construct(
        FormFactoryInterface $formFactory,
        AuthorizeNetConfigInterface $config,
        PaymentTransactionProvider $paymentTransactionProvider
    ) {
        $this->formFactory = $formFactory;
        $this->config = $config;
        $this->paymentTransactionProvider = $paymentTransactionProvider;
    }

    /**
     * {@inheritDoc}
     */
    public function getOptions(PaymentContextInterface $context)
    {
        $formOptions = [
            'requireCvvEntryEnabled' => false,// TODO: change to $this->config->isRequireCvvEntryEnabled(),
        ];
        $formView = $this->formFactory->create(CreditCardType::NAME, null, $formOptions)->createView();

        return [
            'formView' => $formView,
            'creditCardComponentOptions' => [
                'allowedCreditCards' => $this->getAllowedCreditCards(),
                'clientKey' => $this->config->getClientKey(),
                'apiLoginID' => $this->config->getApiLogin(),
                'testMode' => $this->config->isTestMode(),
            ],
        ];
    }

    /**
     * {@inheritDoc}
     */
    public function getBlock()
    {
        return '_payment_methods_au_net_credit_card_widget';
    }

    /**
     * {@inheritDoc}
     */
    public function getLabel()
    {
        return $this->config->getLabel();
    }

    /**
     * {@inheritDoc}
     */
    public function getShortLabel()
    {
        return $this->config->getShortLabel();
    }

    /**
     * {@inheritDoc}
     */
    public function getAdminLabel()
    {
        return $this->config->getAdminLabel();
    }

    /**
     * @return array
     */
    public function getAllowedCreditCards()
    {
        return $this->config->getAllowedCreditCards();
    }

    /**
     * {@inheritDoc}
     */
    public function getPaymentMethodIdentifier()
    {
        return $this->config->getPaymentMethodIdentifier();
    }
}
