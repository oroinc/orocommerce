<?php

namespace Oro\Bundle\WebsiteSearchBundle\Tests\Functional\Engine;

use Oro\Bundle\SearchBundle\Engine\IndexerInterface;
use Oro\Bundle\SearchBundle\Query\Criteria\Comparison;
use Oro\Bundle\SearchBundle\Query\Criteria\Criteria;
use Oro\Bundle\SearchBundle\Query\Query;
use Oro\Bundle\SearchBundle\Query\Result;
use Oro\Bundle\SearchBundle\Query\Result\Item;
use Oro\Bundle\SearchBundle\Tests\Functional\Controller\DataFixtures\LoadSearchItemData;
use Oro\Bundle\SearchBundle\Tests\Functional\SearchExtensionTrait;
use Oro\Bundle\TestFrameworkBundle\Entity\Item as TestEntity;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;
use Oro\Bundle\WebsiteSearchBundle\Engine\AbstractEngine;
use Oro\Bundle\WebsiteSearchBundle\Entity\IndexText;
use Oro\Bundle\WebsiteSearchBundle\Event\IndexEntityEvent;
use Oro\Bundle\WebsiteSearchBundle\Placeholder\LocalizationIdPlaceholder;
use Oro\Bundle\WebsiteSearchBundle\Tests\Functional\Traits\DefaultLocalizationIdTestTrait;
use Symfony\Component\HttpFoundation\Request;

abstract class AbstractEngineTest extends WebTestCase
{
    use DefaultLocalizationIdTestTrait;
    use SearchExtensionTrait;

    /** @var callable */
    protected $listener;

    /** @var AbstractEngine */
    protected $engine;

    protected function setUp(): void
    {
        $this->getContainer()->get('request_stack')->push(Request::create(''));

        $this->loadFixtures([LoadSearchItemData::class]);

        $this->listener = $this->getIndexEntityListener();
        $this->getContainer()->get('event_dispatcher')->addListener(
            IndexEntityEvent::NAME,
            $this->listener,
            -255
        );

        $this->engine = $this->getSearchEngine();
    }

    protected function tearDown(): void
    {
        $this->getContainer()->get('event_dispatcher')->removeListener(IndexEntityEvent::NAME, $this->listener);

        $this->clearIndexTextTable(IndexText::class);
    }

    /**
     * @return Result\Item[]
     */
    protected function getSearchItems(Query $query): array
    {
        return $this->engine->search($query)->getElements();
    }

    protected function getIndexEntityListener(): callable
    {
        return function (IndexEntityEvent $event) {
            $defaultLocalizationId = $this->getDefaultLocalizationId();

            $items = $this->getContainer()->get('doctrine')
                ->getRepository(TestEntity::class)
                ->findBy(['id' => $event->getEntities()]);

            /** @var TestEntity $item */
            foreach ($items as $item) {
                $event->addField($item->getId(), 'stringValue', $item->stringValue);
                $event->addField($item->getId(), 'integerValue', $item->integerValue);
                $event->addField($item->getId(), 'decimalValue', $item->decimalValue);
                $event->addField($item->getId(), 'floatValue', $item->floatValue);
                $event->addField($item->getId(), 'datetimeValue', $item->datetimeValue);
                $event->addField($item->getId(), 'phone', $item->phone);
                $event->addField($item->getId(), 'blobValue', (string)$item->blobValue);

                $event->addPlaceholderField(
                    $item->getId(),
                    'title_LOCALIZATION_ID',
                    "Some text with placeholder {$defaultLocalizationId} for {$item->stringValue}",
                    [LocalizationIdPlaceholder::NAME => $defaultLocalizationId]
                );

                // decimal value as search relevance weight reverses the order
                $event->addField($item->getId(), IndexerInterface::WEIGHT_FIELD, $item->decimalValue);
            }
        };
    }

    public function testSearchAll()
    {
        $query = new Query();
        $query->from('*');
        $query->getCriteria()->andWhere(new Comparison('text.stringValue', '~', 'item'));
        $items = $this->getSearchItems($query);

        $this->assertCount(LoadSearchItemData::COUNT, $items);

        // reverse order is a consequence of custom search relevance weight
        $this->assertEquals($this->getReference('item_9')->getId(), $items[0]->getRecordId());
        $this->assertEquals($this->getReference('item_8')->getId(), $items[1]->getRecordId());
        $this->assertEquals($this->getReference('item_7')->getId(), $items[2]->getRecordId());
        $this->assertEquals($this->getReference('item_6')->getId(), $items[3]->getRecordId());
        $this->assertEquals($this->getReference('item_5')->getId(), $items[4]->getRecordId());
        $this->assertEquals($this->getReference('item_4')->getId(), $items[5]->getRecordId());
        $this->assertEquals($this->getReference('item_3')->getId(), $items[6]->getRecordId());
        $this->assertEquals($this->getReference('item_2')->getId(), $items[7]->getRecordId());
        $this->assertEquals($this->getReference('item_1')->getId(), $items[8]->getRecordId());
    }

    public function testSearchByAliasWithSelect()
    {
        $searchField = 'stringValue';

        $query = new Query();
        $query->from('oro_test_item_WEBSITE_ID');
        $query->select($searchField);
        $query->getCriteria()->orderBy(['stringValue' => Query::ORDER_ASC]);

        $items = $this->getSearchItems($query);

        $this->assertCount(LoadSearchItemData::COUNT, $items);
        $this->assertSame('item1@mail.com', $this->getSelectData($searchField, $items[0]));
        $this->assertSame('item2@mail.com', $this->getSelectData($searchField, $items[1]));
        $this->assertSame('item3@mail.com', $this->getSelectData($searchField, $items[2]));
        $this->assertSame('item4@mail.com', $this->getSelectData($searchField, $items[3]));
        $this->assertSame('item5@mail.com', $this->getSelectData($searchField, $items[4]));
        $this->assertSame('item6@mail.com', $this->getSelectData($searchField, $items[5]));
        $this->assertSame('item7@mail.com', $this->getSelectData($searchField, $items[6]));
        $this->assertSame('item8@mail.com', $this->getSelectData($searchField, $items[7]));
        $this->assertSame('item9@mail.com', $this->getSelectData($searchField, $items[8]));
    }

    public function testSearchByAliasWithCriteria()
    {
        $expr = new Comparison('integer.integerValue', '=', 5000);
        $criteria = new Criteria($expr);
        $searchField = 'stringValue';

        $query = new Query();
        $query->select($searchField)
            ->from('oro_test_item_WEBSITE_ID')
            ->setCriteria($criteria);

        $items = $this->getSearchItems($query);

        $this->assertCount(1, $items);
        $this->assertSame('item5@mail.com', $this->getSelectData($searchField, $items[0]));
    }

    /**
     * @dataProvider aggregationDataProvider
     */
    public function testAggregateWithCriteria(string $function, array $parameters, mixed $expected)
    {
        $expr = new Comparison('integer.integerValue', '>', 5000);
        $criteria = new Criteria($expr);

        $field = 'aggregated_value';

        $query = new Query();
        $query->from('oro_test_item_WEBSITE_ID')
            ->addAggregate($field, 'integer.integerValue', $function, $parameters)
            ->setCriteria($criteria);

        $results = $this->engine->search($query);
        $aggregatedData = $results->getAggregatedData();

        $this->assertCount(1, $aggregatedData);
        $this->assertArrayHasKey($field, $aggregatedData);
        $this->assertSame($expected, $aggregatedData[$field]);
    }

    public function aggregationDataProvider(): array
    {
        return [
            'count without parameters' => [
                'function' => Query::AGGREGATE_FUNCTION_COUNT,
                'parameters' => [],
                'expected' => [
                    6000 => 1,
                    7000 => 1,
                    8000 => 1,
                    9000 => 1,
                ]
            ],
            'count with max parameter' => [
                'function' => Query::AGGREGATE_FUNCTION_COUNT,
                'parameters' => ['max' => 2],
                'expected' => [
                    6000 => 1,
                    7000 => 1,
                ]
            ],
            'sum' => [
                'function' => Query::AGGREGATE_FUNCTION_SUM,
                'parameters' => [],
                'expected' => 30000.0
            ],
            'min' => [
                'function' => Query::AGGREGATE_FUNCTION_MIN,
                'parameters' => [],
                'expected' => 6000.0
            ],
            'max' => [
                'function' => Query::AGGREGATE_FUNCTION_MAX,
                'parameters' => [],
                'expected' => 9000.0
            ],
            'avg' => [
                'function' => Query::AGGREGATE_FUNCTION_AVG,
                'parameters' => [],
                'expected' => 7500.0
            ],
        ];
    }

    protected function getSelectData(string $field, Item $item): mixed
    {
        $selectedData = $item->getSelectedData();

        if (!array_key_exists($field, $selectedData)) {
            throw new \RuntimeException(sprintf('Field "%s" not found in selectedData', $field));
        }

        return $selectedData[$field];
    }

    abstract protected function getSearchEngine(): AbstractEngine;
}
