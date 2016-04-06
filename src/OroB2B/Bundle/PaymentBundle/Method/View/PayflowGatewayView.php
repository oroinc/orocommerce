<?php

namespace OroB2B\Bundle\PaymentBundle\Method\View;

use Symfony\Component\Form\FormFactoryInterface;

use Oro\Bundle\ConfigBundle\Config\ConfigManager;

use OroB2B\Bundle\PaymentBundle\Traits\ConfigTrait;
use OroB2B\Bundle\PaymentBundle\DependencyInjection\Configuration;
use OroB2B\Bundle\PaymentBundle\Form\Type\CreditCardType;
use OroB2B\Bundle\PaymentBundle\Method\PayflowGateway;

class PayflowGatewayView implements PaymentMethodViewInterface
{
    use ConfigTrait;

    /**
     * @var array
     */
    protected $cardNamesMap = [
        'visa' => 'visa',
        'mastercard' => 'mc',
        'discover' => 'discover',
        'american_express' => 'ae'
    ];

    /** @var FormFactoryInterface */
    protected $formFactory;

    /**
     * @param FormFactoryInterface $formFactory
     * @param ConfigManager $configManager
     */
    public function __construct(FormFactoryInterface $formFactory, ConfigManager $configManager)
    {
        $this->formFactory = $formFactory;
        $this->configManager = $configManager;
    }

    /** {@inheritdoc} */
    public function getOptions()
    {
        $formView = $this->formFactory->create(CreditCardType::NAME)->createView();

        return [
            'formView' => $formView,
            'allowedCreditCards' => $this->mapAllowedCreditCards($this->getAllowedCreditCards())
        ];
    }

    /** {@inheritdoc} */
    public function getTemplate()
    {
        return 'OroB2BPaymentBundle:PaymentMethod:form.html.twig';
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
        return $this->getConfigValue(Configuration::PAYFLOW_GATEWAY_LABEL_KEY);
    }

    /**
     * @return mixed
     */
    public function getAllowedCreditCards()
    {
        return $this->getConfigValue(Configuration::PAYFLOW_GATEWAY_ALLOWED_CC_TYPES_KEY);
    }

    /**
     * @param array $allowedCreditCard
     * @return array
     */
    protected function mapAllowedCreditCards(array $allowedCreditCard)
    {
        return array_map(function ($value) {
            return $this->cardNamesMap[$value];
        }, $allowedCreditCard);
    }
}
