<?php

namespace Oro\Bundle\InventoryBundle\Tests\Functional\Entity\Repository;

use Oro\Bundle\EntityBundle\Entity\EntityFieldFallbackValue;
use Oro\Bundle\EntityBundle\Entity\EntityFieldFallbackValueRepository;
use Oro\Bundle\InventoryBundle\Tests\Functional\DataFixtures\LoadProductRelatedFallbackValuesData;
use Oro\Bundle\ProductBundle\Tests\Functional\DataFixtures\LoadProductData;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;

class EntityFieldFallbackValueRepositoryTest extends WebTestCase
{
    /** @var EntityFieldFallbackValueRepository */
    private $repo;

    public function setUp()
    {
        $this->initClient();
        $this->loadFixtures([
            LoadProductRelatedFallbackValuesData::class,
        ]);

        $this->repo = $this->client->getContainer()
            ->get('oro_entity.doctrine_helper')
            ->getEntityRepository(EntityFieldFallbackValue::class);
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

    /**
     * @expectedException \InvalidArgumentException
     */
    public function testGetIdsByEntityFieldsException()
    {
        $this->repo->findByEntityFields($this->getReference(LoadProductData::PRODUCT_1), [
            'inventoryThreshold',
            'some_absent_field',
            'highlightLowInventory',
        ]);
    }

    /**
     * @param string $fieldName
     * @return EntityFieldFallbackValue
     */
    private function getFallbackValue(string $fieldName): EntityFieldFallbackValue
    {
        return $this->getReference(LoadProductRelatedFallbackValuesData::getReferenceName(
            LoadProductData::PRODUCT_1,
            $fieldName
        ));
    }
}
