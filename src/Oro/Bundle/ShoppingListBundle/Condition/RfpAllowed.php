<?php

namespace Oro\Bundle\ShoppingListBundle\Condition;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\PersistentCollection;
use Oro\Bundle\RFPBundle\Provider\ProductRFPAvailabilityProvider;
use Oro\Bundle\RFPBundle\Resolver\ShoppingListToRequestQuoteValidationGroupResolver;
use Oro\Bundle\ShoppingListBundle\Entity\LineItem;
use Oro\Bundle\ShoppingListBundle\Entity\ShoppingList;
use Oro\Bundle\ShoppingListBundle\Provider\InvalidShoppingListLineItemsProvider;
use Oro\Component\ConfigExpression\Condition\AbstractCondition;
use Oro\Component\ConfigExpression\ContextAccessorAwareInterface;
use Oro\Component\ConfigExpression\ContextAccessorAwareTrait;
use Symfony\Component\PropertyAccess\PropertyPathInterface;

/**
 * Checks whether products can be added to RFP.
 * Usage:
 * @rfp_allowed: items
 */
class RfpAllowed extends AbstractCondition implements ContextAccessorAwareInterface
{
    use ContextAccessorAwareTrait;

    private ProductRFPAvailabilityProvider $productAvailabilityProvider;
    private PropertyPathInterface $propertyPath;
    private InvalidShoppingListLineItemsProvider $provider;

    public function __construct(ProductRFPAvailabilityProvider $productAvailabilityProvider)
    {
        $this->productAvailabilityProvider = $productAvailabilityProvider;
    }

    public function setInvalidShoppingListLineItemsProvider(InvalidShoppingListLineItemsProvider $provider): void
    {
        $this->provider = $provider;
    }

    #[\Override]
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

        if (empty($lineItems) || ($lineItems instanceof PersistentCollection && $lineItems->count() === 0)) {
            return false;
        }

        $productsIds = [];
        /** @var LineItem $lineItem */
        foreach ($lineItems as $lineItem) {
            $productsIds[] = $lineItem->getProduct()->getId();
        }

        if ($lineItem?->getShoppingList() && $this->isInvalidShoppingList($lineItem->getShoppingList())) {
            return false;
        }

        if ($productsIds) {
            return $this->productAvailabilityProvider->hasProductsAllowedForRFP($productsIds);
        }

        return false;
    }

    #[\Override]
    public function getName()
    {
        return 'rfp_allowed';
    }

    #[\Override]
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

    #[\Override]
    public function toArray()
    {
        return $this->convertToArray([$this->propertyPath]);
    }

    #[\Override]
    public function compile($factoryAccessor)
    {
        return $this->convertToPhpCode([$this->propertyPath], $factoryAccessor);
    }

    private function isInvalidShoppingList(ShoppingList $shoppingList): bool
    {
        $invalidIds = $this->provider->getInvalidLineItemsIds(
            $shoppingList->getLineItems(),
            ShoppingListToRequestQuoteValidationGroupResolver::TYPE
        );

        return !empty($invalidIds);
    }
}
