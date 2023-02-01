<?php

namespace Oro\Bundle\FedexShippingBundle\ShippingMethod;

// @codingStandardsIgnoreStart
use Oro\Bundle\CurrencyBundle\Entity\Price;
use Oro\Bundle\FedexShippingBundle\Client\RateService\FedexRateServiceBySettingsClientInterface;
use Oro\Bundle\FedexShippingBundle\Client\RateService\Request\Factory\FedexRequestByRateServiceSettingsFactoryInterface;
use Oro\Bundle\FedexShippingBundle\Client\RateService\Request\Settings\Factory\FedexRateServiceRequestSettingsFactoryInterface;
use Oro\Bundle\FedexShippingBundle\Entity\FedexIntegrationSettings;
use Oro\Bundle\FedexShippingBundle\Entity\FedexShippingService;
use Oro\Bundle\FedexShippingBundle\Entity\ShippingServiceRule;
use Oro\Bundle\FedexShippingBundle\Form\Type\FedexShippingMethodOptionsType;
use Oro\Bundle\ShippingBundle\Context\ShippingContextInterface;
use Oro\Bundle\ShippingBundle\Method\PricesAwareShippingMethodInterface;
use Oro\Bundle\ShippingBundle\Method\ShippingMethodIconAwareInterface;
use Oro\Bundle\ShippingBundle\Method\ShippingMethodInterface;
use Oro\Bundle\ShippingBundle\Method\ShippingMethodTypeInterface;
use Oro\Bundle\ShippingBundle\Method\ShippingTrackingAwareInterface;

// @codingStandardsIgnoreEnd

/**
 * Represents FedEx shipping method.
 */
class FedexShippingMethod implements
    ShippingMethodInterface,
    ShippingMethodIconAwareInterface,
    PricesAwareShippingMethodInterface,
    ShippingTrackingAwareInterface
{
    public const OPTION_SURCHARGE = 'surcharge';

    private const TRACKING_URL = 'https://www.fedex.com/apps/fedextrack/?action=track&trackingnumber=';

    private FedexRateServiceRequestSettingsFactoryInterface $rateServiceRequestSettingsFactory;
    private FedexRequestByRateServiceSettingsFactoryInterface $rateServiceRequestFactory;
    private FedexRateServiceBySettingsClientInterface $rateServiceClient;
    private string $identifier;
    private string $label;
    private ?string $iconPath;
    private bool $enabled;
    private FedexIntegrationSettings $settings;
    /** @var ShippingMethodTypeInterface[] */
    private array $types;

    /**
     * @param FedexRateServiceRequestSettingsFactoryInterface   $rateServiceRequestSettingsFactory
     * @param FedexRequestByRateServiceSettingsFactoryInterface $rateServiceRequestFactory
     * @param FedexRateServiceBySettingsClientInterface         $rateServiceClient
     * @param string                                            $identifier
     * @param string                                            $label
     * @param string|null                                       $iconPath
     * @param bool                                              $enabled
     * @param FedexIntegrationSettings                          $settings
     * @param ShippingMethodTypeInterface[]                     $types
     */
    public function __construct(
        FedexRateServiceRequestSettingsFactoryInterface $rateServiceRequestSettingsFactory,
        FedexRequestByRateServiceSettingsFactoryInterface $rateServiceRequestFactory,
        FedexRateServiceBySettingsClientInterface $rateServiceClient,
        string $identifier,
        string $label,
        ?string $iconPath,
        bool $enabled,
        FedexIntegrationSettings $settings,
        array $types
    ) {
        $this->rateServiceRequestSettingsFactory = $rateServiceRequestSettingsFactory;
        $this->rateServiceRequestFactory = $rateServiceRequestFactory;
        $this->rateServiceClient = $rateServiceClient;
        $this->identifier = $identifier;
        $this->label = $label;
        $this->iconPath = $iconPath;
        $this->enabled = $enabled;
        $this->settings = $settings;
        $this->types = $types;
    }

    /**
     * {@inheritDoc}
     */
    public function isGrouped(): bool
    {
        return true;
    }

    /**
     * {@inheritDoc}
     */
    public function isEnabled(): bool
    {
        return $this->enabled;
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
        return $this->label;
    }

    /**
     * {@inheritDoc}
     */
    public function getIcon(): ?string
    {
        return $this->iconPath;
    }

    /**
     * {@inheritDoc}
     */
    public function getTypes(): array
    {
        return $this->types;
    }

    /**
     * {@inheritDoc}
     */
    public function getType(string $identifier): ?ShippingMethodTypeInterface
    {
        foreach ($this->getTypes() as $methodType) {
            if ($methodType->getIdentifier() === $identifier) {
                return $methodType;
            }
        }

        return null;
    }

    public function getShippingService(string $code): ?FedexShippingService
    {
        $services = $this->settings->getShippingServices();
        foreach ($services as $service) {
            if ($code === $service->getCode()) {
                return $service;
            }
        }

        return null;
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
    public function getSortOrder(): int
    {
        return 20;
    }

    /**
     * {@inheritDoc}
     */
    public function calculatePrices(
        ShippingContextInterface $context,
        array $methodOptions,
        array $optionsByTypes
    ): array {
        $shippingServices = $this->getShippingServicesFromOptions($optionsByTypes);
        $prices = $this->getPricesForShippingServices($shippingServices, $context);

        $methodSurcharge = $this->getSurchargeFromOptions($methodOptions);
        $result = [];
        foreach ($optionsByTypes as $typeId => $option) {
            if (!\array_key_exists($typeId, $prices)) {
                continue;
            }

            $price = $prices[$typeId];

            $result[$typeId] = Price::create(
                (float)$price->getValue() + $methodSurcharge + $this->getSurchargeFromOptions($option),
                $price->getCurrency()
            );
        }

        return $result;
    }

    /**
     * {@inheritDoc}
     */
    public function getTrackingLink(string $number): ?string
    {
        foreach ($this->getTrackingRegexList() as $regex) {
            if (preg_match($regex, $number)) {
                return self::TRACKING_URL.$number;
            }
        }

        return null;
    }

    private function getTrackingRegexList(): array
    {
        return [
            '/(\b96\d{20}\b)|(\b\d{15}\b)|(\b\d{12}\b)/',
            '/\b((98\d\d\d\d\d?\d\d\d\d|98\d\d) ?\d\d\d\d ?\d\d\d\d( ?\d\d\d)?)\b/',
            '/^[0-9]{15}$/',
        ];
    }

    private function getSurchargeFromOptions(array $option): float
    {
        return (float)$option[self::OPTION_SURCHARGE];
    }

    /**
     * @param array $optionsByTypes
     *
     * @return FedexShippingService[]
     */
    private function getShippingServicesFromOptions(array $optionsByTypes): array
    {
        $services = [];
        foreach (array_keys($optionsByTypes) as $typeId) {
            $shippingService = $this->getShippingService($typeId);
            if (!$shippingService) {
                continue;
            }

            $services[] = $shippingService;
        }

        return $services;
    }

    /**
     * @param FedexShippingService[]   $shippingServices
     * @param ShippingContextInterface $context
     *
     * @return Price[]
     */
    private function getPricesForShippingServices(array $shippingServices, ShippingContextInterface $context): array
    {
        $prices = [];
        $rulePrices = [];
        foreach ($shippingServices as $service) {
            $ruleId = $service->getRule()->getId();

            if (!\array_key_exists($ruleId, $rulePrices)) {
                $rulePrices[$ruleId] = $this->getPricesForRule($context, $service->getRule());
            }

            if (\array_key_exists($service->getCode(), $rulePrices[$ruleId])) {
                $prices[$service->getCode()] = $rulePrices[$ruleId][$service->getCode()];
            }
        }

        return $prices;
    }

    private function getPricesForRule(ShippingContextInterface $context, ShippingServiceRule $rule): array
    {
        $request = $this->rateServiceRequestFactory->create(
            $this->rateServiceRequestSettingsFactory->create($this->settings, $context, $rule)
        );
        if (!$request) {
            return [];
        }

        return $this->rateServiceClient->send($request, $this->settings)->getPrices();
    }
}
