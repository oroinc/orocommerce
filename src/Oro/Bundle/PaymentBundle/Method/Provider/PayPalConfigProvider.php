<?php

namespace Oro\Bundle\PaymentBundle\Method\Provider;

use Doctrine\Common\Persistence\ManagerRegistry;
use Oro\Bundle\IntegrationBundle\Entity\Channel;
use Oro\Bundle\PaymentBundle\Method\Config\PaymentConfigProviderInterface;
use Oro\Bundle\PayPalBundle\Method\Config\PayPalCreditCardConfig;
use Oro\Bundle\PayPalBundle\Method\Config\PayPalExpressCheckoutConfig;
use Oro\Bundle\PayPalBundle\Method\Config\PayPalExpressCheckoutConfigInterface;
use Oro\Bundle\SecurityBundle\Encoder\SymmetricCrypterInterface;

class PayPalConfigProvider implements PaymentConfigProviderInterface
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
    public function getPaymentConfigs()
    {
        if ($this->getConfigs() !== null) {
            return $this->getConfigs();
        }

        $channels = $this->doctrine->getManagerForClass('OroIntegrationBundle:Channel')
            ->getRepository('OroIntegrationBundle:Channel')
            ->findBy([
                'type' => [self::CHANEL_TYPE_PAYPAL_PAYFLOW_GATEWAY, self::CHANEL_TYPE_PAYPAL_PAYMENTS_PRO],
                'enabled' => true
            ])
        ;
        if (count($channels) > 0) {
            /** @var Channel $channel */
            foreach ($channels as $channel) {
                switch ($this->getType()) {
                    case PayPalCreditCardConfig::TYPE:
                        $this->configs[] = new PayPalCreditCardConfig($channel, $this->encoder);
                        break;
                    case PayPalExpressCheckoutConfig::TYPE:
                        $this->configs[] = new PayPalExpressCheckoutConfig($channel, $this->encoder);
                        break;
                }
            }
        }

         return $this->getConfigs();
    }

    /**
     * {@inheritdoc}
     */
    public function getPaymentConfig($identifier)
    {
        $paymentConfigs = $this->getPaymentConfigs();

        if ($paymentConfigs === null) {
            return null;
        }

        $paymentConfig = array_filter(
            $paymentConfigs,
            function ($paymentConfig) use ($identifier) {
                /** @var PayPalExpressCheckoutConfigInterface $paymentConfig */
                return $paymentConfig->getPaymentMethodIdentifier() === $identifier;
            }
        );

        return count($paymentConfig) > 0 ? $paymentConfig : null;
    }

    /**
     * {@inheritdoc}
     */
    public function hasPaymentConfig($identifier)
    {
        return count($this->getPaymentConfig($identifier)) > 0;
    }
}
