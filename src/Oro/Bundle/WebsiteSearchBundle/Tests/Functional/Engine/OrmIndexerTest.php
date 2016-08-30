<?php

namespace Oro\Bundle\WebsiteSearchBundle\Tests\Unit\Engine;

use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\WebsiteSearchBundle\Engine\OrmIndexer;
use Oro\Bundle\WebsiteSearchBundle\Entity\Item;
use Oro\Bundle\WebsiteSearchBundle\Entity\IndexDatetime;
use Oro\Bundle\WebsiteSearchBundle\Entity\IndexDecimal;
use Oro\Bundle\WebsiteSearchBundle\Entity\IndexInteger;
use Oro\Bundle\WebsiteSearchBundle\Entity\IndexText;
use Oro\Bundle\WebsiteSearchBundle\Tests\Functional\DataFixtures\LoadItemData;
use Oro\Bundle\WebsiteSearchBundle\Tests\Functional\SearchTestTrait;
use Oro\Bundle\WebsiteSearchBundle\Tests\Functional\WebTestCase;

/**
 * @dbIsolationPerTest
 */
class OrmIndexerTest extends WebTestCase
{
    use SearchTestTrait;

    protected function setUp()
    {
        $this->initClient();
        $this->loadFixtures([LoadItemData::class], false, 'search');
    }

    protected function tearDown()
    {
        $this->truncateIndexTextTable();
    }

    public function testCount()
    {
        $this->assertEntityCount(5, Item::class);
        $this->assertEntityCount(1, IndexInteger::class);
        $this->assertEntityCount(2, IndexText::class);
        $this->assertEntityCount(1, IndexDatetime::class);
        $this->assertEntityCount(1, IndexDecimal::class);
    }

    public function testRemoveEntitiesWhenNonExistentEntityRemoved()
    {
        $this->getIndexer()->delete([$this->getProductEntity(123456)], ['website_id' => 1]);

        $this->assertEntityCount(5, Item::class);
        $this->assertEntityCount(1, IndexInteger::class);
        $this->assertEntityCount(2, IndexText::class);
        $this->assertEntityCount(1, IndexDatetime::class);
        $this->assertEntityCount(1, IndexDecimal::class);
    }

    public function testRemoveEntitiesWhenEntityIdsArrayIsEmpty()
    {
        $this->getIndexer()->delete([], ['website_id' => 1]);

        $this->assertEntityCount(5, Item::class);
        $this->assertEntityCount(1, IndexInteger::class);
        $this->assertEntityCount(2, IndexText::class);
        $this->assertEntityCount(1, IndexDatetime::class);
        $this->assertEntityCount(1, IndexDecimal::class);
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

        $this->assertEntityCount(3, Item::class);
        $this->assertEntityCount(1, IndexInteger::class);
        $this->assertEntityCount(1, IndexText::class);
        $this->assertEntityCount(0, IndexDatetime::class);
        $this->assertEntityCount(1, IndexDecimal::class);
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

        $this->assertEntityCount(1, Item::class);
        $this->assertEntityCount(1, IndexInteger::class);
        $this->assertEntityCount(0, IndexText::class);
        $this->assertEntityCount(0, IndexDatetime::class);
        $this->assertEntityCount(0, IndexDecimal::class);
    }

    /**
     * @param int $id
     * @return Product
     */
    private function getProductEntity($id)
    {
        return $this->getContainer()
            ->get('doctrine.orm.default_entity_manager')
            ->getReference(Product::class, $id);
    }

    /**
     * @return OrmIndexer
     */
    private function getIndexer()
    {
        return $this->getContainer()->get('oro_website_search.engine.orm_indexer');
    }
}
