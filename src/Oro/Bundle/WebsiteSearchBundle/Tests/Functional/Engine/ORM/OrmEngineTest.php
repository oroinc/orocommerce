<?php

namespace Oro\Bundle\WebsiteSearchBundle\Tests\Functional\Engine\ORM;

use Doctrine\Common\Persistence\ObjectManager;

use Oro\Bundle\FrontendBundle\Request\FrontendHelper;
use Oro\Bundle\SearchBundle\Query\Criteria\Comparison;
use Oro\Bundle\SearchBundle\Query\Criteria\Criteria;
use Oro\Bundle\SearchBundle\Query\Query;
use Oro\Bundle\SearchBundle\Query\Result;
use Oro\Bundle\SearchBundle\Query\Result\Item;
use Oro\Bundle\SearchBundle\Tests\Functional\Controller\DataFixtures\LoadSearchItemData;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;
use Oro\Bundle\WebsiteSearchBundle\Engine\ORM\OrmEngine;
use Oro\Bundle\WebsiteSearchBundle\Event\IndexEntityEvent;
use Oro\Bundle\WebsiteSearchBundle\Tests\Functional\SearchTestTrait;
use Oro\Bundle\TestFrameworkBundle\Entity\Item as TestEntity;

/**
 * @dbIsolationPerTest
 */
class OrmEngineTest extends WebTestCase
{
    use SearchTestTrait;

    /**
     * @var callable
     */
    protected $listener;

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

        if ($this->getContainer()->getParameter('oro_website_search.engine') != 'orm') {
            $this->markTestSkipped('Should be tested only with ORM search engine');
        }

        /** @var FrontendHelper|\PHPUnit_Framework_MockObject_MockObject */
        $frontendHelperMock = $this->getMockBuilder(FrontendHelper::class)
            ->disableOriginalConstructor()
            ->getMock();

        $frontendHelperMock->expects($this->any())
            ->method('isFrontendRequest')
            ->willReturn(true);

        $this->getContainer()->set('orob2b_frontend.request.frontend_helper', $frontendHelperMock);

        $this->loadFixtures([LoadSearchItemData::class]);

        $this->listener = $this->setListener();

        $indexer = $this->getContainer()->get('oro_website_search.indexer');
        $indexer->reindex(TestEntity::class, []);

        $this->ormEngine = $this->getContainer()->get('oro_website_search.engine');
        $this->manager = $this->getContainer()->get('doctrine')->getManager();

        $this->expectedSearchItems = [];
        $this->expectedSearchItems[] = $this->getReference('searchItem1');
        $this->expectedSearchItems[] = $this->getReference('searchItem2');
        $this->expectedSearchItems[] = $this->getReference('searchItem3');
        $this->expectedSearchItems[] = $this->getReference('searchItem4');
        $this->expectedSearchItems[] = $this->getReference('searchItem5');
        $this->expectedSearchItems[] = $this->getReference('searchItem6');
        $this->expectedSearchItems[] = $this->getReference('searchItem7');
        $this->expectedSearchItems[] = $this->getReference('searchItem8');
        $this->expectedSearchItems[] = $this->getReference('searchItem9');
    }

    protected function tearDown()
    {
        $this->truncateIndexTextTable();

        unset($this->listener, $this->ormEngine, $this->manager, $this->expectedSearchItems);
    }

    /**
     * @return callable
     */
    private function setListener()
    {
        $listener = function (IndexEntityEvent $event) {
            $items = $this->getContainer()->get('doctrine')
                ->getRepository(TestEntity::class)
                ->findBy(['id' => $event->getEntityIds()]);

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
                    'stringValue_1',
                    $item->stringValue
                );
                $event->addField(
                    $item->getId(),
                    Query::TYPE_TEXT,
                    'all_text_1',
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
