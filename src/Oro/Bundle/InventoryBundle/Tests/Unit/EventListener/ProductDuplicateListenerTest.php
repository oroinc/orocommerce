<?php

namespace Oro\Bundle\InventoryBundle\Tests\Unit\EventListener;

use Doctrine\ORM\EntityManager;
use Oro\Bundle\EntityBundle\Entity\EntityFieldFallbackValue;
use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;
use Oro\Bundle\InventoryBundle\EventListener\ProductDuplicateListener;
use Oro\Bundle\InventoryBundle\Tests\Unit\EventListener\Stub\ProductStub as Product;
use Oro\Bundle\ProductBundle\Event\ProductDuplicateAfterEvent;
use Oro\Component\Testing\Unit\EntityTrait;

class ProductDuplicateListenerTest extends \PHPUnit\Framework\TestCase
{
    use EntityTrait;

    /** @var DoctrineHelper|\PHPUnit\Framework\MockObject\MockObject */
    private $doctrineHelper;

    /** @var ProductDuplicateListener */
    private $listener;

    /**
     * {@inheritdoc}
     */
    protected function setUp(): void
    {
        $this->doctrineHelper = $this->createMock(DoctrineHelper::class);
        $this->listener = new ProductDuplicateListener($this->getPropertyAccessor(), [
            'manageInventory',
            'highlightLowInventory',
            'inventoryThreshold',
            'lowInventoryThreshold',
            'minimumQuantityToOrder',
            'maximumQuantityToOrder',
            'decrementQuantity',
            'backOrder',
            'isUpcoming',
        ]);
        $this->listener->setDoctrineHelper($this->doctrineHelper);
    }

    public function testOnDuplicateAfter()
    {
        $sourceProduct = $this->getEntity(Product::class, [
            'id' => 1,
            'manageInventory' =>
                $this->getEntityFieldFallbackValue(['id' => 1, 'scalarValue' => 'manageInventory']),
            'highlightLowInventory' =>
                $this->getEntityFieldFallbackValue(['id' => 2, 'scalarValue' => 'highlightLowInventory']),
            'inventoryThreshold' =>
                $this->getEntityFieldFallbackValue(['id' => 3, 'scalarValue' => 'inventoryThreshold']),
            'lowInventoryThreshold' =>
                $this->getEntityFieldFallbackValue(['id' => 4, 'scalarValue' => 'lowInventoryThreshold']),
            'minimumQuantityToOrder' =>
                $this->getEntityFieldFallbackValue(['id' => 5, 'scalarValue' => 'minimumQuantityToOrder']),
            'maximumQuantityToOrder' =>
                $this->getEntityFieldFallbackValue(['id' => 6, 'scalarValue' => 'maximumQuantityToOrder']),
            'decrementQuantity' =>
                $this->getEntityFieldFallbackValue(['id' => 7, 'scalarValue' => 'decrementQuantity']),
            'backOrder' =>
                $this->getEntityFieldFallbackValue(['id' => 8, 'scalarValue' => 'backOrder']),
            'isUpcoming' =>
                $this->getEntityFieldFallbackValue(['id' => 9, 'scalarValue' => 'isUpcoming']),
        ]);

        $product = $this->getEntity(Product::class, ['id' => 2]);
        $expectedProduct = $this->getEntity(Product::class, [
            'id' => 2,
            'manageInventory' =>
                $this->getEntityFieldFallbackValue(['id' => null, 'scalarValue' => 'manageInventory']),
            'highlightLowInventory' =>
                $this->getEntityFieldFallbackValue(['id' => null, 'scalarValue' => 'highlightLowInventory']),
            'inventoryThreshold' =>
                $this->getEntityFieldFallbackValue(['id' => null, 'scalarValue' => 'inventoryThreshold']),
            'lowInventoryThreshold' =>
                $this->getEntityFieldFallbackValue(['id' => null, 'scalarValue' => 'lowInventoryThreshold']),
            'minimumQuantityToOrder' =>
                $this->getEntityFieldFallbackValue(['id' => null, 'scalarValue' => 'minimumQuantityToOrder']),
            'maximumQuantityToOrder' =>
                $this->getEntityFieldFallbackValue(['id' => null, 'scalarValue' => 'maximumQuantityToOrder']),
            'decrementQuantity' =>
                $this->getEntityFieldFallbackValue(['id' => null, 'scalarValue' => 'decrementQuantity']),
            'backOrder' =>
                $this->getEntityFieldFallbackValue(['id' => null, 'scalarValue' => 'backOrder']),
            'isUpcoming' =>
                $this->getEntityFieldFallbackValue(['id' => null, 'scalarValue' => 'isUpcoming']),
        ]);

        $em = $this->createMock(EntityManager::class);
        $em->expects($this->once())
            ->method('flush')
            ->with($expectedProduct);
        $this->doctrineHelper
            ->expects($this->once())
            ->method('getEntityManager')
            ->with(Product::class)
            ->willReturn($em);

        $event = new ProductDuplicateAfterEvent($product, $sourceProduct);
        $this->listener->onDuplicateAfter($event);

        $this->assertNull($this->getPropertyAccessor()->getValue($product, 'manageInventory')->getId());
        $this->assertNull($this->getPropertyAccessor()->getValue($product, 'highlightLowInventory')->getId());
        $this->assertNull($this->getPropertyAccessor()->getValue($product, 'inventoryThreshold')->getId());
        $this->assertNull($this->getPropertyAccessor()->getValue($product, 'lowInventoryThreshold')->getId());
        $this->assertNull($this->getPropertyAccessor()->getValue($product, 'minimumQuantityToOrder')->getId());
        $this->assertNull($this->getPropertyAccessor()->getValue($product, 'maximumQuantityToOrder')->getId());
        $this->assertNull($this->getPropertyAccessor()->getValue($product, 'decrementQuantity')->getId());
        $this->assertNull($this->getPropertyAccessor()->getValue($product, 'backOrder')->getId());
        $this->assertNull($this->getPropertyAccessor()->getValue($product, 'isUpcoming')->getId());
    }

    public function testOnDuplicateAfterWithoutMeta()
    {
        $sourceProduct = $this->getEntity(Product::class, ['id' => 1]);
        $product = $this->getEntity(Product::class, ['id' => 2]);
        $expectedProduct = $this->getEntity(Product::class, [
            'id' => 2,
            'manageInventory' => null,
            'highlightLowInventory' => null,
            'inventoryThreshold' => null,
            'lowInventoryThreshold' => null,
            'minimumQuantityToOrder' => null,
            'maximumQuantityToOrder' => null,
            'decrementQuantity' => null,
            'backOrder' => null,
            'isUpcoming' => null,
        ]);

        $em = $this->createMock(EntityManager::class);
        $em->expects($this->once())
            ->method('flush')
            ->with($expectedProduct);
        $this->doctrineHelper
            ->expects($this->once())
            ->method('getEntityManager')
            ->with(Product::class)
            ->willReturn($em);

        $event = new ProductDuplicateAfterEvent($product, $sourceProduct);
        $this->listener->onDuplicateAfter($event);

        $this->assertNull($this->getPropertyAccessor()->getValue($product, 'manageInventory'));
        $this->assertNull($this->getPropertyAccessor()->getValue($product, 'highlightLowInventory'));
        $this->assertNull($this->getPropertyAccessor()->getValue($product, 'inventoryThreshold'));
        $this->assertNull($this->getPropertyAccessor()->getValue($product, 'lowInventoryThreshold'));
        $this->assertNull($this->getPropertyAccessor()->getValue($product, 'minimumQuantityToOrder'));
        $this->assertNull($this->getPropertyAccessor()->getValue($product, 'maximumQuantityToOrder'));
        $this->assertNull($this->getPropertyAccessor()->getValue($product, 'decrementQuantity'));
        $this->assertNull($this->getPropertyAccessor()->getValue($product, 'backOrder'));
        $this->assertNull($this->getPropertyAccessor()->getValue($product, 'isUpcoming'));
    }

    /**
     * @param array $properties
     *
     * @return EntityFieldFallbackValue
     */
    private function getEntityFieldFallbackValue(array $properties = [])
    {
        return $this->getEntity(EntityFieldFallbackValue::class, $properties);
    }
}
