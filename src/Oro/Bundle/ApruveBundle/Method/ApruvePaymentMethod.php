<?php

namespace Oro\Bundle\ApruveBundle\Method;

use Oro\Bundle\ApruveBundle\Apruve\Provider\SupportedCurrenciesProviderInterface;
use Oro\Bundle\ApruveBundle\Method\Config\ApruveConfigInterface;
use Oro\Bundle\ApruveBundle\Method\PaymentAction\Executor\PaymentActionExecutor;
use Oro\Bundle\PaymentBundle\Context\PaymentContextInterface;
use Oro\Bundle\PaymentBundle\Entity\PaymentTransaction;
use Oro\Bundle\PaymentBundle\Method\PaymentMethodInterface;

class ApruvePaymentMethod implements PaymentMethodInterface
{
    const TYPE = 'apruve';

    const COMPLETE = 'complete';
    const CANCEL = 'cancel';

    const PARAM_ORDER_ID = 'apruveOrderId';

    /**
     * @var ApruveConfigInterface
     */
    protected $config;

    /**
     * @var PaymentActionExecutor
     */
    protected $paymentActionExecutor;

    /**
     * @var SupportedCurrenciesProviderInterface
     */
    protected $supportedCurrenciesProvider;

    /**
     * @param ApruveConfigInterface $config
     * @param SupportedCurrenciesProviderInterface $supportedCurrenciesProvider
     * @param PaymentActionExecutor $paymentActionExecutor
     */
    public function __construct(
        ApruveConfigInterface $config,
        SupportedCurrenciesProviderInterface $supportedCurrenciesProvider,
        PaymentActionExecutor $paymentActionExecutor
    ) {
        $this->config = $config;
        $this->paymentActionExecutor = $paymentActionExecutor;
        $this->supportedCurrenciesProvider = $supportedCurrenciesProvider;
    }

    /**
     * {@inheritdoc}
     * @throws \InvalidArgumentException
     */
    public function execute($action, PaymentTransaction $paymentTransaction)
    {
        return $this->paymentActionExecutor->execute($action, $this->config, $paymentTransaction);
    }

    /**
     * {@inheritdoc}
     */
    public function getIdentifier()
    {
        return $this->config->getPaymentMethodIdentifier();
    }

    /**
     * {@inheritdoc}
     */
    public function isApplicable(PaymentContextInterface $context)
    {
        return $this->supportedCurrenciesProvider->isSupported($context->getCurrency());
    }

    /**
     * {@inheritdoc}
     */
    public function supports($actionName)
    {
        return $this->paymentActionExecutor->supports($actionName);
    }
}
