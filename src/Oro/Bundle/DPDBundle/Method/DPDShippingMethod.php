<?php

namespace Oro\Bundle\DPDBundle\Method;

use Oro\Bundle\DPDBundle\Form\Type\DPDShippingMethodOptionsType;
use Oro\Bundle\ShippingBundle\Context\ShippingContextInterface;
use Oro\Bundle\ShippingBundle\Method\PricesAwareShippingMethodInterface;
use Oro\Bundle\ShippingBundle\Method\ShippingMethodInterface;
use Oro\Bundle\ShippingBundle\Method\ShippingMethodTypeInterface;
use Oro\Bundle\ShippingBundle\Method\ShippingTrackingAwareInterface;
use Oro\Bundle\DPDBundle\Entity\DPDTransport as DPDSettings;
use Oro\Bundle\DPDBundle\Provider\DPDTransport as DPDTransportProvider;

class DPDShippingMethod implements
    ShippingMethodInterface,
    ShippingTrackingAwareInterface,
    PricesAwareShippingMethodInterface
{
    const IDENTIFIER = 'dpd';
    const TRACKING_URL = 'https://tracking.dpd.de/parcelstatus?query=';

    const HANDLING_FEE_OPTION = 'handling_fee';

    /** @var string */
    protected $identifier;

    /** @var string */
    protected $label;

    /** @var ShippingMethodTypeInterface[] */
    protected $types;

    /** @var DPDSettings */
    protected $transport;

    /** @var DPDTransportProvider */
    protected $transportProvider;

    /**
     * Construct.
     *
     * @param $identifier
     * @param $label
     * @param array                $types
     * @param DPDSettings          $transport
     * @param DPDTransportProvider $transportProvider
     */
    public function __construct(
        $identifier,
        $label,
        array $types,
        DPDSettings $transport,
        DPDTransportProvider $transportProvider
    ) {
        $this->identifier = $identifier;
        $this->label = $label;
        $this->types = $types;
        $this->transport = $transport;
        $this->transportProvider = $transportProvider;
    }

    /**
     * {@inheritdoc}
     */
    public function getIdentifier()
    {
        return $this->identifier;
    }

    /**
     * {@inheritdoc}
     */
    public function isGrouped()
    {
        return true;
    }

    /**
     * {@inheritdoc}
     */
    public function getLabel()
    {
        return $this->label;
    }

    /**
     * {@inheritdoc}
     */
    public function getTypes()
    {
        return $this->types;
    }

    /**
     * @param string $identifier
     *
     * @return DPDShippingMethodType|null
     */
    public function getType($identifier)
    {
        $methodTypes = $this->getTypes();
        if ($methodTypes !== null) {
            foreach ($methodTypes as $methodType) {
                if ($methodType->getIdentifier() === (string) $identifier) {
                    return $methodType;
                }
            }
        }

        return null;
    }

    /**
     * {@inheritdoc}
     */
    public function getOptionsConfigurationFormType()
    {
        return DPDShippingMethodOptionsType::class;
    }

    /**
     * {@inheritdoc}
     */
    public function getSortOrder()
    {
        return 20;
    }

    public function getTrackingLink($number)
    {
        //FIXME: get current locale to localize url
        return self::TRACKING_URL.$number;
    }

    /**
     * {@inheritdoc}
     */
    public function calculatePrices(ShippingContextInterface $context, array $methodOptions, array $optionsByTypes)
    {
        $prices = [];

        if (count($this->getTypes()) < 1) {
            return $prices;
        }

        foreach ($optionsByTypes as $typeId => $typeOptions) {
            $type = $this->getType($typeId);
            $prices[$typeId] = $type->calculatePrice($context, $methodOptions, $typeOptions);
        }

        return $prices;
    }
}
