<?php

namespace OroB2B\Bundle\PaymentBundle\Method;

use Oro\Bundle\ConfigBundle\Config\ConfigManager;

use OroB2B\Bundle\PaymentBundle\DependencyInjection\Configuration;
use OroB2B\Bundle\PaymentBundle\Entity\PaymentTransaction;
use OroB2B\Bundle\PaymentBundle\Provider\PaymentTermProvider;
use OroB2B\Bundle\PaymentBundle\Traits\ConfigTrait;

class PaymentTerm implements PaymentMethodInterface
{
    use ConfigTrait;

    const TYPE = 'payment_term';

    /** @var PaymentTermProvider */
    protected $paymentTermProvider;

    /**
     * @param PaymentTermProvider $paymentTermProvider
     * @param ConfigManager $configManager
     */
    public function __construct(PaymentTermProvider $paymentTermProvider, ConfigManager $configManager)
    {
        $this->paymentTermProvider = $paymentTermProvider;
        $this->configManager = $configManager;
    }

    /** {@inheritdoc} */
    public function execute(PaymentTransaction $paymentTransaction)
    {
        $paymentTransaction->setSuccessful(true);

        return [];
    }

    /** {@inheritdoc} */
    public function isEnabled()
    {
        return (bool)$this->paymentTermProvider->getCurrentPaymentTerm() &&
            $this->getConfigValue(Configuration::PAYMENT_TERM_ENABLED_KEY);
    }

    /** {@inheritdoc} */
    public function getType()
    {
        return self::TYPE;
    }
}
