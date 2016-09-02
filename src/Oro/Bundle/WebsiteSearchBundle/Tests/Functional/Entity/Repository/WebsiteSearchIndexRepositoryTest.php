<?php

namespace Oro\Bundle\WebsiteSearchBundle\Tests\Functional\Entity\Repository;

use Oro\Bundle\TestFrameworkBundle\Entity\Product;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;
use Oro\Bundle\WebsiteSearchBundle\Entity\IndexDatetime;
use Oro\Bundle\WebsiteSearchBundle\Entity\IndexDecimal;
use Oro\Bundle\WebsiteSearchBundle\Entity\IndexInteger;
use Oro\Bundle\WebsiteSearchBundle\Entity\IndexText;
use Oro\Bundle\WebsiteSearchBundle\Entity\Item;
use Oro\Bundle\WebsiteSearchBundle\Tests\Functional\DataFixtures\LoadItemData;
use Oro\Bundle\WebsiteSearchBundle\Tests\Functional\SearchTestTrait;

/**
 * @dbIsolationPerTest
 */
class WebsiteSearchIndexRepositoryTest extends WebTestCase
{
    use SearchTestTrait;

    protected function setUp()
    {
        $this->initClient();
        $this->loadFixtures([LoadItemData::class]);
    }

    protected function tearDown()
    {
        $this->truncateIndexTextTable();
    }

    public function testLoadedData()
    {
        $this->assertEntityCount(5, Item::class);
        $this->assertEntityCount(1, IndexInteger::class);
        $this->assertEntityCount(2, IndexText::class);
        $this->assertEntityCount(1, IndexDatetime::class);
        $this->assertEntityCount(1, IndexDecimal::class);
    }

    public function testRemoveEntitiesWhenEmptyIdsArrayGiven()
    {
        $this->getItemRepository()->removeEntities([], Product::class);

        $this->assertEntityCount(5, Item::class);
        $this->assertEntityCount(1, IndexInteger::class);
        $this->assertEntityCount(2, IndexText::class);
        $this->assertEntityCount(1, IndexDatetime::class);
        $this->assertEntityCount(1, IndexDecimal::class);
    }

    public function testRemoveEntitiesForSpecificWebsite()
    {
        $this->getItemRepository()->removeEntities([1, 2], Product::class, 'orob2b_product_website_2');

        $this->assertEntityCount(3, Item::class);
        $this->assertEntityCount(1, IndexText::class);
        $this->assertEntityCount(0, IndexDecimal::class);
    }

    public function testRemoveEntitiesForAllWebsites()
    {
        $this->getItemRepository()->removeEntities([1, 2], Product::class);

        $this->assertEntityCount(1, Item::class);
        $this->assertEntityCount(1, IndexInteger::class);
        $this->assertEntityCount(0, IndexText::class);
        $this->assertEntityCount(0, IndexDecimal::class);
        $this->assertEntityCount(0, IndexDatetime::class);
    }

    public function testRemoveEntitiesForNonExistentEntities()
    {
        $this->getItemRepository()->removeEntities([91, 92], 'SomeClass');

        $this->assertEntityCount(5, Item::class);
    }
}
