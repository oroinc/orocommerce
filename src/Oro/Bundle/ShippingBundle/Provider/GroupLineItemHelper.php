<?php

namespace Oro\Bundle\ShippingBundle\Provider;

use Doctrine\Common\Collections\Collection;
use Oro\Bundle\CheckoutBundle\Provider\MultiShipping\ConfigProvider;
use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;
use Symfony\Component\PropertyAccess\Exception\UnexpectedTypeException;
use Symfony\Component\PropertyAccess\PropertyAccessorInterface;

/**
 * Provides a set of methods that help working with grouped line items in context of Multi Shipping functionality.
 */
class GroupLineItemHelper implements GroupLineItemHelperInterface
{
    public const GROUPING_DELIMITER = ':';
    public const OTHER_ITEMS_KEY = 'other-items';

    private ConfigProvider $configProvider;
    private PropertyAccessorInterface $propertyAccessor;
    private DoctrineHelper $doctrineHelper;

    public function __construct(
        ConfigProvider $configProvider,
        PropertyAccessorInterface $propertyAccessor,
        DoctrineHelper $doctrineHelper
    ) {
        $this->configProvider = $configProvider;
        $this->propertyAccessor = $propertyAccessor;
        $this->doctrineHelper = $doctrineHelper;
    }

    #[\Override]
    public function getGroupedLineItems(Collection $lineItems, string $groupingFieldPath): array
    {
        $groupedLineItems = [];
        foreach ($lineItems as $lineItem) {
            $groupedLineItems[$this->getLineItemGroupKey($lineItem, $groupingFieldPath)][] = $lineItem;
        }

        return $groupedLineItems;
    }

    #[\Override]
    public function isLineItemsGroupedByOrganization(string $groupingFieldPath): bool
    {
        return false;
    }

    #[\Override]
    public function getGroupingFieldPath(): string
    {
        return $this->configProvider->getGroupLineItemsByField();
    }

    #[\Override]
    public function getGroupingFieldValue(object $lineItem, string $groupingFieldPath): mixed
    {
        try {
            return $this->propertyAccessor->getValue($lineItem, $groupingFieldPath);
        } catch (UnexpectedTypeException) {
            return null;
        }
    }

    #[\Override]
    public function getLineItemGroupKey(object $lineItem, string $groupingFieldPath): string
    {
        try {
            $value = $this->propertyAccessor->getValue($lineItem, $groupingFieldPath);
        } catch (UnexpectedTypeException) {
            // Use "other items" predefined key if key value could not be obtained by the property path.
            // This is case for free from product items.
            // Such line items are grouped in "Other Items" group.
            return self::OTHER_ITEMS_KEY;
        }

        return
            $groupingFieldPath
            . self::GROUPING_DELIMITER
            . $this->getGroupingFieldValueForLineItemGroupKey($value);
    }

    private function getGroupingFieldValueForLineItemGroupKey(mixed $value): string
    {
        if (\is_object($value)) {
            return (string)$this->doctrineHelper->getSingleEntityIdentifier($value);
        }

        return (string)($value ?? '0');
    }
}
