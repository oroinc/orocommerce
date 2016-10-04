<?php

namespace Oro\Bundle\WebsiteSearchBundle\Tests\Functional\Engine\ORM\Driver;

use Oro\Bundle\SearchBundle\Query\Criteria\Criteria;
use Oro\Bundle\SearchBundle\Query\Query;
use Oro\Bundle\WebsiteSearchBundle\Entity\Item;
use Oro\Bundle\WebsiteSearchBundle\Tests\Functional\AbstractSearchWebTestCase;
use Oro\Bundle\WebsiteSearchBundle\Tests\Functional\DataFixtures\LoadItemData;

/**
 * @dbIsolationPerTest
 */
class DriverDecoratorTest extends AbstractSearchWebTestCase
{
    protected function setUp()
    {
        parent::setUp();

        if (!$this->getContainer()->hasParameter('oro_website_search.engine') ||
            $this->getContainer()->getParameter('oro_website_search.engine') !== 'orm'
        ) {
            $this->markTestSkipped('Should be tested only with ORM search engine');
        }

        $this->loadFixtures([LoadItemData::class]);
    }

    public function testSearchDefaultWebsite()
    {
        $websiteId = $this->getDefaultWebsiteId();

        $query = new Query();
        $query->from('oro_product_'.$websiteId);
        $query->getCriteria()->orderBy(['id' => Query::ORDER_ASC]);

        /** @var Item $goodProductReference */
        $goodProductReference = LoadItemData::getSearchReferenceRepository()->getReference(
            LoadItemData::getReferenceName(LoadItemData::REFERENCE_GOOD_PRODUCT, $websiteId)
        );

        /** @var Item $betterProductReference */
        $betterProductReference = LoadItemData::getSearchReferenceRepository()->getReference(
            LoadItemData::getReferenceName(LoadItemData::REFERENCE_BETTER_PRODUCT, $websiteId)
        );

        $expectedProducts = [
            [
                'item' => $this->convertItemToArray($goodProductReference),
                'value' => null,
            ],
            [
                'item' => $this->convertItemToArray($betterProductReference),
                'value' => null,
            ],
        ];

        $this->assertEquals(
            $expectedProducts,
            $this->getContainer()->get('oro_website_search.engine.orm.driver')->search($query)
        );
    }

    /**
     * @param Item $item
     * @return array
     */
    private function convertItemToArray(Item $item)
    {
        return [
            'id' => $item->getId(),
            'entity' => $item->getEntity(),
            'alias' => $item->getAlias(),
            'recordId' => $item->getRecordId(),
            'title' => $item->getTitle(),
            'changed' => $item->getChanged(),
            'createdAt' => $item->getCreatedAt(),
            'updatedAt' => $item->getUpdatedAt(),
        ];
    }

    public function testSearchDefaultWebsiteWithContains()
    {
        $websiteId = $this->getDefaultWebsiteId();

        $query = new Query();
        $query->from('oro_product_'.$websiteId);
        $query->getCriteria()->andWhere(Criteria::expr()->contains('long_description', 'Long description'));

        $referenceName = LoadItemData::getReferenceName(LoadItemData::REFERENCE_GOOD_PRODUCT, $websiteId);
        /** @var Item $item */
        $item = LoadItemData::getSearchReferenceRepository()->getReference($referenceName);
        $expectedItem = $this->convertItemToArray($item);

        $itemResults = $this->getContainer()->get('oro_website_search.engine.orm.driver')->search($query);
        $itemResult = reset($itemResults);

        $this->assertCount(1, $itemResults);
        $this->assertEquals($expectedItem, $itemResult['item']);
    }

    public function testSearchDefaultWebsiteWithEq()
    {
        $websiteId = $this->getDefaultWebsiteId();

        $referenceName = LoadItemData::getReferenceName(LoadItemData::REFERENCE_BETTER_PRODUCT, $websiteId);
        /** @var Item $item */
        $item = LoadItemData::getSearchReferenceRepository()->getReference($referenceName);
        $expectedItem = $this->convertItemToArray($item);

        $query = new Query();
        $query->from('oro_product_'.$websiteId);
        $query->getCriteria()->andWhere(Criteria::expr()->eq('integer.lucky_number', 777));

        $itemResults = $this->getContainer()->get('oro_website_search.engine.orm.driver')->search($query);
        $itemResult = reset($itemResults);

        $this->assertCount(1, $itemResults);
        $this->assertEquals($expectedItem, $itemResult['item']);
    }
}
