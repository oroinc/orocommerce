<?php

namespace Oro\Bundle\CheckoutBundle\Provider\MultiShipping\LineItemsGrouping;

use Oro\Bundle\CheckoutBundle\Provider\MultiShipping\ConfigProvider;
use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;
use Oro\Bundle\ProductBundle\Model\ProductLineItemsHolderInterface;
use Symfony\Component\PropertyAccess\Exception\UnexpectedTypeException;
use Symfony\Component\PropertyAccess\PropertyAccessorInterface;

/**
 * Group line items according to configured line items fields.
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

    public function getGroupedLineItems(ProductLineItemsHolderInterface $entity): array
    {
        $cacheKey = $this->getEntityKey($entity);
        if (!isset($this->cachedGroupedLineItems[$cacheKey])) {
            $groupedLineItems = [];
            $lineItems = $entity->getLineItems();
            $groupedFieldPath = $this->configProvider->getGroupLineItemsByField();

            foreach ($lineItems as $lineItem) {
                try {
                    $value = $this->propertyAccessor->getValue($lineItem, $groupedFieldPath);
                    $groupFieldValue = $this->getGroupFieldValue($value);
                    $key = $groupedFieldPath . ':' . $groupFieldValue;
                } catch (UnexpectedTypeException $exception) {
                    // Use other items default key if key value could not be obtained by property path.
                    // This is case for free from product items.
                    $key = static::OTHER_ITEMS_KEY;
                }

                $groupedLineItems[$key][] = $lineItem;
            }

            $this->cachedGroupedLineItems[$cacheKey] = $groupedLineItems;
        }

        return $this->cachedGroupedLineItems[$cacheKey];
    }

    /**
     * @param mixed $value
     * @return mixed
     */
    private function getGroupFieldValue($value)
    {
        if (is_object($value)) {
            $value = $this->doctrineHelper->getSingleEntityIdentifier($value);
        } elseif (null === $value) {
            $value = 0;
        }

        return $value;
    }

    private function getEntityKey(ProductLineItemsHolderInterface $entity): string
    {
        return spl_object_hash($entity);
    }
}
