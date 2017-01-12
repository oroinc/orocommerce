<?php

namespace Oro\Bundle\PaymentBundle\Method\Provider;

use Doctrine\Common\Persistence\ManagerRegistry;
use Oro\Bundle\PaymentBundle\Method\Config\PaymentConfigProviderInterface;
use Oro\Bundle\PayPalBundle\Method\Config\PayPalExpressCheckoutConfigInterface;
use Oro\Bundle\SecurityBundle\Encoder\SymmetricCrypterInterface;

abstract class PayPalConfigProvider implements PaymentConfigProviderInterface
{
    const CHANEL_TYPE_PAYPAL_PAYFLOW_GATEWAY = 'paypal_payflow_gateway';
    const CHANEL_TYPE_PAYPAL_PAYMENTS_PRO = 'paypal_payments_pro';

    /**
     * @var string
     */
    protected $type;

    /**
     * @var array
     */
    protected $configs = [];

    /**
     * @var ManagerRegistry
     */
    protected $doctrine;

    /**
     * @var SymmetricCrypterInterface
     */
    protected $encoder;

    /**
     * @param ManagerRegistry $doctrine
     * @param SymmetricCrypterInterface $encoder
     * @param string $type
     */
    public function __construct(ManagerRegistry $doctrine, SymmetricCrypterInterface $encoder, $type)
    {
        $this->doctrine = $doctrine;
        $this->encoder = $encoder;
        $this->type = $type;
    }

    /**
     * @return string
     */
    public function getType()
    {
        return $this->type;
    }

    /**
     * @return array|null
     */
    public function getConfigs()
    {
        return count($this->configs) > 0 ? $this->configs : null;
    }

    /**
     * {@inheritdoc}
     */
    abstract public function getPaymentConfigs();

    /**
     * {@inheritdoc}
     */
    public function getPaymentConfig($identifier)
    {
        $paymentConfigs = $this->getPaymentConfigs();

        if (($paymentConfigs === null) || !array_key_exists($identifier, $paymentConfigs)) {
            return null;
        }

        return $paymentConfigs[$identifier];
    }

    /**
     * {@inheritdoc}
     */
    public function hasPaymentConfig($identifier)
    {
        return $this->getPaymentConfig($identifier) !== null;
    }
}
