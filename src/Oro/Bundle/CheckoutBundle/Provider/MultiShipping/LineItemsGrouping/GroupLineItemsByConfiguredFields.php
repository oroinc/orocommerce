<?php

namespace Oro\Bundle\CheckoutBundle\Provider\MultiShipping\LineItemsGrouping;

use Oro\Bundle\CheckoutBundle\Provider\MultiShipping\ConfigProvider;
use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;
use Oro\Bundle\ProductBundle\Model\ProductLineItemsHolderInterface;
use Symfony\Component\PropertyAccess\Exception\UnexpectedTypeException;
use Symfony\Component\PropertyAccess\PropertyAccessorInterface;

/**
 * Groups line items according to configured line items fields.
 */
class GroupLineItemsByConfiguredFields implements GroupedLineItemsProviderInterface
{
    public const OTHER_ITEMS_KEY = 'other-items';

    private ConfigProvider $configProvider;
    private PropertyAccessorInterface $propertyAccessor;
    private DoctrineHelper $doctrineHelper;
    private array $cachedGroupedLineItems = [];

    public function __construct(
        ConfigProvider $configProvider,
        PropertyAccessorInterface $propertyAccessor,
        DoctrineHelper $doctrineHelper
    ) {
        $this->configProvider = $configProvider;
        $this->propertyAccessor = $propertyAccessor;
        $this->doctrineHelper = $doctrineHelper;
    }

    /**
     * {@inheritDoc}
     */
    public function getGroupedLineItems(ProductLineItemsHolderInterface $entity): array
    {
        $cacheKey = $this->getEntityKey($entity);
        if (!isset($this->cachedGroupedLineItems[$cacheKey])) {
            $groupedLineItems = [];
            $lineItems = $entity->getLineItems();
            $groupingFieldPath = $this->configProvider->getGroupLineItemsByField();
            foreach ($lineItems as $lineItem) {
                try {
                    $value = $this->propertyAccessor->getValue($lineItem, $groupingFieldPath);
                    $key = $groupingFieldPath . ':' . $this->getGroupFieldValue($value);
                } catch (UnexpectedTypeException $e) {
                    // Use "other items" predefined key if key value could not be obtained by the property path.
                    // This is case for free from product items.
                    // Such line items are grouped in "Other Items" group.
                    $key = self::OTHER_ITEMS_KEY;
                }
                $groupedLineItems[$key][] = $lineItem;
            }
            $this->cachedGroupedLineItems[$cacheKey] = $groupedLineItems;
        }

        return $this->cachedGroupedLineItems[$cacheKey];
    }

    private function getGroupFieldValue(mixed $value): mixed
    {
        if (\is_object($value)) {
            return $this->doctrineHelper->getSingleEntityIdentifier($value);
        }

        return $value ?? 0;
    }

    private function getEntityKey(ProductLineItemsHolderInterface $entity): string
    {
        return spl_object_hash($entity);
    }
}
