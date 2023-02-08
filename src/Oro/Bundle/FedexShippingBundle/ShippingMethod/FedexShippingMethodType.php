<?php

namespace Oro\Bundle\FedexShippingBundle\ShippingMethod;

// @codingStandardsIgnoreStart
use Oro\Bundle\CurrencyBundle\Entity\Price;
use Oro\Bundle\FedexShippingBundle\Client\RateService\FedexRateServiceBySettingsClientInterface;
use Oro\Bundle\FedexShippingBundle\Client\RateService\Request\Factory\FedexRequestByRateServiceSettingsFactoryInterface;
use Oro\Bundle\FedexShippingBundle\Client\RateService\Request\Settings\Factory\FedexRateServiceRequestSettingsFactoryInterface;
use Oro\Bundle\FedexShippingBundle\Entity\FedexIntegrationSettings;
use Oro\Bundle\FedexShippingBundle\Entity\FedexShippingService;
use Oro\Bundle\FedexShippingBundle\Form\Type\FedexShippingMethodOptionsType;
use Oro\Bundle\ShippingBundle\Context\ShippingContextInterface;
use Oro\Bundle\ShippingBundle\Method\ShippingMethodTypeInterface;

// @codingStandardsIgnoreEnd

/**
 * Represents FedEx shipping method type.
 */
class FedexShippingMethodType implements ShippingMethodTypeInterface
{
    private FedexRateServiceRequestSettingsFactoryInterface $rateServiceRequestSettingsFactory;
    private FedexRequestByRateServiceSettingsFactoryInterface $rateServiceRequestFactory;
    private FedexRateServiceBySettingsClientInterface $rateServiceClient;
    private string $identifier;
    private FedexShippingService $shippingService;
    private FedexIntegrationSettings $settings;

    public function __construct(
        FedexRateServiceRequestSettingsFactoryInterface $rateServiceRequestSettingsFactory,
        FedexRequestByRateServiceSettingsFactoryInterface $rateServiceRequestFactory,
        FedexRateServiceBySettingsClientInterface $rateServiceClient,
        string $identifier,
        FedexShippingService $shippingService,
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
    public function getIdentifier(): string
    {
        return $this->identifier;
    }

    /**
     * {@inheritDoc}
     */
    public function getLabel(): string
    {
        return (string)$this->shippingService->getDescription();
    }

    /**
     * {@inheritDoc}
     */
    public function getSortOrder(): int
    {
        return 0;
    }

    /**
     * {@inheritDoc}
     */
    public function getOptionsConfigurationFormType(): ?string
    {
        return FedexShippingMethodOptionsType::class;
    }

    /**
     * {@inheritDoc}
     */
    public function calculatePrice(
        ShippingContextInterface $context,
        array $methodOptions,
        array $typeOptions
    ): ?Price {
        $rule = $this->shippingService->getRule();
        $request = $this->rateServiceRequestFactory->create(
            $this->rateServiceRequestSettingsFactory->create($this->settings, $context, $rule)
        );
        if (!$request) {
            return null;
        }

        $prices = $this->rateServiceClient->send($request, $this->settings)->getPrices();
        if (!\array_key_exists($this->shippingService->getCode(), $prices)) {
            return null;
        }

        $price = $prices[$this->shippingService->getCode()];
        $methodSurcharge = $this->getSurchargeFromOptions($methodOptions);
        $typeSurcharge = $this->getSurchargeFromOptions($typeOptions);

        return Price::create(
            (float)$price->getValue() + $methodSurcharge + $typeSurcharge,
            $price->getCurrency()
        );
    }

    private function getSurchargeFromOptions(array $option): float
    {
        return (float)$option[FedexShippingMethod::OPTION_SURCHARGE];
    }
}
