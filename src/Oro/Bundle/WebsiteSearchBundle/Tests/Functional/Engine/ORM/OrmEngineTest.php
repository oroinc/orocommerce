<?php

namespace Oro\Bundle\WebsiteSearchBundle\Tests\Functional\Engine\ORM;

use Oro\Bundle\SearchBundle\Engine\Orm\PdoMysql\MysqlVersionCheckTrait;
use Oro\Bundle\SearchBundle\Query\Criteria\Comparison;
use Oro\Bundle\SearchBundle\Query\Query;
use Oro\Bundle\SearchBundle\Tests\Functional\Controller\DataFixtures\LoadSearchItemData;
use Oro\Bundle\TestFrameworkBundle\Entity\Item as TestEntity;
use Oro\Bundle\WebsiteSearchBundle\Engine\AbstractEngine;
use Oro\Bundle\WebsiteSearchBundle\Engine\ORM\Driver\DriverInterface;
use Oro\Bundle\WebsiteSearchBundle\Tests\Functional\Engine\AbstractEngineTest;

/**
 * @dbIsolationPerTest
 */
class OrmEngineTest extends AbstractEngineTest
{
    use MysqlVersionCheckTrait;

    protected function setUp()
    {
        $this->initClient();

        if ($this->getContainer()->getParameter('oro_website_search.engine') !== 'orm') {
            $this->markTestSkipped('Should be tested only with ORM search engine');
        }

        $indexer = $this->getContainer()->get('oro_website_search.indexer');
        $indexer->resetIndex(TestEntity::class);

        parent::setUp();

        $this->platform = $this->getContainer()->get('doctrine')->getManager()->getConnection()->getDatabasePlatform();
        $indexer->reindex(TestEntity::class);
    }

    public function testSearchAll()
    {
        if ($this->isMysqlPlatform() && $this->isInnoDBFulltextIndexSupported()) {
            $this->markTestSkipped(
                'Skipped because current test implementation isn\'t compatible with InnoDB Full-Text index'
            );
        }

        $query = new Query();
        $query->from('*');
        $query->getCriteria()->andWhere(new Comparison('text.stringValue', 'STARTS WITH', 'item'));
        $items = $this->getSearchItems($query);

        $this->assertCount(LoadSearchItemData::COUNT, $items);

        $this->assertEquals($this->getReference('item_1')->getId(), $items[8]->getRecordId());
        $this->assertEquals($this->getReference('item_2')->getId(), $items[7]->getRecordId());
        $this->assertEquals($this->getReference('item_3')->getId(), $items[6]->getRecordId());
        $this->assertEquals($this->getReference('item_4')->getId(), $items[5]->getRecordId());
        $this->assertEquals($this->getReference('item_5')->getId(), $items[4]->getRecordId());
        $this->assertEquals($this->getReference('item_6')->getId(), $items[3]->getRecordId());
        $this->assertEquals($this->getReference('item_7')->getId(), $items[2]->getRecordId());
        $this->assertEquals($this->getReference('item_8')->getId(), $items[1]->getRecordId());
        $this->assertEquals($this->getReference('item_9')->getId(), $items[0]->getRecordId());
    }

    /**
     * @return AbstractEngine
     */
    protected function getSearchEngine()
    {
        $driver = $this->getEngineDriver();
        $engine = $this->getContainer()->get('oro_website_search.engine');
        $engine->setDriver($driver);

        return $engine;
    }

    /**
     * @return DriverInterface
     */
    protected function getEngineDriver()
    {
        return $this->getContainer()->get('oro_website_search.engine.orm.driver');
    }
}
