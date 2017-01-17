<?php

namespace Oro\Bundle\PayPalBundle\Method\Config\Builder;

use Oro\Bundle\IntegrationBundle\Entity\Channel;
use Oro\Bundle\LocaleBundle\Helper\LocalizationHelper;
use Oro\Bundle\PayPalBundle\Entity\PayPalSettings;
use Oro\Bundle\PayPalBundle\Method\Config\PayPalCreditCardConfig;
use Oro\Bundle\PayPalBundle\Method\Config\PayPalCreditCardConfigInterface;
use Oro\Bundle\PayPalBundle\Method\Config\PayPalExpressCheckoutConfig;
use Oro\Bundle\SecurityBundle\Encoder\SymmetricCrypterInterface;
use Symfony\Component\HttpFoundation\ParameterBag;

class PayPalExpressCheckoutConfigBuilder implements PayPalConfigBuilderInterface
{
    /**
     * @var Channel
     */
    protected $channel;

    /**
     * @var SymmetricCrypterInterface
     */
    protected $encoder;

    /**
     * @var LocalizationHelper
     */
    protected $localizationHelper;

    /**
     * @param SymmetricCrypterInterface $encoder
     * @param LocalizationHelper $localizationHelper
     */
    public function __construct(
        SymmetricCrypterInterface $encoder,
        LocalizationHelper $localizationHelper
    ) {
        $this->encoder = $encoder;
        $this->localizationHelper = $localizationHelper;
    }
    
    /**
     * @return PayPalCreditCardConfigInterface|null
     */
    public function getResult()
    {
        if (null !== $this->channel) {
            /** @var ParameterBag $parameterBag */
            $parameterBag = $this->channel->getTransport()->getSettingsBag();
            $parameterBag->set(
                PayPalSettings::PASSWORD_KEY,
                $this->encoder->decryptData($parameterBag->get(PayPalSettings::PASSWORD_KEY))
            );
            $parameterBag->set(
                PayPalSettings::EXPRESS_CHECKOUT_LABELS_KEY,
                $this->localizationHelper->
                getLocalizedValue($parameterBag->get(PayPalSettings::EXPRESS_CHECKOUT_LABELS_KEY))
            );
            $parameterBag->set(
                PayPalSettings::EXPRESS_CHECKOUT_SHORT_LABELS_KEY,
                $this->localizationHelper->
                getLocalizedValue($parameterBag->get(PayPalSettings::EXPRESS_CHECKOUT_SHORT_LABELS_KEY))
            );
            $parameterBag->set(
                PayPalExpressCheckoutConfig::PAYMENT_METHOD_IDENTIFIER_KEY,
                $this->channel->getType() . '_' . PayPalExpressCheckoutConfig::TYPE . '_' . $this->channel->getId()
            );
            $parameterBag->set(
                PayPalExpressCheckoutConfig::ADMIN_LABEL_KEY,
                $parameterBag->get(PayPalSettings::EXPRESS_CHECKOUT_NAME_KEY)
            );
            
            return new PayPalExpressCheckoutConfig($parameterBag);
        }
        
        return null;
    }

    /**
     * {inheritdoc}
     */
    public function setChannel(Channel $channel)
    {
        $this->channel = $channel;
        return $this;
    }

    /**
     * @param string $key
     * @return mixed
     */
    protected function getConfigValue($key)
    {
        if (null !== $this->channel) {
            return $this->channel->getTransport()->getSettingsBag()->get($key);
        }

        return null;
    }
}
