<?php

namespace Oro\Bundle\WebsiteSearchBundle\Tests\Functional\Engine\ORM;

use Oro\Bundle\SearchBundle\Query\Criteria\Comparison;
use Oro\Bundle\SearchBundle\Query\Criteria\Criteria;
use Oro\Bundle\SearchBundle\Query\Query;
use Oro\Bundle\SearchBundle\Query\Result;
use Oro\Bundle\SearchBundle\Tests\Functional\Controller\DataFixtures\LoadSearchItemData;
use Oro\Bundle\TestFrameworkBundle\Entity\Item as TestEntity;
use Oro\Bundle\WebsiteSearchBundle\Engine\ORM\OrmIndexer;
use Oro\Bundle\WebsiteSearchBundle\Engine\ORM\OrmEngine;
use Oro\Bundle\WebsiteSearchBundle\Event\IndexEntityEvent;
use Oro\Bundle\WebsiteSearchBundle\Placeholder\LocalizationIdPlaceholder;
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
                    'name' => 'title_LOCALIZATION_ID',
                    'type' => 'text'
                ],
                [
                    'name' => 'stringValue',
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
                    'type' => 'decimal'
                ],
                [
                    'name' => 'datetimeValue',
                    'type' => 'datetime'
                ],
                [
                    'name' => 'blobValue',
                    'type' => 'text'
                ],
                [
                    'name' => 'phone',
                    'type' => 'text'
                ]
            ],
        ]
    ];

    protected function setUp()
    {
        parent::setUp();

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

        $this->mappingProviderMock
            ->expects($this->any())
            ->method('getEntityConfig')
            ->with(TestEntity::class)
            ->willReturn($this->mappingConfig[TestEntity::class]);

        $this->loadFixtures([LoadSearchItemData::class]);

        $this->listener = $this->setListener();

        $driver = $this->getContainer()->get('oro_website_search.engine.orm.driver');

        $indexer = new OrmIndexer(
            $this->getContainer()->get('oro_entity.doctrine_helper'),
            $this->mappingProviderMock,
            $this->getContainer()->get('oro_website_search.engine.entity_dependencies_resolver'),
            $this->getContainer()->get('oro_website_search.engine.index_data'),
            $this->getContainer()->get('oro_website_search.placeholder_decorator')
        );
        $indexer->setDriver($driver);

        $indexer->reindex(TestEntity::class, []);

        $this->ormEngine = $this->getContainer()->get('oro_website_search.engine');
        $this->ormEngine->setDriver($driver);
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
        $query = new Query();
        $query->from('*');
        $query->getCriteria()->orderBy(['stringValue' => Query::ORDER_ASC]);
        $items = $this->getSearchItems($query);

        $this->assertStringStartsWith('item1@mail.com', $items[0]->getRecordTitle());
        $this->assertStringStartsWith('item2@mail.com', $items[1]->getRecordTitle());
        $this->assertStringStartsWith('item3@mail.com', $items[2]->getRecordTitle());
        $this->assertStringStartsWith('item4@mail.com', $items[3]->getRecordTitle());
        $this->assertStringStartsWith('item5@mail.com', $items[4]->getRecordTitle());
        $this->assertStringStartsWith('item6@mail.com', $items[5]->getRecordTitle());
        $this->assertStringStartsWith('item7@mail.com', $items[6]->getRecordTitle());
        $this->assertStringStartsWith('item8@mail.com', $items[7]->getRecordTitle());
        $this->assertStringStartsWith('item9@mail.com', $items[8]->getRecordTitle());
    }

    public function testSearchByAliasWithSelect()
    {
        $query = new Query();
        $query->from('oro_test_item_WEBSITE_ID');
        $query->select('stringValue');
        $query->getCriteria()->orderBy(['stringValue' => Query::ORDER_ASC]);

        $items = $this->getSearchItems($query);

        $this->assertCount(9, $items);
        $this->assertStringStartsWith('item1@mail.com', $items[0]->getRecordTitle());
        $this->assertStringStartsWith('item2@mail.com', $items[1]->getRecordTitle());
        $this->assertStringStartsWith('item3@mail.com', $items[2]->getRecordTitle());
        $this->assertStringStartsWith('item4@mail.com', $items[3]->getRecordTitle());
        $this->assertStringStartsWith('item5@mail.com', $items[4]->getRecordTitle());
        $this->assertStringStartsWith('item6@mail.com', $items[5]->getRecordTitle());
        $this->assertStringStartsWith('item7@mail.com', $items[6]->getRecordTitle());
        $this->assertStringStartsWith('item8@mail.com', $items[7]->getRecordTitle());
        $this->assertStringStartsWith('item9@mail.com', $items[8]->getRecordTitle());
    }

    public function testSearchByAliasWithCriteria()
    {
        $query = new Query();
        $query->from('oro_test_item_WEBSITE_ID');
        $expr = new Comparison("integer.integerValue", "=", 5000);
        $criteria = new Criteria();
        $criteria->where($expr);
        $query->setCriteria($criteria);

        $items = $this->getSearchItems($query);

        $this->assertCount(1, $items);
        $this->assertStringStartsWith('item5@mail.com', $items[0]->getRecordTitle());
    }
}
