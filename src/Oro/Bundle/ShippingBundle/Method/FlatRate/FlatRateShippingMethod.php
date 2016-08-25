<?php

namespace Oro\Bundle\ShippingBundle\Method\FlatRate;

use Oro\Bundle\ShippingBundle\Method\ShippingMethodInterface;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;

class FlatRateShippingMethod implements ShippingMethodInterface
{
    const IDENTIFIER = 'flat_rate';

    /**
     * @var FlatRateShippingMethodType
     */
    protected $type;

    /**
     * Construct
     */
    public function __construct()
    {
        $this->type = new FlatRateShippingMethodType();
    }

    /**
     * {@inheritdoc}
     */
    public function getIdentifier()
    {
        return static::IDENTIFIER;
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
        return 'oro.shipping.method.flat_rate.label';
    }

    /**
     * {@inheritdoc}
     */
    public function getTypes()
    {
        return [$this->type];
    }

    /**
     * {@inheritdoc}
     */
    public function getType($type)
    {
        if ($this->type->getIdentifier() === $type) {
            return $this->type;
        }
        return null;
    }

    /**
     * {@inheritdoc}
     */
    public function getOptionsConfigurationFormType()
    {
        return HiddenType::class;
    }

    /**
     * {@inheritdoc}
     */
    public function getSortOrder()
    {
        return 10;
    }
}
