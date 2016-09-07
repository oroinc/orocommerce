<?php

namespace Oro\Bundle\WebsiteSearchBundle\Tests\Functional\Engine\ORM;

use Doctrine\Common\Persistence\ObjectManager;

use Oro\Bundle\SearchBundle\Query\Criteria\Comparison;
use Oro\Bundle\SearchBundle\Query\Criteria\Criteria;
use Oro\Bundle\SearchBundle\Query\Query;
use Oro\Bundle\SearchBundle\Query\Result;
use Oro\Bundle\SearchBundle\Query\Result\Item;
use Oro\Bundle\SearchBundle\Tests\Functional\Controller\DataFixtures\LoadSearchItemData;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;
use Oro\Bundle\WebsiteSearchBundle\Engine\ORM\OrmEngine;
use Oro\Bundle\WebsiteSearchBundle\Tests\Functional\SearchTestTrait;
use Oro\Bundle\TestFrameworkBundle\Entity\Item as TestEntity;

/**
 * @dbIsolationPerTest
 */
class OrmEngineTest extends WebTestCase
{
    use SearchTestTrait;

    /**
     * @var OrmEngine
     */
    protected $ormEngine;

    /**
     * @var ObjectManager
     */
    protected $manager;

    /**
     * @var TestEntity[]
     */
    protected $expectedSearchItems = [];

    /**
     * @var array
     */
    protected $expectedEntityConfig = [
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
    ];

    protected function setUp()
    {
        $this->initClient();

        // TODO: Add check to only run this test with orm search engine
        // TODO: when the configuration for the engine types will be added
        if (false) {
            $this->markTestSkipped('Should be tested only with ORM search engine');
        }

        $this->loadFixtures([LoadSearchItemData::class]);

        $this->getContainer()->get('event_dispatcher')->addListenerService(
            'oro_website_search.event.index_entity',
            [
                'oro_test.item.event_listener.website_search_index',
                'onWebsiteSearchIndex',
            ]
        );

        $indexer = $this->getContainer()->get('oro_website_search.engine.orm_indexer');
        $indexer->reindex(TestEntity::class, []);

        $this->ormEngine = $this->getContainer()->get('oro_website_search.orm.engine');
        $this->manager = $this->getContainer()->get('doctrine')->getManager();

        $this->expectedSearchItems = [];
        for ($ind = 1; $ind <= LoadSearchItemData::COUNT; $ind++) {
            $this->expectedSearchItems[] = $this->getReference(sprintf('searchItem%s', $ind));
        }
    }

    protected function tearDown()
    {
        $this->truncateIndexTextTable();

        unset($this->ormEngine, $this->manager, $this->expectedSearchItems);
    }

    public function testSearchAll()
    {
        $query = new Query();
        $query->from('*');

        $expectedResult = new Result(
            $query,
            [
                new Item(
                    $this->manager,
                    'Oro\Bundle\TestFrameworkBundle\Entity\Item',
                    $this->expectedSearchItems[0]->getId(),
                    'item1@mail.com',
                    null,
                    [],
                    $this->expectedEntityConfig
                ),
                new Item(
                    $this->manager,
                    'Oro\Bundle\TestFrameworkBundle\Entity\Item',
                    $this->expectedSearchItems[1]->getId(),
                    'item2@mail.com',
                    null,
                    [],
                    $this->expectedEntityConfig
                ),
                new Item(
                    $this->manager,
                    'Oro\Bundle\TestFrameworkBundle\Entity\Item',
                    $this->expectedSearchItems[2]->getId(),
                    'item3@mail.com',
                    null,
                    [],
                    $this->expectedEntityConfig
                ),
                new Item(
                    $this->manager,
                    'Oro\Bundle\TestFrameworkBundle\Entity\Item',
                    $this->expectedSearchItems[3]->getId(),
                    'item4@mail.com',
                    null,
                    [],
                    $this->expectedEntityConfig
                ),
                new Item(
                    $this->manager,
                    'Oro\Bundle\TestFrameworkBundle\Entity\Item',
                    $this->expectedSearchItems[4]->getId(),
                    'item5@mail.com',
                    null,
                    [],
                    $this->expectedEntityConfig
                ),
                new Item(
                    $this->manager,
                    'Oro\Bundle\TestFrameworkBundle\Entity\Item',
                    $this->expectedSearchItems[5]->getId(),
                    'item6@mail.com',
                    null,
                    [],
                    $this->expectedEntityConfig
                ),
                new Item(
                    $this->manager,
                    'Oro\Bundle\TestFrameworkBundle\Entity\Item',
                    $this->expectedSearchItems[6]->getId(),
                    'item7@mail.com',
                    null,
                    [],
                    $this->expectedEntityConfig
                ),
                new Item(
                    $this->manager,
                    'Oro\Bundle\TestFrameworkBundle\Entity\Item',
                    $this->expectedSearchItems[7]->getId(),
                    'item8@mail.com',
                    null,
                    [],
                    $this->expectedEntityConfig
                ),
                new Item(
                    $this->manager,
                    'Oro\Bundle\TestFrameworkBundle\Entity\Item',
                    $this->expectedSearchItems[8]->getId(),
                    'item9@mail.com',
                    null,
                    [],
                    $this->expectedEntityConfig
                ),
            ],
            9
        );

        $this->assertEquals($expectedResult, $this->ormEngine->search($query));
    }

    /**
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     */
    public function testSearchByAliasWithSelect()
    {
        $query = new Query();
        $query->from('oro_test_item_website_WEBSITE_ID');
        $query->select('stringValue_1');

        $expectedResult = new Result(
            $query,
            [
                new Item(
                    $this->manager,
                    'Oro\Bundle\TestFrameworkBundle\Entity\Item',
                    $this->expectedSearchItems[0]->getId(),
                    'item1@mail.com',
                    null,
                    [
                        'stringValue_1' => 'item1@mail.com',
                    ],
                    $this->expectedEntityConfig
                ),
                new Item(
                    $this->manager,
                    'Oro\Bundle\TestFrameworkBundle\Entity\Item',
                    $this->expectedSearchItems[1]->getId(),
                    'item2@mail.com',
                    null,
                    [
                        'stringValue_1' => 'item2@mail.com',
                    ],
                    $this->expectedEntityConfig
                ),
                new Item(
                    $this->manager,
                    'Oro\Bundle\TestFrameworkBundle\Entity\Item',
                    $this->expectedSearchItems[2]->getId(),
                    'item3@mail.com',
                    null,
                    [
                        'stringValue_1' => 'item3@mail.com',
                    ],
                    $this->expectedEntityConfig
                ),
                new Item(
                    $this->manager,
                    'Oro\Bundle\TestFrameworkBundle\Entity\Item',
                    $this->expectedSearchItems[3]->getId(),
                    'item4@mail.com',
                    null,
                    [
                        'stringValue_1' => 'item4@mail.com',
                    ],
                    $this->expectedEntityConfig
                ),
                new Item(
                    $this->manager,
                    'Oro\Bundle\TestFrameworkBundle\Entity\Item',
                    $this->expectedSearchItems[4]->getId(),
                    'item5@mail.com',
                    null,
                    [
                        'stringValue_1' => 'item5@mail.com',
                    ],
                    $this->expectedEntityConfig
                ),
                new Item(
                    $this->manager,
                    'Oro\Bundle\TestFrameworkBundle\Entity\Item',
                    $this->expectedSearchItems[5]->getId(),
                    'item6@mail.com',
                    null,
                    [
                        'stringValue_1' => 'item6@mail.com',
                    ],
                    $this->expectedEntityConfig
                ),
                new Item(
                    $this->manager,
                    'Oro\Bundle\TestFrameworkBundle\Entity\Item',
                    $this->expectedSearchItems[6]->getId(),
                    'item7@mail.com',
                    null,
                    [
                        'stringValue_1' => 'item7@mail.com',
                    ],
                    $this->expectedEntityConfig
                ),
                new Item(
                    $this->manager,
                    'Oro\Bundle\TestFrameworkBundle\Entity\Item',
                    $this->expectedSearchItems[7]->getId(),
                    'item8@mail.com',
                    null,
                    [
                        'stringValue_1' => 'item8@mail.com',
                    ],
                    $this->expectedEntityConfig
                ),
                new Item(
                    $this->manager,
                    'Oro\Bundle\TestFrameworkBundle\Entity\Item',
                    $this->expectedSearchItems[8]->getId(),
                    'item9@mail.com',
                    null,
                    [
                        'stringValue_1' => 'item9@mail.com',
                    ],
                    $this->expectedEntityConfig
                ),
            ],
            9
        );

        $this->assertEquals($expectedResult, $this->ormEngine->search($query));
    }

    public function testSearchByAliasWithCriteria()
    {
        $query = new Query();
        $query->from('oro_test_item_website_WEBSITE_ID');
        $expr = new Comparison("integer.integerValue", "=", 5000);
        $criteria = new Criteria();
        $criteria->where($expr);
        $query->setCriteria($criteria);

        $expectedResult = new Result(
            $query,
            [
                new Item(
                    $this->manager,
                    'Oro\Bundle\TestFrameworkBundle\Entity\Item',
                    $this->expectedSearchItems[4]->getId(),
                    'item5@mail.com',
                    null,
                    [],
                    $this->expectedEntityConfig
                ),
            ],
            1
        );

        $this->assertEquals($expectedResult, $this->ormEngine->search($query));
    }
}
