<?php

namespace Oro\Bundle\InventoryBundle\EventListener;

use Oro\Bundle\EntityBundle\Entity\EntityFieldFallbackValue;
use Oro\Bundle\ImportExportBundle\Event\LoadTemplateFixturesEvent;
use Oro\Bundle\InventoryBundle\Provider\ProductUpcomingProvider;
use Oro\Bundle\ProductBundle\Entity\Product;
use Symfony\Component\PropertyAccess\PropertyAccess;

/** This event listener is used to add data to product import template fixture */
class ProductImportTemplateFixtureEventListener
{
    /**
     * @param LoadTemplateFixturesEvent $event
     */
    public function afterLoadTemplateFixture(LoadTemplateFixturesEvent $event)
    {
        $entities = $event->getEntities();

        if (!$this->isAplicable($entities)) {
            return;
        }

        $entityData = current($entities[Product::class]);
        $entity = isset($entityData['entity']) ? $entityData['entity'] : null;

        if ($entity instanceof Product) {
            $fallbackEntity = new EntityFieldFallbackValue();
            $fallbackEntity->setScalarValue(1);

            $accessor = PropertyAccess::createPropertyAccessor();
            $accessor->setValue($entity, ProductUpcomingProvider::IS_UPCOMING, $fallbackEntity);
            $accessor->setValue(
                $entity,
                ProductUpcomingProvider::AVAILABILITY_DATE,
                new \DateTime('tomorrow', new \DateTimeZone('UTC'))
            );
        }
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
