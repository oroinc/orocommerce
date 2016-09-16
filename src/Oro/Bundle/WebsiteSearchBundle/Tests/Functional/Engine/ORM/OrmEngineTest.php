<?php

namespace Oro\Bundle\WebsiteSearchBundle\Tests\Functional\Engine\ORM;

use Oro\Bundle\SearchBundle\Query\Criteria\Comparison;
use Oro\Bundle\SearchBundle\Query\Criteria\Criteria;
use Oro\Bundle\SearchBundle\Query\Query;
use Oro\Bundle\SearchBundle\Query\Result;
use Oro\Bundle\SearchBundle\Tests\Functional\Controller\DataFixtures\LoadSearchItemData;
use Oro\Bundle\TestFrameworkBundle\Entity\Item as TestEntity;
use Oro\Bundle\WebsiteSearchBundle\Engine\OrmIndexer;
use Oro\Bundle\WebsiteSearchBundle\Engine\ORM\OrmEngine;
use Oro\Bundle\WebsiteSearchBundle\Event\IndexEntityEvent;
use Oro\Bundle\WebsiteSearchBundle\Provider\WebsiteSearchMappingProvider;
use Oro\Bundle\WebsiteSearchBundle\Tests\Functional\AbstractSearchWebTestCase;

/**
 * @dbIsolationPerTest
 */
class OrmEngineTest extends AbstractSearchWebTestCase
{
    /** @var WebsiteSearchMappingProvider|\PHPUnit_Framework_MockObject_MockObject */
    protected $mappingProviderMock;

    /**
     * @var callable
     */
    protected $listener;

    /**
     * @var OrmEngine
     */
    protected $ormEngine;

    /**
     * @var array
     */
    protected $mappingConfig = [
        TestEntity::class => [
            'alias' => 'oro_test_item_WEBSITE_ID',
            'fields' => [
                [
                    'name' => 'stringValue_LOCALIZATION_ID',
                    'type' => 'text',
                ],
                [
                    'name' => 'integerValue',
                    'type' => 'integer',
                ],
                [
                    'name' => 'decimalValue',
                    'type' => 'decimal',
                ],
                [
                    'name' => 'floatValue',
                    'type' => 'decimal',
                ],
                [
                    'name' => 'datetimeValue',
                    'type' => 'datetime',
                ],
                [
                    'name' => 'blobValue',
                    'type' => 'text',
                ],
                [
                    'name' => 'phone',
                    'type' => 'text',
                ],
            ],
        ]
    ];

    protected function setUp()
    {
        $this->initClient();

        if ($this->getContainer()->getParameter('oro_website_search.engine') !== 'orm') {
            $this->markTestSkipped('Should be tested only with ORM search engine');
        }

        $this->mappingProviderMock = $this->getMockBuilder(WebsiteSearchMappingProvider::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->mappingProviderMock
            ->expects($this->once())
            ->method('isClassSupported')
            ->willReturn(true);

        $this->mappingProviderMock
            ->expects($this->once())
            ->method('getEntityAlias')
            ->with(TestEntity::class)
            ->willReturn($this->mappingConfig[TestEntity::class]['alias']);

        $this->addFrontendRequest();

        $this->loadFixtures([LoadSearchItemData::class]);

        $this->listener = $this->setListener();

        $indexer = new OrmIndexer(
            $this->getContainer()->get('event_dispatcher'),
            $this->getContainer()->get('oro_entity.doctrine_helper'),
            $this->mappingProviderMock,
            $this->getContainer()->get('oro_entity.entity_alias_resolver')
        );

        $indexer->reindex(TestEntity::class, []);

        $this->ormEngine = $this->getContainer()->get('oro_website_search.engine');
        $this->ormEngine->setMappingProvider($this->mappingProviderMock);
    }

    protected function tearDown()
    {
        $this->clearIndexTextTable();

        unset($this->listener, $this->ormEngine, $this->expectedSearchItems);
    }

    /**
     * @param Query $query
     * @return Result\Item[]
     */
    private function getSearchItems(Query $query)
    {
        $searchResults = $this->ormEngine->search($query);
        return $searchResults->getElements();
    }

    /**
     * @return callable
     */
    private function setListener()
    {
        $listener = function (IndexEntityEvent $event) {
            $defaultLocalizationId = $this->getDefaultLocalizationId();

            $items = $this->getContainer()->get('doctrine')
                ->getRepository(TestEntity::class)
                ->findBy(['id' => $event->getEntityIds()]);

            /** @var TestEntity $item */
            foreach ($items as $item) {
                $event->addField(
                    $item->getId(),
                    Query::TYPE_INTEGER,
                    'integerValue',
                    $item->integerValue
                );
                $event->addField(
                    $item->getId(),
                    Query::TYPE_DECIMAL,
                    'decimalValue',
                    $item->decimalValue
                );
                $event->addField(
                    $item->getId(),
                    Query::TYPE_DECIMAL,
                    'floatValue',
                    $item->floatValue
                );
                $event->addField(
                    $item->getId(),
                    Query::TYPE_DATETIME,
                    'datetimeValue',
                    $item->datetimeValue
                );
                $event->addField(
                    $item->getId(),
                    Query::TYPE_TEXT,
                    'stringValue_' . $defaultLocalizationId,
                    $item->stringValue
                );
                $event->addField(
                    $item->getId(),
                    Query::TYPE_TEXT,
                    'all_text_' . $defaultLocalizationId,
                    $item->stringValue
                );
                $event->addField(
                    $item->getId(),
                    Query::TYPE_TEXT,
                    'phone',
                    $item->phone
                );
                $event->addField(
                    $item->getId(),
                    Query::TYPE_TEXT,
                    'blobValue',
                    (string)$item->blobValue
                );
            }
        };

        $this->getContainer()->get('event_dispatcher')->addListener(
            IndexEntityEvent::NAME,
            $listener,
            -255
        );

        return $listener;
    }

    public function testSearchAll()
    {
        $defaultLocalizationId = $this->getDefaultLocalizationId();

        $this->mappingProviderMock
            ->expects($this->exactly(9))
            ->method('getEntityConfig')
            ->with(TestEntity::class)
            ->willReturn($this->mappingConfig[TestEntity::class]);

        $query = new Query();
        $query->from('*');
        $query->getCriteria()->orderBy([
            'stringValue_' . $defaultLocalizationId => Query::ORDER_ASC
        ]);

        $items = $this->getSearchItems($query);

        $this->assertEquals('item1@mail.com', $items[0]->getRecordTitle());
        $this->assertEquals('item2@mail.com', $items[1]->getRecordTitle());
        $this->assertEquals('item3@mail.com', $items[2]->getRecordTitle());
        $this->assertEquals('item4@mail.com', $items[3]->getRecordTitle());
        $this->assertEquals('item5@mail.com', $items[4]->getRecordTitle());
        $this->assertEquals('item6@mail.com', $items[5]->getRecordTitle());
        $this->assertEquals('item7@mail.com', $items[6]->getRecordTitle());
        $this->assertEquals('item8@mail.com', $items[7]->getRecordTitle());
        $this->assertEquals('item9@mail.com', $items[8]->getRecordTitle());
    }

    public function testSearchByAliasWithSelect()
    {
        $defaultLocalizationId = $this->getDefaultLocalizationId();

        $this->mappingProviderMock
            ->expects($this->exactly(9))
            ->method('getEntityConfig')
            ->with(TestEntity::class)
            ->willReturn($this->mappingConfig[TestEntity::class]);

        $query = new Query();
        $query->from('oro_test_item_WEBSITE_ID');
        $query->select('stringValue_' . $defaultLocalizationId);
        $query->getCriteria()->orderBy([
            'stringValue_' . $defaultLocalizationId => Query::ORDER_ASC
        ]);

        $items = $this->getSearchItems($query);

        $this->assertCount(9, $items);
        $this->assertEquals('item1@mail.com', $items[0]->getRecordTitle());
        $this->assertEquals('item2@mail.com', $items[1]->getRecordTitle());
        $this->assertEquals('item3@mail.com', $items[2]->getRecordTitle());
        $this->assertEquals('item4@mail.com', $items[3]->getRecordTitle());
        $this->assertEquals('item5@mail.com', $items[4]->getRecordTitle());
        $this->assertEquals('item6@mail.com', $items[5]->getRecordTitle());
        $this->assertEquals('item7@mail.com', $items[6]->getRecordTitle());
        $this->assertEquals('item8@mail.com', $items[7]->getRecordTitle());
        $this->assertEquals('item9@mail.com', $items[8]->getRecordTitle());
    }

    public function testSearchByAliasWithCriteria()
    {
        $query = new Query();
        $query->from('oro_test_item_WEBSITE_ID');

        $this->mappingProviderMock
            ->expects($this->once())
            ->method('getEntityConfig')
            ->with(TestEntity::class)
            ->willReturn($this->mappingConfig[TestEntity::class]);

        $expr = new Comparison("integer.integerValue", "=", 5000);
        $criteria = new Criteria();
        $criteria->where($expr);
        $query->setCriteria($criteria);

        $items = $this->getSearchItems($query);

        $this->assertCount(1, $items);
        $this->assertEquals('item5@mail.com', $items[0]->getRecordTitle());
    }
}
