<?php

namespace Oro\Bundle\AuthorizeNetBundle\Method\View;

use Oro\Bundle\AuthorizeNetBundle\Form\Type\CreditCardType;
use Oro\Bundle\AuthorizeNetBundle\Method\Config\AuthorizeNetConfigInterface;
use Oro\Bundle\PaymentBundle\Context\PaymentContextInterface;
use Oro\Bundle\PaymentBundle\Method\View\PaymentMethodViewInterface;
use Symfony\Component\Form\FormFactoryInterface;

class AuthorizeNetPaymentMethodView implements PaymentMethodViewInterface
{
    /**
     * @var FormFactoryInterface
     */
    protected $formFactory;

    /**
     * @var AuthorizeNetConfigInterface
     */
    protected $config;

    /**
     * @param FormFactoryInterface            $formFactory
     * @param AuthorizeNetConfigInterface     $config
     */
    public function __construct(
        FormFactoryInterface $formFactory,
        AuthorizeNetConfigInterface $config
    ) {
        $this->formFactory = $formFactory;
        $this->config = $config;
    }

    /**
     * {@inheritDoc}
     */
    public function getOptions(PaymentContextInterface $context)
    {
        $formOptions = [
            'requireCvvEntryEnabled' => $this->config->isRequireCvvEntryEnabled(),
        ];
        $formView = $this->formFactory->create(CreditCardType::NAME, null, $formOptions)->createView();

        return [
            'formView' => $formView,
            'creditCardComponentOptions' => [
                'allowedCreditCards' => $this->getAllowedCreditCards(),
                'clientKey' => $this->config->getClientKey(),
                'apiLoginID' => $this->config->getApiLoginId(),
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
