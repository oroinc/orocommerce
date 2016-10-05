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

    /**
     * @param RequestDataStorageExtension $requestDataStorageExtension
     */
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

        return $this->isAllowedRFP($lineItems);
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

    /**
     * @param LineItem[]|ArrayCollection $lineItems
     * @return boolean
     */
    private function isAllowedRFP($lineItems)
    {
        if (!empty($lineItems)) {
            $products = [];
            foreach ($lineItems as $lineItem) {
                /** @var LineItem $lineItem */
                $products[]['productSku'] = $lineItem->getProduct()->getSku();
            }

            return $this->requestDataStorageExtension->isAllowedRFP($products);
        }

        return false;
    }
}
