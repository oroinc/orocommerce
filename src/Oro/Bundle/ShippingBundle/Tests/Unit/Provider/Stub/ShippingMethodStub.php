<?php

namespace Oro\Bundle\ShippingBundle\Tests\Unit\Provider\Stub;

use Oro\Bundle\ShippingBundle\Method\ShippingMethodInterface;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;

class ShippingMethodStub implements ShippingMethodInterface
{
    /**
     * @var ShippingMethodTypeStub[]
     */
    protected $types = [];

    /**
     * @var string
     */
    protected $identifier;

    /**
     * @var string
     */
    protected $label;

    /**
     * @var int
     */
    protected $sortOrder;

    /**
     * @var string
     */
    protected $optionsConfigurationFormType = HiddenType::class;

    /**
     * @var bool
     */
    protected $isGrouped = false;

    /**
     * @return ShippingMethodTypeStub[]
     */
    public function getTypes()
    {
        return $this->types;
    }

    /**
     * @param ShippingMethodTypeStub[] $types
     * @return $this
     */
    public function setTypes($types)
    {
        $this->types = $types;
        return $this;
    }

    /**
     * @return string
     */
    public function getIdentifier()
    {
        return $this->identifier;
    }

    /**
     * @param string $identifier
     * @return null|ShippingMethodTypeStub
     */
    public function getType($identifier)
    {
        foreach ($this->types as $type) {
            if ($type->getIdentifier() === $identifier) {
                return $type;
            }
        }
        return null;
    }

    /**
     * @param string $identifier
     * @return $this
     */
    public function setIdentifier($identifier)
    {
        $this->identifier = $identifier;
        return $this;
    }

    /**
     * @return string
     */
    public function getLabel()
    {
        return $this->label ?: $this->identifier . '.label';
    }

    /**
     * @param string $label
     * @return $this
     */
    public function setLabel($label)
    {
        $this->label = $label;
        return $this;
    }

    /**
     * @return int
     */
    public function getSortOrder()
    {
        return $this->sortOrder;
    }

    /**
     * @param int $sortOrder
     * @return $this
     */
    public function setSortOrder($sortOrder)
    {
        $this->sortOrder = $sortOrder;
        return $this;
    }

    /**
     * @return string
     */
    public function getOptionsConfigurationFormType()
    {
        return $this->optionsConfigurationFormType;
    }

    /**
     * @param string $optionsConfigurationFormType
     * @return $this
     */
    public function setOptionsConfigurationFormType($optionsConfigurationFormType)
    {
        $this->optionsConfigurationFormType = $optionsConfigurationFormType;
        return $this;
    }

    /**
     * @return boolean
     */
    public function isGrouped()
    {
        return $this->isGrouped;
    }

    /**
     * @param boolean $isGrouped
     * @return $this
     */
    public function setIsGrouped($isGrouped)
    {
        $this->isGrouped = $isGrouped;
        return $this;
    }
}
