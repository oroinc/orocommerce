<?php

namespace Oro\Bundle\ShippingBundle\Tests\Unit\Provider\Stub;

use Oro\Bundle\ShippingBundle\Context\ShippingContextInterface;
use Oro\Bundle\ShippingBundle\Method\ShippingMethodTypeInterface;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;

class ShippingMethodTypeStub implements ShippingMethodTypeInterface
{
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
     * @return string
     */
    public function getIdentifier()
    {
        return $this->identifier;
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
     * {@inheritdoc}
     */
    public function calculatePrice(ShippingContextInterface $context, array $methodOptions, array $typeOptions)
    {
        return $typeOptions['price'];
    }
}
