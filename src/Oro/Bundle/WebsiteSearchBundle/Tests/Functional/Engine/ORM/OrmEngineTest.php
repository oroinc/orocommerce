<?php

namespace Oro\Bundle\WebsiteSearchBundle\Tests\Functional\Engine\ORM;

use Oro\Bundle\TestFrameworkBundle\Entity\Item as TestEntity;
use Oro\Bundle\WebsiteSearchBundle\Engine\AbstractEngine;
use Oro\Bundle\WebsiteSearchBundle\Engine\ORM\Driver\DriverInterface;
use Oro\Bundle\WebsiteSearchBundle\Engine\ORM\OrmIndexer;
use Oro\Bundle\WebsiteSearchBundle\Event\IndexEntityEvent;
use Oro\Bundle\WebsiteSearchBundle\Placeholder\LocalizationIdPlaceholder;
use Oro\Bundle\WebsiteSearchBundle\Tests\Functional\Engine\AbstractEngineTest;

/**
 * @dbIsolationPerTest
 */
class OrmEngineTest extends AbstractEngineTest
{
    protected function setUp()
    {
        $this->initClient();

        if ($this->getContainer()->getParameter('oro_website_search.engine') !== 'orm') {
            $this->markTestSkipped('Should be tested only with ORM search engine');
        }

        parent::setUp();

        $indexer = $this->getIndexer();
        $indexer->reindex(TestEntity::class, []);
    }

    /**
     * @return callable
     */
    protected function setListener()
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

    /**
     * @return OrmIndexer
     */
    protected function getIndexer()
    {
        $driver = $this->getEngineDriver();

        $indexer = new OrmIndexer(
            $this->getContainer()->get('oro_entity.doctrine_helper'),
            $this->mappingProvider,
            $this->getContainer()->get('oro_website_search.engine.entity_dependencies_resolver'),
            $this->getContainer()->get('oro_website_search.engine.index_data'),
            $this->getContainer()->get('oro_website_search.placeholder_decorator')
        );
        $indexer->setDriver($driver);

        return $indexer;
    }
}
