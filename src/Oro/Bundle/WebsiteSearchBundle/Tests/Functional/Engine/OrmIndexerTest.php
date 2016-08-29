<?php

namespace Oro\Bundle\WebsiteSearchBundle\Tests\Unit\Engine;

use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;
use Oro\Bundle\WebsiteSearchBundle\Engine\OrmIndexer;
use Oro\Bundle\WebsiteSearchBundle\Entity\Item;
use Oro\Bundle\WebsiteSearchBundle\Entity\Repository\WebsiteSearchIndexRepository;
use Oro\Bundle\WebsiteSearchBundle\Tests\Functional\DataFixtures\LoadItemData;

/**
 * @dbIsolationPerTest
 */
class OrmIndexerTest extends WebTestCase
{
    /**
     * {@inheritdoc}
     */
    protected function setUp()
    {
        $this->initClient();
        $this->loadFixtures([LoadItemData::class]);
    }

    public function testCount()
    {
        $this->assertEquals(5, $this->getItemRepository()->getCount());
    }

    public function testRemoveEntitiesWhenNonExistentEntityRemoved()
    {
        $this->getIndexer()->delete([$this->getProductEntity(123456)], ['website_id' => 1]);

        $this->assertEquals(5, $this->getItemRepository()->getCount());
    }

    public function testRemoveEntitiesWhenEntityIdsArrayIsEmpty()
    {
        $this->getIndexer()->delete([], ['website_id' => 1]);

        $this->assertEquals(5, $this->getItemRepository()->getCount());
    }

    public function testRemoveEntitiesWhenProductEntitiesForSpecificWebsiteRemoved()
    {
        $this->getIndexer()->delete(
            [
                $this->getProductEntity(1),
                $this->getProductEntity(2)
            ],
            ['website_id' => 1]
        );

        $this->assertEquals(3, $this->getItemRepository()->getCount());
    }

    public function testRemoveEntitiesWhenProductEntitiesForAllWebsitesRemoved()
    {
        $this->getIndexer()->delete(
            [
                $this->getProductEntity(1),
                $this->getProductEntity(2)
            ],
            []
        );

        $this->assertEquals(1, $this->getItemRepository()->getCount());
    }

    /**
     * @param int $id
     * @return Product
     */
    private function getProductEntity($id)
    {
        $em = $this->getContainer()->get('oro_entity.doctrine_helper')->getEntityManagerForClass(Product::class);
        return $em->getReference(Product::class, $id);
    }

    /**
     * @return WebsiteSearchIndexRepository
     */
    private function getItemRepository()
    {
        return $this->getContainer()->get('doctrine')->getRepository(Item::class);
    }

    /**
     * @return OrmIndexer
     */
    private function getIndexer()
    {
        return $this->getContainer()->get('oro_website_search.engine.orm_indexer');
    }
}
