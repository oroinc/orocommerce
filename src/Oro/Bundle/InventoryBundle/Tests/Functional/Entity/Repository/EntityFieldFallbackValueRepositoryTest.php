<?php

namespace Oro\Bundle\InventoryBundle\Tests\Functional\Entity\Repository;

use Oro\Bundle\EntityBundle\Entity\EntityFieldFallbackValue;
use Oro\Bundle\EntityBundle\Entity\EntityFieldFallbackValueRepository;
use Oro\Bundle\InventoryBundle\Tests\Functional\DataFixtures\LoadProductRelatedFallbackValuesData;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\ProductBundle\Tests\Functional\DataFixtures\LoadProductData;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;

class EntityFieldFallbackValueRepositoryTest extends WebTestCase
{
    /** @var EntityFieldFallbackValueRepository */
    private $repo;

    protected function setUp(): void
    {
        $this->initClient();
        $this->loadFixtures([
            LoadProductRelatedFallbackValuesData::class,
        ]);

        $this->repo = $this->client->getContainer()
            ->get('oro_entity.doctrine_helper')
            ->getEntityRepository(EntityFieldFallbackValue::class);
    }

    private function getFallbackValue(string $fieldName): EntityFieldFallbackValue
    {
        return $this->getReference(LoadProductRelatedFallbackValuesData::getReferenceName(
            LoadProductData::PRODUCT_1,
            $fieldName
        ));
    }

    public function testFindByEntityFields()
    {
        $product = $this->getReference(LoadProductData::PRODUCT_1);

        $manageInventoryFallbackValue = $this->getFallbackValue('manageInventory');
        $highlightLowInventory = $this->getFallbackValue('highlightLowInventory');
        $inventoryThreshold = $this->getFallbackValue('inventoryThreshold');
        $lowInventoryThreshold = $this->getFallbackValue('lowInventoryThreshold');

        $this->assertSame([], $this->repo->findByEntityFields($product, []));

        $this->assertEquals([
            'manageInventory' => $manageInventoryFallbackValue,
            'highlightLowInventory' => $highlightLowInventory,
            'inventoryThreshold' => $inventoryThreshold,
            'lowInventoryThreshold' => $lowInventoryThreshold,
        ], $this->repo->findByEntityFields($product, [
            'manageInventory',
            'highlightLowInventory',
            'inventoryThreshold',
            'minimumQuantityToOrder',
            'lowInventoryThreshold',
        ]));
    }

    public function testGetIdsByEntityFieldsException()
    {
        $this->expectException(\InvalidArgumentException::class);

        $this->repo->findByEntityFields($this->getReference(LoadProductData::PRODUCT_1), [
            'inventoryThreshold',
            'some_absent_field',
            'highlightLowInventory',
        ]);
    }

    public function testFindEntityId()
    {
        $fieldName = 'highlightLowInventory';
        $productId = $this->getReference(LoadProductData::PRODUCT_1)->getId();
        $entityFieldFallbackValueId = $this->getFallbackValue($fieldName)->getId();

        $this->assertSame(
            $productId,
            $this->repo->findEntityId(Product::class, $fieldName, $entityFieldFallbackValueId)
        );
    }

    public function testFindEntityIdWhenEntityNotFound()
    {
        $fieldName = 'highlightLowInventory';
        $entityFieldFallbackValueId = 99999999;

        $this->assertNull(
            $this->repo->findEntityId(Product::class, $fieldName, $entityFieldFallbackValueId)
        );
    }
}
