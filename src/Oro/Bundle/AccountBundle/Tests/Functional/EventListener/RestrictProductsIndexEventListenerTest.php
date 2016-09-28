<?php

namespace Oro\Bundle\AccountBundle\Tests\Functional\EventListener;

use Oro\Bundle\AccountBundle\Tests\Functional\DataFixtures\LoadProductVisibilityData;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\SearchBundle\Query\Query;
use Oro\Bundle\WebsiteSearchBundle\Engine\AbstractIndexer;
use Oro\Bundle\WebsiteSearchBundle\Event\RestrictIndexEntityEvent;
use Oro\Bundle\WebsiteSearchBundle\Tests\Functional\AbstractSearchWebTestCase;

/**
 * @dbIsolationPerTest
 */
class RestrictProductsIndexEventListenerTest extends AbstractSearchWebTestCase
{
    protected function setUp()
    {
        parent::setUp();

        $this->clearRestrictListeners($this->getRestrictEntityEventName());

        $this->dispatcher->addListener(
            $this->getRestrictEntityEventName(),
            [
                $this->getContainer()->get('oro_account.event_listener.restrict_products_index'),
                'onRestrictIndexEntityEvent'
            ],
            -255
        );

        $this->loadFixtures([LoadProductVisibilityData::class]);

        $this->getContainer()->get('oro_account.visibility.cache.product.cache_builder')->buildCache();
    }

    public function testRestrictIndexEntityEventListener()
    {
        $configManager = $this->getContainer()->get('oro_config.manager');
        $configManager->set('oro_account.product_visibility', 'visible');

        $indexer = $this->getContainer()->get('oro_website_search.indexer');
        $searchEngine = $this->getContainer()->get('oro_website_search.engine');
        $indexer->reindex(Product::class, [AbstractIndexer::CONTEXT_WEBSITE_ID_KEY => $this->getDefaultWebsiteId()]);

        $query = new Query();
        $query->from('oro_product_WEBSITE_ID');
        $query->select('recordTitle');
        $query->getCriteria()->orderBy(['title_' . $this->getDefaultWebsiteId() => Query::ORDER_ASC]);

        $result = $searchEngine->search($query);
        $values = $result->getElements();

        $this->assertEquals(7, $result->getRecordsCount());
        $this->assertEquals('product.1', $values[0]->getRecordTitle());
        $this->assertEquals('product.3', $values[1]->getRecordTitle());
        $this->assertEquals('product.4', $values[1]->getRecordTitle());
        $this->assertEquals('product.5', $values[2]->getRecordTitle());
        $this->assertEquals('product.6', $values[3]->getRecordTitle());
        $this->assertEquals('product.7', $values[4]->getRecordTitle());
        $this->assertEquals('product.8', $values[5]->getRecordTitle());

        $configManager->reset('oro_account.product_visibility');
    }

    /**
     * {@inheritdoc}
     */
    public function getRestrictEntityEventName()
    {
        return sprintf('%s.%s', RestrictIndexEntityEvent::NAME, 'product');
    }
}
