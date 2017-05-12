<?php

namespace Oro\Bundle\DPDBundle\Method;

use Oro\Bundle\DPDBundle\Form\Type\DPDShippingMethodOptionsType;
use Oro\Bundle\ShippingBundle\Context\ShippingContextInterface;
use Oro\Bundle\ShippingBundle\Method\PricesAwareShippingMethodInterface;
use Oro\Bundle\ShippingBundle\Method\ShippingMethodInterface;
use Oro\Bundle\ShippingBundle\Method\ShippingMethodTypeInterface;
use Oro\Bundle\ShippingBundle\Method\ShippingTrackingAwareInterface;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;

class DPDShippingMethod implements
    ShippingMethodInterface,
    ShippingTrackingAwareInterface,
    PricesAwareShippingMethodInterface,
    DPDHandledShippingMethodAwareInterface
{
    const IDENTIFIER = 'dpd';
    const TRACKING_URL = 'https://tracking.dpd.de/parcelstatus?query=';
    const TRACKING_REGEX = '/\b 0 [0-9]{13}\b/x';

    const HANDLING_FEE_OPTION = 'handling_fee';

    /**
     * @var string
     */
    private $identifier;

    /**
     * @var string
     */
    private $label;

    /**
     * @var bool
     */
    private $isEnabled;

    /**
     * @var ShippingMethodTypeInterface[]
     */
    private $types;

    /**
     * @var DPDHandlerInterface[]
     */
    private $handlers;

    /**
     * Construct.
     *
     * @param string               $identifier
     * @param string               $label
     * @param bool                 $isEnabled
     * @param array                $types
     * @param array                $handlers
     */
    public function __construct(
        $identifier,
        $label,
        $isEnabled,
        array $types,
        array $handlers
    ) {
        $this->identifier = $identifier;
        $this->label = $label;
        $this->isEnabled = $isEnabled;
        $this->types = $types;
        $this->handlers = $handlers;
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
    public function isEnabled()
    {
        return $this->isEnabled;
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
    public function getLabel()
    {
        return $this->label;
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
     * {@inheritDoc}
     */
    public function getOptionsConfigurationFormType()
    {
        return HiddenType::class;
    }

    /**
     * {@inheritDoc}
     */
    public function getSortOrder()
    {
        return 20;
    }

    /**
     * @param string $number
     *
     * @return null|string
     */
    public function getTrackingLink($number)
    {
        if (!preg_match(self::TRACKING_REGEX, $number, $match)) {
            return null;
        }

        return self::TRACKING_URL.$match[0];
    }

    /**
     * {@inheritDoc}
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

    /**
     * {@inheritDoc}
     */
    public function getDPDHandlers()
    {
        return $this->handlers;
    }

    /**
     * {@inheritDoc}
     */
    public function getDPDHandler($identifier)
    {
        $handlers = $this->getDPDHandlers();
        if ($handlers !== null) {
            foreach ($handlers as $handler) {
                if ($handler->getIdentifier() === (string) $identifier) {
                    return $handler;
                }
            }
        }

        return null;
    }
}
