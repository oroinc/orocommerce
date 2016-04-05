<?php

namespace OroB2B\Bundle\PaymentBundle\Method\View;

use Symfony\Component\Form\FormFactoryInterface;

use Oro\Bundle\ConfigBundle\Config\ConfigManager;
use OroB2B\Bundle\PaymentBundle\DependencyInjection\Configuration;
use OroB2B\Bundle\PaymentBundle\DependencyInjection\OroB2BPaymentExtension;
use OroB2B\Bundle\PaymentBundle\Form\Type\CreditCardType;
use OroB2B\Bundle\PaymentBundle\Method\PayflowGateway;

class PayflowGatewayView implements PaymentMethodViewInterface
{
    /** @var FormFactoryInterface */
    protected $formFactory;

    /** @var ConfigManager */
    protected $configManager;

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

        return ['formView' => $formView];
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
     * @param string $key
     * @return string
     */
    protected function getConfigValue($key)
    {
        $key = OroB2BPaymentExtension::ALIAS . ConfigManager::SECTION_MODEL_SEPARATOR . $key;

        return $this->configManager->get($key);
    }
}
