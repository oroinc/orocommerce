<?php

namespace Oro\Bundle\InventoryBundle\Tests\Functional\ImportExport\EventListeners;

use Oro\Bundle\CatalogBundle\Fallback\Provider\CategoryFallbackProvider;
use Oro\Bundle\EntityBundle\Entity\EntityFieldFallbackValue;
use Oro\Bundle\EntityBundle\ImportExport\EventListeners\EntityFieldFallbackValueMergeListener;
use Oro\Bundle\EntityExtendBundle\PropertyAccess;
use Oro\Bundle\ImportExportBundle\Context\ContextInterface;
use Oro\Bundle\ImportExportBundle\Event\StrategyEvent;
use Oro\Bundle\ImportExportBundle\Strategy\StrategyInterface;
use Oro\Bundle\InventoryBundle\Tests\Functional\DataFixtures\LoadProductRelatedFallbackValuesData;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\ProductBundle\Tests\Functional\DataFixtures\LoadProductData;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;

class EntityFieldFallbackValueMergeListenerTest extends WebTestCase
{
    protected function setUp(): void
    {
        $this->initClient();
        $this->loadFixtures([
            LoadProductRelatedFallbackValuesData::class,
        ]);
    }

    public function testOnProcessAfter()
    {
        /** @var EntityFieldFallbackValueMergeListener $listener */
        $listener = $this->getContainer()
            ->get('oro_entity.importexport.event_listeners.entity_fallback_value_clean_listener');

        /** @var Product $product */
        $product = $this->getReference(LoadProductData::PRODUCT_1);
        $event = new StrategyEvent(
            $this->createMock(StrategyInterface::class),
            $product,
            $this->createMock(ContextInterface::class)
        );

        /** @var EntityFieldFallbackValue $oldLowInventoryValue */
        $oldLowInventoryValue = $this->getReference(LoadProductRelatedFallbackValuesData::getReferenceName(
            LoadProductData::PRODUCT_1,
            'highlightLowInventory'
        ));
        /** @var EntityFieldFallbackValue $oldInventoryThresholdValue */
        $oldInventoryThresholdValue = $this->getReference(LoadProductRelatedFallbackValuesData::getReferenceName(
            LoadProductData::PRODUCT_1,
            'inventoryThreshold'
        ));
        /** @var EntityFieldFallbackValue $oldIsUpcomingValue */
        $oldIsUpcomingValue = $this->getReference(LoadProductRelatedFallbackValuesData::getReferenceName(
            LoadProductData::PRODUCT_1,
            'isUpcoming'
        ));

        $newLowInventory = new EntityFieldFallbackValue();
        $newLowInventory->setScalarValue('1');

        $newInventoryThresholdValue = new EntityFieldFallbackValue();
        $newInventoryThresholdValue->setFallback(CategoryFallbackProvider::FALLBACK_ID);

        $accessor = PropertyAccess::createPropertyAccessor();
        $accessor->setValue($product, 'highlightLowInventory', $newLowInventory);
        $accessor->setValue($product, 'inventoryThreshold', $newInventoryThresholdValue);

        $em = $this->getContainer()->get('oro_entity.doctrine_helper')
            ->getEntityManagerForClass(EntityFieldFallbackValue::class);
        $em->persist($newLowInventory);
        $em->persist($newInventoryThresholdValue);

        $listener->onProcessAfter($event);

        $this->assertSame($oldLowInventoryValue, $accessor->getValue($product, 'highlightLowInventory'));
        $this->assertSame($newLowInventory->getFallback(), $oldLowInventoryValue->getFallback());
        $this->assertSame($newLowInventory->getScalarValue(), $oldLowInventoryValue->getScalarValue());
        $this->assertSame($newLowInventory->getArrayValue(), $oldLowInventoryValue->getArrayValue());

        $this->assertSame($oldInventoryThresholdValue, $accessor->getValue($product, 'inventoryThreshold'));
        $this->assertSame($newInventoryThresholdValue->getFallback(), $oldInventoryThresholdValue->getFallback());
        $this->assertSame($newInventoryThresholdValue->getScalarValue(), $oldInventoryThresholdValue->getScalarValue());
        $this->assertSame($newInventoryThresholdValue->getArrayValue(), $oldInventoryThresholdValue->getArrayValue());

        $this->assertSame($oldIsUpcomingValue, $accessor->getValue($product, 'isUpcoming'));

        $repo = $em->getRepository(EntityFieldFallbackValue::class);
        $totalValuesCount = $repo->count([]);
        $em->flush();
        $this->assertEmpty($newLowInventory->getId());
        $this->assertEmpty($newInventoryThresholdValue->getId());
        $this->assertSame($totalValuesCount, $repo->count([]));
    }
}
