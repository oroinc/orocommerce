<?php

namespace Oro\Bundle\FedexShippingBundle\ShippingMethod;

use Oro\Bundle\FedexShippingBundle\Client\RateService\FedexRateServiceBySettingsClientInterface;
use Oro\Bundle\FedexShippingBundle\Client\Request\Factory\FedexRequestFromShippingContextFactoryInterface;
use Oro\Bundle\FedexShippingBundle\Entity\FedexIntegrationSettings;
use Oro\Bundle\FedexShippingBundle\Form\Type\FedexShippingMethodOptionsType;
use Oro\Bundle\ShippingBundle\Context\ShippingContextInterface;
use Oro\Bundle\ShippingBundle\Method\ShippingMethodTypeInterface;

class FedexShippingMethodType implements ShippingMethodTypeInterface
{
    /**
     * @var FedexRequestFromShippingContextFactoryInterface
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
     * @param FedexRequestFromShippingContextFactoryInterface $rateServiceRequestFactory
     * @param FedexRateServiceBySettingsClientInterface       $rateServiceClient
     * @param string                                          $identifier
     * @param string                                          $label
     * @param FedexIntegrationSettings                        $settings
     */
    public function __construct(
        FedexRequestFromShippingContextFactoryInterface $rateServiceRequestFactory,
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
        $response = $this->rateServiceClient->send(
            $this->rateServiceRequestFactory->create($this->settings, $context),
            $this->settings
        );

        $prices = $response->getPrices();
        if (!array_key_exists($this->getIdentifier(), $prices)) {
            return null;
        }

        $price = $prices[$this->getIdentifier()];

        $optionsDefaults = [
            FedexShippingMethod::OPTION_SURCHARGE => 0,
        ];
        $methodOptions = array_merge($optionsDefaults, $methodOptions);
        $typeOptions = array_merge($optionsDefaults, $typeOptions);

        return $price->setValue(array_sum([
            (float)$price->getValue(),
            (float)$methodOptions[FedexShippingMethod::OPTION_SURCHARGE],
            (float)$typeOptions[FedexShippingMethod::OPTION_SURCHARGE]
        ]));
    }
}
