<?php

namespace Oro\Bundle\AccountBundle\Tests\Functional\EventListener;

use Oro\Bundle\AccountBundle\Tests\Functional\DataFixtures\LoadProductVisibilityData;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\SearchBundle\Query\Query;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;
use Oro\Bundle\WebsiteSearchBundle\Engine\AbstractIndexer;
use Oro\Bundle\WebsiteSearchBundle\Event\RestrictIndexEntityEvent;
use Oro\Bundle\WebsiteSearchBundle\Tests\Functional\FrontendRequestTrait;
use Oro\Bundle\WebsiteSearchBundle\Tests\Functional\SearchTestInterface;
use Oro\Bundle\WebsiteSearchBundle\Tests\Functional\SearchTestTrait;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

/**
 * @dbIsolationPerTest
 */
class RestrictedProductsIndexEventListenerTest extends WebTestCase implements SearchTestInterface
{
    use FrontendRequestTrait;
    use SearchTestTrait;

    /**
     * @var EventDispatcherInterface
     */
    private $dispatcher;

    protected function setUp()
    {
        $this->initClient();

        $this->substituteRequestStack();

        $this->dispatcher = $this->getContainer()->get('event_dispatcher');
        $this->clearRestrictListeners();
        $this->setListener();

        $this->loadFixtures([LoadProductVisibilityData::class]);
    }

    public function testRestrictIndexEntityEventListener()
    {
        $website = $this->getContainer()
            ->get('doctrine')
            ->getRepository('OroWebsiteBundle:Website')
            ->getDefaultWebsite();

        $indexer = $this->getContainer()->get('oro_website_search.indexer');
        $searchEngine = $this->getContainer()->get('oro_website_search.engine');
        $indexer->reindex(Product::class, [AbstractIndexer::CONTEXT_WEBSITE_ID_KEY => $website->getId()]);

        $query = new Query();
        $query->from('oro_product_product_WEBSITE_ID');
        $query->select('recordTitle');

        $result = $searchEngine->search($query);
        $values = $result->getElements();

        $this->assertEquals(6, $result->getRecordsCount());
        $this->assertEquals('product.1', $values[0]->getRecordTitle());
        $this->assertEquals('product.4', $values[1]->getRecordTitle());
        $this->assertEquals('product.5', $values[2]->getRecordTitle());
        $this->assertEquals('product.6', $values[3]->getRecordTitle());
        $this->assertEquals('product.7', $values[4]->getRecordTitle());
        $this->assertEquals('product.8', $values[5]->getRecordTitle());
    }

    /**
     * {@inheritdoc}
     */
    public function getRestrictEntityEventName()
    {
        return sprintf('%s.%s', RestrictIndexEntityEvent::NAME, 'product');
    }

    /**
     * @return callable
     */
    private function setListener()
    {
        $this->dispatcher->addListener(
            $this->getRestrictEntityEventName(),
            [
                $this->getContainer()->get('oro_account.event_listener.restricted_products_index'),
                'onRestrictIndexEntityEvent'
            ],
            -255
        );
    }
}
