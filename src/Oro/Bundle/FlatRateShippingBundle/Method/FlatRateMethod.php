<?php

namespace Oro\Bundle\FlatRateShippingBundle\Method;

use Oro\Bundle\ShippingBundle\Method\ShippingMethodInterface;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;

class FlatRateMethod implements ShippingMethodInterface
{
    /**
     * @var FlatRateMethodType
     */
    protected $type;

    /**
     * @var string
     */
    protected $label;

    /**
     * @var int
     */
    protected $identifier;

    /**
     * @var bool
     */
    private $enabled;

    /**
     * @param int|string $identifier
     * @param string     $label
     * @param bool       $enabled
     */
    public function __construct($identifier, $label, $enabled)
    {
        $this->identifier = $identifier;
        $this->label = $label;
        $this->type = new FlatRateMethodType($label);
        $this->enabled = $enabled;
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
    public function isGrouped()
    {
        return false;
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
    public function getLabel()
    {
        return $this->label;
    }

    /**
     * {@inheritDoc}
     */
    public function getTypes()
    {
        return [$this->type];
    }

    /**
     * {@inheritDoc}
     */
    public function getType($type)
    {
        if ($this->type->getIdentifier() === $type) {
            return $this->type;
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
    public function getOptions()
    {
        return [];
    }

    /**
     * {@inheritDoc}
     */
    public function getSortOrder()
    {
        return 10;
    }
}
