<?php

namespace Oro\Bundle\FedexShippingBundle\ShippingMethod;

use Oro\Bundle\FedexShippingBundle\Entity\FedexIntegrationSettings;
use Oro\Bundle\FedexShippingBundle\Form\Type\FedexShippingMethodOptionsType;
use Oro\Bundle\ShippingBundle\Context\ShippingContextInterface;
use Oro\Bundle\ShippingBundle\Method\ShippingMethodTypeInterface;

class FedexShippingMethodType implements ShippingMethodTypeInterface
{
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
     * @param string                   $identifier
     * @param string                   $label
     * @param FedexIntegrationSettings $settings
     */
    public function __construct(
        string $identifier,
        string $label,
        FedexIntegrationSettings $settings
    ) {
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
    }
}
