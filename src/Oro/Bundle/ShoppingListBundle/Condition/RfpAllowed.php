<?php

namespace Oro\Bundle\ShoppingListBundle\Condition;

use Doctrine\Common\Collections\ArrayCollection;
use Oro\Bundle\RFPBundle\Form\Extension\RequestDataStorageExtension;
use Oro\Bundle\ShoppingListBundle\Entity\LineItem;
use Oro\Component\ConfigExpression\Condition\AbstractCondition;
use Oro\Component\ConfigExpression\ContextAccessorAwareInterface;
use Oro\Component\ConfigExpression\ContextAccessorAwareTrait;
use Symfony\Component\PropertyAccess\PropertyPathInterface;

/**
 * Check if at least one of products can be added to RFP
 * Usage:
 * @rfp_allowed: items
 */
class RfpAllowed extends AbstractCondition implements ContextAccessorAwareInterface
{
    use ContextAccessorAwareTrait;

    const NAME = 'rfp_allowed';

    /**
     * @var PropertyPathInterface
     */
    protected $propertyPath;

    /**
     * @var RequestDataStorageExtension
     */
    protected $requestDataStorageExtension;

    public function __construct(RequestDataStorageExtension $requestDataStorageExtension)
    {
        $this->requestDataStorageExtension = $requestDataStorageExtension;
    }

    /**
     * {@inheritdoc}
     */
    protected function isConditionAllowed($context)
    {
        $lineItems = $this->resolveValue($context, $this->propertyPath);

        if ($lineItems instanceof ArrayCollection) {
            throw new \InvalidArgumentException(
                'Property must be a valid ArrayCollection. but is '
                .get_class($lineItems)
            );
        }

        if (!empty($lineItems)) {
            $productsIds = [];
            /** @var LineItem $lineItem */
            foreach ($lineItems as $lineItem) {
                $productsIds[] = $lineItem->getProduct()->getId();
            }

            return $this->requestDataStorageExtension->isAllowedRFPByProductsIds($productsIds);
        }

        return false;
    }

    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return static::NAME;
    }

    /**
     * {@inheritdoc}
     */
    public function initialize(array $options)
    {
        $option = reset($options);

        if (!$option instanceof PropertyPathInterface) {
            throw new \InvalidArgumentException(
                'Condition option must be a PropertyPathInterface, but is '
                .get_class($option)
            );
        }

        $this->propertyPath = $option;

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function toArray()
    {
        return $this->convertToArray([$this->propertyPath]);
    }

    /**
     * {@inheritdoc}
     */
    public function compile($factoryAccessor)
    {
        return $this->convertToPhpCode([$this->propertyPath], $factoryAccessor);
    }
}
