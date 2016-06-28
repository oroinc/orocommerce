<?php

namespace OroB2B\Bundle\PricingBundle\Provider;

use Doctrine\Bundle\DoctrineBundle\Registry;

use Oro\Bundle\EntityBundle\Provider\VirtualFieldProviderInterface;

class PriceRuleAttributeProvider
{
    /**
     * @var VirtualFieldProviderInterface
     */
    protected $virtualFieldProvider;

    /**
     * @var Registry
     */
    protected $registry;

    /**
     * @var array
     */
    protected $availableClasses = [];

    /**
     * @var array
     */
    protected $availableRuleAttributes;

    /**
     * @var array
     */
    protected $availableConditionAttributes;

    /**
     * @param Registry $registry
     * @param VirtualFieldProviderInterface $virtualFieldProvider
     */
    public function __construct(Registry $registry, VirtualFieldProviderInterface $virtualFieldProvider)
    {
        $this->registry = $registry;
        $this->virtualFieldProvider = $virtualFieldProvider;
    }

    /**
     * @return array
     */
    public function getAvailableRuleAttributes()
    {
        if ($this->availableRuleAttributes === null) {
            $this->availableRuleAttributes = [];
            $availableFieldTypes = ['integer', 'float', 'money', 'decimal'];
            foreach ($this->getAvailableClasses() as $class) {
                $metadata = $this->registry->getManagerForClass($class)->getClassMetadata($class);
                foreach ($metadata->getFieldNames() as $fieldName) {
                    if (in_array($metadata->getTypeOfField($fieldName), $availableFieldTypes, true)) {
                        $this->availableRuleAttributes[$class][] = $fieldName;
                    }
                }
            }
        }
        return $this->availableRuleAttributes;

    }

    /**
     * @return array
     */
    public function getAvailableConditionAttributes()
    {
        if ($this->availableConditionAttributes === null) {
            $this->availableConditionAttributes = $this->getAvailableRuleAttributes();
            foreach ($this->getAvailableClasses() as $class) {
                $classAttributes = [];
                if (isset($this->availableConditionAttributes[$class])) {
                    $classAttributes = $this->availableConditionAttributes[$class];
                }
                $virtualFields = $this->virtualFieldProvider->getVirtualFields($class);
                if (!empty($virtualFields)) {
                    $classAttributes = array_merge($classAttributes, $virtualFields);
                }
                if (!empty($classAttributes)) {
                    $this->availableConditionAttributes[$class] = $classAttributes;
                }
            }
        }

        return $this->availableConditionAttributes;
    }

    /**
     * @return array|string[]
     */
    public function getAvailableClasses()
    {
        return $this->availableClasses;
    }

    /**
     * @param string $class
     */
    public function addAvailableClass($class)
    {
        $this->availableClasses[] = $class;
    }
}
