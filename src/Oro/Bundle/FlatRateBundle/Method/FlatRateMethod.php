<?php

namespace Oro\Bundle\FlatRateBundle\Method;

use Oro\Bundle\ShippingBundle\Method\ShippingMethodInterface;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;

class FlatRateMethod implements ShippingMethodInterface
{
    const IDENTIFIER = 'flat_rate';

    /** @var FlatRateMethodType */
    protected $type;

    /** @var string */
    protected $label;

    /** @var int */
    protected $channelId;

    /**
     * @param string $label
     * @param int    $channelId
     */
    public function __construct($label, $channelId)
    {
        $this->type = new FlatRateMethodType();
        $this->label = $label;
        $this->channelId = $channelId;
    }

    /**
     * {@inheritdoc}
     */
    public function getIdentifier()
    {
        return static::IDENTIFIER . $this->channelId;
    }

    /**
     * {@inheritdoc}
     */
    public function isGrouped()
    {
        return false;
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
    public function getOptions()
    {
        return [];
    }

    /**
     * {@inheritdoc}
     */
    public function getSortOrder()
    {
        return 10;
    }
}
