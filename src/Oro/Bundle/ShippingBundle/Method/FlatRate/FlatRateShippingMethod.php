<?php

namespace Oro\Bundle\ShippingBundle\Method\FlatRate;

use Oro\Bundle\ShippingBundle\Method\ShippingMethodInterface;

class FlatRateShippingMethod implements ShippingMethodInterface
{
    const IDENTIFIER = 'flat_rate';

    /**
     * @var FlatRateShippingMethodType
     */
    protected $type;

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
        // TODO: Implement in BB-4287
        return null;
    }

    /**
     * {@inheritdoc}
     */
    public function getSortOrder()
    {
        return 10;
    }
}
