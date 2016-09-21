<?php

namespace Oro\Bundle\ScopeBundle\Manager;

use Oro\Component\PropertyAccess\PropertyAccessor;
use Symfony\Component\PropertyAccess\Exception\NoSuchPropertyException;

abstract class AbstractScopeProvider implements ScopeProviderInterface
{
    /**
     * @var PropertyAccessor
     */
    protected $propertyAccessor;

    /**
     * @param array|object $context
     * @return array
     */
    public function getCriteriaByContext($context)
    {
        $this->propertyAccessor = new PropertyAccessor();
        if (is_object($context) || is_array($context)) {
            $value = $this->getValue($context);
            if (is_a($value, $this->getCriteriaValueType())) {
                return [$this->getCriteriaField() => $value];
            }
        }

        return [];
    }

    /**
     * {@inheritdoc}
     */
    abstract public function getCriteriaForCurrentScope();

    /**
     * @return string
     */
    abstract protected function getCriteriaField();

    /**
     * @return string
     */
    abstract protected function getCriteriaValueType();

    /**
     * @param object|array $context
     * @return mixed|null
     */
    protected function getValue($context)
    {
        try {
            return $this->getPropertyAccessor()
                ->getValue($context, $this->getCriteriaField());
        } catch (NoSuchPropertyException $e) {
            return null;
        }
    }

    /**
     * @return PropertyAccessor
     */
    protected function getPropertyAccessor()
    {
        if (!$this->propertyAccessor) {
            $this->propertyAccessor = new PropertyAccessor();
        }

        return $this->propertyAccessor;
    }
}
