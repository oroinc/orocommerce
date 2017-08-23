<?php

namespace Oro\Bundle\FedexShippingBundle\ShippingMethod;

use Oro\Bundle\CurrencyBundle\Entity\Price;
use Oro\Bundle\FedexShippingBundle\Client\RateService\FedexRateServiceBySettingsClientInterface;
use Oro\Bundle\FedexShippingBundle\Client\Request\Factory\FedexRequestByContextAndSettingsFactoryInterface;
use Oro\Bundle\FedexShippingBundle\Entity\FedexIntegrationSettings;
use Oro\Bundle\FedexShippingBundle\Form\Type\FedexShippingMethodOptionsType;
use Oro\Bundle\ShippingBundle\Context\ShippingContextInterface;
use Oro\Bundle\ShippingBundle\Method\ShippingMethodTypeInterface;

class FedexShippingMethodType implements ShippingMethodTypeInterface
{
    /**
     * @var FedexRequestByContextAndSettingsFactoryInterface
     */
    private $rateServiceRequestFactory;

    /**
     * @var FedexRateServiceBySettingsClientInterface
     */
    private $rateServiceClient;

    /**
     * @var string
     */
    private $identifier;

    /**
     * @var string
     */
    private $label;

    /**
     * @var FedexIntegrationSettings
     */
    private $settings;

    /**
     * @param FedexRequestByContextAndSettingsFactoryInterface $rateServiceRequestFactory
     * @param FedexRateServiceBySettingsClientInterface        $rateServiceClient
     * @param string                                           $identifier
     * @param string                                           $label
     * @param FedexIntegrationSettings                         $settings
     */
    public function __construct(
        FedexRequestByContextAndSettingsFactoryInterface $rateServiceRequestFactory,
        FedexRateServiceBySettingsClientInterface $rateServiceClient,
        string $identifier,
        string $label,
        FedexIntegrationSettings $settings
    ) {
        $this->rateServiceRequestFactory = $rateServiceRequestFactory;
        $this->rateServiceClient = $rateServiceClient;
        $this->identifier = $identifier;
        $this->label = $label;
        $this->settings = $settings;
    }

    /**
     * {@inheritDoc}
     */
    public function getIdentifier()
    {
        return $this->identifier;
    }

    /**
     * {@inheritDoc}
     */
    public function getLabel()
    {
        return $this->label;
    }

    /**
     * {@inheritDoc}
     */
    public function getSortOrder()
    {
        return 0;
    }

    /**
     * {@inheritDoc}
     */
    public function getOptionsConfigurationFormType()
    {
        return FedexShippingMethodOptionsType::class;
    }

    /**
     * {@inheritDoc}
     */
    public function calculatePrice(ShippingContextInterface $context, array $methodOptions, array $typeOptions)
    {
        $request = $this->rateServiceRequestFactory->create($this->settings, $context);
        if (!$request) {
            return null;
        }

        $prices = $this->rateServiceClient->send($request, $this->settings)->getPrices();
        if (!array_key_exists($this->getIdentifier(), $prices)) {
            return null;
        }

        $price = $prices[$this->getIdentifier()];
        $methodSurcharge = $this->getSurchargeFromOptions($methodOptions);
        $typeSurcharge = $this->getSurchargeFromOptions($typeOptions);

        return Price::create(
            (float) $price->getValue() + $methodSurcharge + $typeSurcharge,
            $price->getCurrency()
        );
    }

    /**
     * @param array $option
     *
     * @return float
     */
    private function getSurchargeFromOptions(array $option): float
    {
        return (float) $option[FedexShippingMethod::OPTION_SURCHARGE];
    }
}
