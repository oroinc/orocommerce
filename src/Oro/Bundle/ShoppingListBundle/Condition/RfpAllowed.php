<?php

namespace Oro\Bundle\ShoppingListBundle\Condition;

use Doctrine\Common\Collections\ArrayCollection;
use Oro\Bundle\RFPBundle\Provider\ProductAvailabilityProvider;
use Oro\Bundle\ShoppingListBundle\Entity\LineItem;
use Oro\Component\ConfigExpression\Condition\AbstractCondition;
use Oro\Component\ConfigExpression\ContextAccessorAwareInterface;
use Oro\Component\ConfigExpression\ContextAccessorAwareTrait;
use Symfony\Component\PropertyAccess\PropertyPathInterface;

/**
 * Checks whether at least one of products can be added to RFP.
 * Usage:
 * @rfp_allowed: items
 */
class RfpAllowed extends AbstractCondition implements ContextAccessorAwareInterface
{
    use ContextAccessorAwareTrait;

    private ProductAvailabilityProvider $productAvailabilityProvider;
    private PropertyPathInterface $propertyPath;

    public function __construct(ProductAvailabilityProvider $productAvailabilityProvider)
    {
        $this->productAvailabilityProvider = $productAvailabilityProvider;
    }

    /**
     * {@inheritDoc}
     */
    protected function isConditionAllowed($context)
    {
        $lineItems = $this->resolveValue($context, $this->propertyPath);

        if ($lineItems instanceof ArrayCollection) {
            throw new \InvalidArgumentException(sprintf(
                'Property must be a valid "%s", but got "%s".',
                ArrayCollection::class,
                \get_class($lineItems)
            ));
        }

        if (!empty($lineItems)) {
            $productsIds = [];
            /** @var LineItem $lineItem */
            foreach ($lineItems as $lineItem) {
                $productsIds[] = $lineItem->getProduct()->getId();
            }

            return $this->productAvailabilityProvider->hasProductsAllowedForRFP($productsIds);
        }

        return false;
    }

    /**
     * {@inheritDoc}
     */
    public function getName()
    {
        return 'rfp_allowed';
    }

    /**
     * {@inheritDoc}
     */
    public function initialize(array $options)
    {
        $option = reset($options);

        if (!$option instanceof PropertyPathInterface) {
            throw new \InvalidArgumentException(sprintf(
                'Condition option must be "%s", but got "%s".',
                PropertyPathInterface::class,
                \get_class($option)
            ));
        }

        $this->propertyPath = $option;

        return $this;
    }

    /**
     * {@inheritDoc}
     */
    public function toArray()
    {
        return $this->convertToArray([$this->propertyPath]);
    }

    /**
     * {@inheritDoc}
     */
    public function compile($factoryAccessor)
    {
        return $this->convertToPhpCode([$this->propertyPath], $factoryAccessor);
    }
}
