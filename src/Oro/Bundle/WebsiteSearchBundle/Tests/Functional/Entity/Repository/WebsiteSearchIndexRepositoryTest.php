<?php

namespace Oro\Bundle\WebsiteSearchBundle\Tests\Functional\Entity\Repository;

use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;
use Oro\Bundle\WebsiteSearchBundle\Entity\Item;
use Oro\Bundle\WebsiteSearchBundle\Entity\Repository\WebsiteSearchIndexRepository;
use Oro\Bundle\WebsiteSearchBundle\Tests\Functional\DataFixtures\LoadItemData;

/**
 * @dbIsolation
 */
class WebsiteSearchIndexRepositoryTest extends WebTestCase
{
    protected function setUp()
    {
        $this->initClient();
        $this->loadFixtures([LoadItemData::class]);
    }

    public function testCount()
    {
        $this->assertEquals(5, $this->getRepository()->getCount());
    }

    public function testGetEntitiesWhenEmptyArrayGiven()
    {
        $this->assertEquals([], $this->getRepository()->getEntitiesToRemove([], Product::class));
    }

    public function testGetEntitiesForSpeciticWebsite()
    {
        $this->assertItemsEqual(
            [
                $this->getReference(LoadItemData::REFERENCE_OTHER_GOOD_PRODUCT),
                $this->getReference(LoadItemData::REFERENCE_OTHER_BETTER_PRODUCT)
            ],
            $this->getRepository()->getEntitiesToRemove([1, 2], Product::class, 'orob2b_product_2')
        );
    }

    public function testGetEntitiesForAllWebsites()
    {
        $this->assertItemsEqual(
            [
                $this->getReference(LoadItemData::REFERENCE_GOOD_PRODUCT),
                $this->getReference(LoadItemData::REFERENCE_BETTER_PRODUCT),
                $this->getReference(LoadItemData::REFERENCE_OTHER_GOOD_PRODUCT),
                $this->getReference(LoadItemData::REFERENCE_OTHER_BETTER_PRODUCT)
            ],
            $this->getRepository()->getEntitiesToRemove([1, 2], Product::class)
        );
    }

    public function testGetNotExistentEntities()
    {
        $this->assertItemsEqual([], $this->getRepository()->getEntitiesToRemove([91, 92], 'SomeClass'));
    }

    /**
     * @param Item[] $expectedItems
     * @param Item[] $actualItems
     */
    private function assertItemsEqual($expectedItems, $actualItems)
    {
        $expectedIds = [];
        foreach ($expectedItems as $item) {
            $expectedIds[] = $item->getId();
        }

        $actualIds = [];
        foreach ($actualItems as $item) {
            $actualIds[] = $item->getId();
        }

        $this->assertEquals($expectedIds, $actualIds);
    }

    /**
     * @return WebsiteSearchIndexRepository
     */
    private function getRepository()
    {
        return $this->getContainer()->get('doctrine')->getRepository(
            'Oro\Bundle\WebsiteSearchBundle\Entity\Item'
        );
    }
}
