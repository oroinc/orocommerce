<?php

namespace Oro\Bundle\InventoryBundle\EventListener;

use Oro\Bundle\EntityBundle\Entity\EntityFieldFallbackValue;
use Oro\Bundle\EntityBundle\Fallback\Provider\SystemConfigFallbackProvider;
use Oro\Bundle\EntityExtendBundle\PropertyAccess;
use Oro\Bundle\ImportExportBundle\Event\LoadTemplateFixturesEvent;
use Oro\Bundle\InventoryBundle\Provider\UpcomingProductProvider;
use Oro\Bundle\ProductBundle\Entity\Product;

/** This event listener is used to add data to product import template fixture */
class ProductImportTemplateFixtureEventListener
{
    public function afterLoadTemplateFixture(LoadTemplateFixturesEvent $event)
    {
        $entities = $event->getEntities();

        if (!$this->isAplicable($entities)) {
            return;
        }

        $entityData = current($entities[Product::class]);
        $entity = isset($entityData['entity']) ? $entityData['entity'] : null;

        if ($entity instanceof Product) {
            $accessor = PropertyAccess::createPropertyAccessor();
            $accessor->setValue(
                $entity,
                UpcomingProductProvider::AVAILABILITY_DATE,
                new \DateTime('tomorrow', new \DateTimeZone('UTC'))
            );
            foreach ($this->getFallbacks() as $fieldName => $fallbackValue) {
                $accessor->setValue($entity, $fieldName, $fallbackValue);
            }
        }
    }

    /**
     * @return EntityFieldFallbackValue[]
     */
    private function getFallbacks(): array
    {
        $systemFallback = new EntityFieldFallbackValue();
        $systemFallback->setFallback(SystemConfigFallbackProvider::FALLBACK_ID);

        $scalarValueFallback = new EntityFieldFallbackValue();
        $scalarValueFallback->setScalarValue(false);

        return [
            'manageInventory' => $systemFallback,
            'highlightLowInventory' => clone $systemFallback,
            'inventoryThreshold' => clone $systemFallback,
            'lowInventoryThreshold' => clone $systemFallback,
            'backOrder' => clone $systemFallback,
            'decrementQuantity' => clone $systemFallback,
            'isUpcoming' => $scalarValueFallback,
            'minimumQuantityToOrder' => clone $systemFallback,
            'maximumQuantityToOrder' => clone $systemFallback,
        ];
    }

    /**
     * @param $array
     *
     * @return bool
     */
    protected function isAplicable($array)
    {
        return array_key_exists(Product::class, $array);
    }
}
