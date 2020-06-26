<?php

namespace Oro\Bundle\WebsiteSearchBundle\Tests\Functional\Engine\ORM;

use Oro\Bundle\SearchBundle\Engine\Orm\PdoMysql\MysqlVersionCheckTrait;
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

    protected function setUp(): void
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

        parent::testSearchAll();
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
