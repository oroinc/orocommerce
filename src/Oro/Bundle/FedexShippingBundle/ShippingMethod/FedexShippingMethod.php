<?php

namespace Oro\Bundle\FedexShippingBundle\ShippingMethod;

use Oro\Bundle\FedexShippingBundle\Entity\FedexIntegrationSettings;
use Oro\Bundle\FedexShippingBundle\Form\Type\FedexShippingMethodOptionsType;
use Oro\Bundle\ShippingBundle\Context\ShippingContextInterface;
use Oro\Bundle\ShippingBundle\Method\PricesAwareShippingMethodInterface;
use Oro\Bundle\ShippingBundle\Method\ShippingMethodIconAwareInterface;
use Oro\Bundle\ShippingBundle\Method\ShippingMethodInterface;
use Oro\Bundle\ShippingBundle\Method\ShippingMethodTypeInterface;
use Oro\Bundle\ShippingBundle\Method\ShippingTrackingAwareInterface;

class FedexShippingMethod implements
    ShippingMethodInterface,
    ShippingMethodIconAwareInterface,
    PricesAwareShippingMethodInterface,
    ShippingTrackingAwareInterface
{
    const OPTION_SURCHARGE = 'surcharge';

    const TRACKING_URL = 'https://www.fedex.com/apps/fedextrack/?action=track&trackingnumber=';

    /**
     * @var string
     */
    private $identifier;

    /**
     * @var string
     */
    private $label;

    /**
     * @var string|null
     */
    private $iconPath;

    /**
     * @var ShippingMethodTypeInterface[]
     */
    private $types;

    /**
     * @var FedexIntegrationSettings
     */
    private $settings;

    /**
     * @var bool
     */
    private $enabled;

    /**
     * @param string                        $identifier
     * @param string                        $label
     * @param string|null                   $iconPath
     * @param bool                          $enabled
     * @param FedexIntegrationSettings      $settings,
     * @param ShippingMethodTypeInterface[] $types
     */
    public function __construct(
        string $identifier,
        string $label,
        $iconPath,
        bool $enabled,
        FedexIntegrationSettings $settings,
        array $types
    ) {
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
    public function isGrouped()
    {
        return true;
    }

    /**
     * {@inheritDoc}
     */
    public function isEnabled()
    {
        return $this->enabled;
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
    public function getIcon()
    {
        return $this->iconPath;
    }

    /**
     * {@inheritDoc}
     */
    public function getTypes()
    {
        return $this->types;
    }

    /**
     * {@inheritDoc}
     */
    public function getType($identifier)
    {
        foreach ($this->getTypes() as $methodType) {
            if ($methodType->getIdentifier() === (string) $identifier) {
                return $methodType;
            }
        }

        return null;
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
    public function getSortOrder()
    {
        return 20;
    }

    /**
     * {@inheritDoc}
     */
    public function calculatePrices(ShippingContextInterface $context, array $methodOptions, array $optionsByTypes)
    {
    }

    /**
     * {@inheritDoc}
     */
    public function getTrackingLink($number)
    {
        foreach ($this->getTrackingRegexList() as $regex) {
            if (preg_match($regex, $number)) {
                return self::TRACKING_URL . $number;
            }
        }

        return null;
    }

    /**
     * @return array
     */
    private function getTrackingRegexList(): array
    {
        return [
            '/(\b96\d{20}\b)|(\b\d{15}\b)|(\b\d{12}\b)/',
            '/\b((98\d\d\d\d\d?\d\d\d\d|98\d\d) ?\d\d\d\d ?\d\d\d\d( ?\d\d\d)?)\b/',
            '/^[0-9]{15}$/',
        ];
    }
}
