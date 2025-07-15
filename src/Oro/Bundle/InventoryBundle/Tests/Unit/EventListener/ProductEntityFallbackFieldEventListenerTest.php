<?php

declare(strict_types=1);

namespace Oro\Bundle\InventoryBundle\Tests\Unit\EventListener;

use Doctrine\Persistence\Event\LifecycleEventArgs;
use Oro\Bundle\CatalogBundle\Fallback\Provider\CategoryFallbackProvider;
use Oro\Bundle\EntityBundle\Entity\EntityFieldFallbackValue;
use Oro\Bundle\InventoryBundle\EventListener\ProductEntityFallbackFieldEventListener;
use Oro\Bundle\InventoryBundle\Provider\UpcomingProductProvider;
use Oro\Bundle\ProductBundle\Entity\Product;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\PropertyAccess\PropertyAccessorInterface;

final class ProductEntityFallbackFieldEventListenerTest extends TestCase
{
    private const array FALLBACK_FIELDS = [
        'manageInventory',
        'highlightLowInventory',
        'inventoryThreshold',
        'lowInventoryThreshold',
        'backOrder',
        'decrementQuantity',
        UpcomingProductProvider::IS_UPCOMING,
        'minimumQuantityToOrder',
        'maximumQuantityToOrder',
    ];

    private PropertyAccessorInterface&MockObject $propertyAccessor;
    private ProductEntityFallbackFieldEventListener $listener;
    private LifecycleEventArgs&MockObject $args;

    protected function setUp(): void
    {
        $this->propertyAccessor = $this->createMock(PropertyAccessorInterface::class);
        $this->listener = new ProductEntityFallbackFieldEventListener($this->propertyAccessor);
        $this->args = $this->createMock(LifecycleEventArgs::class);
    }

    public function testPrePersistSetsFallbackForAllFieldsWhenNoneAreSet(): void
    {
        $product = new Product();

        $this->propertyAccessor
            ->expects(self::exactly(count(self::FALLBACK_FIELDS)))
            ->method('getValue')
            ->willReturn(null);

        $setValueConsecutiveArgs = [];
        foreach (self::FALLBACK_FIELDS as $field) {
            $setValueConsecutiveArgs[] = [
                $product,
                $field,
                self::callback(static function (EntityFieldFallbackValue $fallback) {
                    self::assertEquals(CategoryFallbackProvider::FALLBACK_ID, $fallback->getFallback());

                    return true;
                })
            ];
        }

        $this->propertyAccessor
            ->expects(self::exactly(count(self::FALLBACK_FIELDS)))
            ->method('setValue')
            ->withConsecutive(...$setValueConsecutiveArgs);

        $this->listener->prePersist($product, $this->args);
    }

    public function testPrePersistOnlySetsFallbackForFieldsThatAreNotSet(): void
    {
        $product = new Product();

        $fieldsWithoutValue = [self::FALLBACK_FIELDS[0], self::FALLBACK_FIELDS[3]];

        $valueMap = [];
        foreach (self::FALLBACK_FIELDS as $field) {
            $valueMap[] = [
                $product,
                $field,
                \in_array($field, $fieldsWithoutValue, true) ? null : new EntityFieldFallbackValue()
            ];
        }
        $this->propertyAccessor
            ->expects(self::any())
            ->method('getValue')
            ->willReturnMap($valueMap);

        $setValueConsecutiveArgs = [];
        foreach ($fieldsWithoutValue as $field) {
            $setValueConsecutiveArgs[] = [
                $product,
                $field,
                self::callback(static function (EntityFieldFallbackValue $fallback) {
                    self::assertEquals(CategoryFallbackProvider::FALLBACK_ID, $fallback->getFallback());

                    return true;
                })
            ];
        }

        $this->propertyAccessor
            ->expects(self::exactly(count($fieldsWithoutValue)))
            ->method('setValue')
            ->withConsecutive(...$setValueConsecutiveArgs);

        $this->listener->prePersist($product, $this->args);
    }
}
