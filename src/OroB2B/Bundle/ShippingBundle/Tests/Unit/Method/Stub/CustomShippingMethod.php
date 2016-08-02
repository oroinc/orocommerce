<?php

namespace OroB2B\Bundle\ShippingBundle\Tests\Unit\Method\Stub;

use OroB2B\Bundle\ShippingBundle\Entity\ShippingRuleConfiguration;
use OroB2B\Bundle\ShippingBundle\Method\ShippingMethodInterface;

class CustomShippingMethod implements ShippingMethodInterface
{
    /**
     * @var string
     */
    protected $name;

    /**
     * @var array
     */
    protected $types = [];

    /**
     * @var string
     */
    protected $formType;

    /**
     * @var string
     */
    protected $ruleConfigClass;

    /**
     * @return mixed
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * @param mixed $name
     * @return $this
     */
    public function setName($name)
    {
        $this->name = $name;
        return $this;
    }

    /**
     * @return array
     */
    public function getTypes()
    {
        return $this->types;
    }

    /**
     * @param array $types
     * @return $this
     */
    public function setTypes($types)
    {
        $this->types = $types;
        return $this;
    }

    /**
     * @return mixed
     */
    public function getFormType()
    {
        return $this->formType;
    }

    /**
     * @param mixed $formType
     * @return $this
     */
    public function setFormType($formType)
    {
        $this->formType = $formType;
        return $this;
    }

    /**
     * @return string
     */
    public function getRuleConfigurationClass()
    {
        return $this->ruleConfigClass;
    }

    /**
     * @param string $ruleConfigClass
     * @return $this
     */
    public function setRuleConfigurationClass($ruleConfigClass)
    {
        $this->ruleConfigClass = $ruleConfigClass;
        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function calculatePrice(ShippingRuleConfiguration $entity)
    {
        throw new \RuntimeException('Not implemented');
    }
}
