<?php

namespace OroB2B\Bundle\PricingBundle\Provider;

use Doctrine\Bundle\DoctrineBundle\Registry;

use Oro\Bundle\EntityBundle\Provider\VirtualFieldProviderInterface;

class PriceRuleAttributeProvider
{
    const SUPPORTED_TYPES = ['integer' => true, 'float' => true, 'money' => true, 'decimal' => true];

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
            foreach ($this->getAvailableClasses() as $class) {
                $metadata = $this->registry->getManagerForClass($class)->getClassMetadata($class);
                foreach ($metadata->getFieldNames() as $fieldName) {
                    $type = $metadata->getTypeOfField($fieldName);
                    if (!empty(self::SUPPORTED_TYPES[$type])) {
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
            $this->availableConditionAttributes = [];
            foreach ($this->getAvailableClasses() as $class) {
                $classAttributes = $this->registry
                    ->getManagerForClass($class)
                    ->getClassMetadata($class)
                    ->getFieldNames();
                $virtualFields = $this->virtualFieldProvider->getVirtualFields($class);
                if (0 !== count($virtualFields)) {
                    $classAttributes = array_merge($classAttributes, $virtualFields);
                }
                if (0 !== count($classAttributes)) {
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
