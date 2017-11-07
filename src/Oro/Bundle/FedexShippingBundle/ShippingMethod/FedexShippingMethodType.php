<?php

namespace Oro\Bundle\FedexShippingBundle\ShippingMethod;

// @codingStandardsIgnoreStart
use Oro\Bundle\CurrencyBundle\Entity\Price;
use Oro\Bundle\FedexShippingBundle\Client\RateService\FedexRateServiceBySettingsClientInterface;
use Oro\Bundle\FedexShippingBundle\Client\RateService\Request\Factory\FedexRequestByRateServiceSettingsFactoryInterface;
use Oro\Bundle\FedexShippingBundle\Client\RateService\Request\Settings\Factory\FedexRateServiceRequestSettingsFactoryInterface;
use Oro\Bundle\FedexShippingBundle\Entity\FedexIntegrationSettings;
use Oro\Bundle\FedexShippingBundle\Entity\ShippingService;
use Oro\Bundle\FedexShippingBundle\Form\Type\FedexShippingMethodOptionsType;
use Oro\Bundle\ShippingBundle\Context\ShippingContextInterface;
use Oro\Bundle\ShippingBundle\Method\ShippingMethodTypeInterface;
// @codingStandardsIgnoreEnd

class FedexShippingMethodType implements ShippingMethodTypeInterface
{
    /**
     * @var FedexRateServiceRequestSettingsFactoryInterface
     */
    private $rateServiceRequestSettingsFactory;

    /**
     * @var FedexRequestByRateServiceSettingsFactoryInterface
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
     * @var ShippingService
     */
    private $shippingService;

    /**
     * @var FedexIntegrationSettings
     */
    private $settings;

    /**
     * @param FedexRateServiceRequestSettingsFactoryInterface   $rateServiceRequestSettingsFactory
     * @param FedexRequestByRateServiceSettingsFactoryInterface $rateServiceRequestFactory
     * @param FedexRateServiceBySettingsClientInterface         $rateServiceClient
     * @param string                                            $identifier
     * @param ShippingService                                   $shippingService,
     * @param FedexIntegrationSettings                          $settings
     */
    public function __construct(
        FedexRateServiceRequestSettingsFactoryInterface $rateServiceRequestSettingsFactory,
        FedexRequestByRateServiceSettingsFactoryInterface $rateServiceRequestFactory,
        FedexRateServiceBySettingsClientInterface $rateServiceClient,
        string $identifier,
        ShippingService $shippingService,
        FedexIntegrationSettings $settings
    ) {
        $this->rateServiceRequestSettingsFactory = $rateServiceRequestSettingsFactory;
        $this->rateServiceRequestFactory = $rateServiceRequestFactory;
        $this->rateServiceClient = $rateServiceClient;
        $this->identifier = $identifier;
        $this->shippingService = $shippingService;
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
        return $this->shippingService->getDescription();
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
        $request = $this->rateServiceRequestFactory->create(
            $this->rateServiceRequestSettingsFactory->create($this->settings, $context, $this->shippingService)
        );
        if (!$request) {
            return null;
        }

        $price = $this->rateServiceClient->send($request, $this->settings)->getPrice();
        if (!$price) {
            return null;
        }

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
