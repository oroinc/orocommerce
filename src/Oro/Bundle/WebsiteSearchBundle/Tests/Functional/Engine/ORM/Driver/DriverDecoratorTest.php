<?php

namespace Oro\Bundle\WebsiteSearchBundle\Tests\Functional\Engine\ORM\Driver;

use Oro\Bundle\SearchBundle\Engine\Orm\PdoMysql\MysqlVersionCheckTrait;
use Oro\Bundle\SearchBundle\Query\Criteria\Criteria;
use Oro\Bundle\SearchBundle\Query\Query;
use Oro\Bundle\SearchBundle\Tests\Functional\SearchExtensionTrait;
use Oro\Bundle\TestFrameworkBundle\Test\DateTime\TrimMicrosecondsTrait;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;
use Oro\Bundle\WebsiteSearchBundle\Entity\IndexText;
use Oro\Bundle\WebsiteSearchBundle\Entity\Item;
use Oro\Bundle\WebsiteSearchBundle\Tests\Functional\DataFixtures\LoadItemData;
use Oro\Bundle\WebsiteSearchBundle\Tests\Functional\Traits\DefaultWebsiteIdTestTrait;

/**
 * @dbIsolationPerTest
 */
class DriverDecoratorTest extends WebTestCase
{
    use DefaultWebsiteIdTestTrait;
    use TrimMicrosecondsTrait;
    use SearchExtensionTrait;
    use MysqlVersionCheckTrait;

    protected function setUp(): void
    {
        $this->initClient();

        $engine = $this->getContainer()
            ->get('oro_website_search.engine.parameters')
            ->getEngineName();
        if ($engine !== 'orm') {
            $this->markTestSkipped('Should be tested only with ORM search engine');
        }

        $this->platform = $this->getContainer()->get('doctrine')->getManager()->getConnection()->getDatabasePlatform();

        $this->loadFixtures([LoadItemData::class]);
    }

    protected function tearDown(): void
    {
        $this->clearIndexTextTable(IndexText::class);
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
                'item' => $this->convertItemToArray($betterProductReference),
                'value' => null,
            ],
            [
                'item' => $this->convertItemToArray($goodProductReference),
                'value' => null,
            ],
        ];

        $this->assertEquals(
            $expectedProducts,
            $this->getContainer()->get('oro_website_search.engine.orm.driver')->search($query)
        );
    }

    private function convertItemToArray(Item $item): array
    {
        return [
            'id' => $item->getId(),
            'entity' => $item->getEntity(),
            'alias' => $item->getAlias(),
            'recordId' => $item->getRecordId(),
            'changed' => $item->getChanged(),
            'weight' => $item->getWeight(),
            'createdAt' => $this->trimMicrosecondsFromDateTimeObject($item->getCreatedAt()),
            'updatedAt' => $this->trimMicrosecondsFromDateTimeObject($item->getUpdatedAt()),
        ];
    }

    public function testSearchDefaultWebsiteWithContains()
    {
        if ($this->isMysqlPlatform() && $this->isInnoDBFulltextIndexSupported()) {
            $this->markTestSkipped(
                'Skipped because current test implementation isn\'t compatible with InnoDB Full-Text index'
            );
        }

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

    /**
     * @dataProvider aggregationDataProvider
     */
    public function testSearchDefaultWebsiteCountAggregate(string $function, array $parameters, mixed $expected)
    {
        $field = 'test_value';
        $websiteId = $this->getDefaultWebsiteId();

        $query = new Query();
        $query->from('oro_product_'.$websiteId);
        $query->addAggregate($field, 'integer.for_count', $function, $parameters);

        $results = $this->getContainer()->get('oro_website_search.engine.orm.driver')->getAggregatedData($query);

        $this->assertCount(1, $results);
        $this->assertArrayHasKey($field, $results);
        $this->assertEquals($expected, $results[$field]);
    }

    public function aggregationDataProvider(): array
    {
        return [
            'count without parameters' => [
                'function' => Query::AGGREGATE_FUNCTION_COUNT,
                'parameters' => [],
                'expected' => [100 => 1, 200 => 1]
            ],
            'count with max parameter' => [
                'function' => Query::AGGREGATE_FUNCTION_COUNT,
                'parameters' => ['max' => 1],
                'expected' => [100 => 1]
            ],
            'sum' => [
                'function' => Query::AGGREGATE_FUNCTION_SUM,
                'parameters' => [],
                'expected' => 300
            ],
            'min' => [
                'function' => Query::AGGREGATE_FUNCTION_MIN,
                'parameters' => [],
                'expected' => 100
            ],
            'max' => [
                'function' => Query::AGGREGATE_FUNCTION_MAX,
                'parameters' => [],
                'expected' => 200
            ],
            'avg' => [
                'function' => Query::AGGREGATE_FUNCTION_AVG,
                'parameters' => [],
                'expected' => 150
            ],
        ];
    }
}
