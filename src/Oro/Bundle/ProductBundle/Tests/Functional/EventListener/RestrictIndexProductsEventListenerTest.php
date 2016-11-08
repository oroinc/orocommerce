<?php

namespace Oro\Bundle\ProductBundle\Tests\Functional\EventListener;

use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\HttpFoundation\Request;

use Oro\Bundle\WebsiteSearchBundle\Tests\Functional\Traits\DefaultLocalizationIdTestTrait;
use Oro\Bundle\WebsiteSearchBundle\Tests\Functional\Traits\DefaultWebsiteIdTestTrait;
use Oro\Bundle\SearchBundle\Query\Query;
use Oro\Bundle\ProductBundle\Tests\Functional\DataFixtures\LoadProductData;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\WebsiteSearchBundle\Event\ReindexationRequestEvent;
use Oro\Bundle\WebsiteSearchBundle\Event\RestrictIndexEntityEvent;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;

/**
 * @dbIsolationPerTest
 */
class RestrictIndexProductsEventListenerTest extends WebTestCase
{
    use DefaultWebsiteIdTestTrait;
    use DefaultLocalizationIdTestTrait;

    /** @var EventDispatcherInterface */
    private $dispatcher;

    protected function setUp()
    {
        $this->initClient();
        $this->getContainer()->get('request_stack')->push(Request::create(''));
        $this->dispatcher = $this->getContainer()->get('event_dispatcher');

        $listener = $this->getContainer()->get('oro_product.event_listener.restrict_index_products');
        $eventName = sprintf('%s.%s', RestrictIndexEntityEvent::NAME, 'product');

        $this->clearRestrictListeners($eventName);

        $this->dispatcher->addListener(
            $eventName,
            [
                $listener,
                'onRestrictIndexEntityEvent'
            ],
            -255
        );

        $this->loadFixtures([LoadProductData::class]);
    }

    public function testRestrictIndexProductsEventListener()
    {
        // TODO: Remove in BB-4512
        if ($this->getContainer()->getParameter('oro_search.engine') === 'elastic_search') {
            $this->markTestSkipped('Disabled for Elastic Search until search method is ready in BB-4512');
        }

        $this->getContainer()->get('event_dispatcher')->dispatch(
            ReindexationRequestEvent::EVENT_NAME,
            new ReindexationRequestEvent([Product::class], [$this->getDefaultWebsiteId()], [], false)
        );

        $query = new Query();
        $query->from('oro_product_WEBSITE_ID');
        $query->select('recordTitle');
        $query->getCriteria()->orderBy(['title_' . $this->getDefaultLocalizationId() => Query::ORDER_ASC]);

        $searchEngine = $this->getContainer()->get('oro_website_search.engine');
        $result = $searchEngine->search($query);
        $values = $result->getElements();

        $this->assertEquals(6, $result->getRecordsCount());
        $this->assertStringStartsWith('product.1', $values[0]->getRecordTitle());
        $this->assertStringStartsWith('product.2', $values[1]->getRecordTitle());
        $this->assertStringStartsWith('product.3', $values[2]->getRecordTitle());
        $this->assertStringStartsWith('product.6', $values[3]->getRecordTitle());
        $this->assertStringStartsWith('product.7', $values[4]->getRecordTitle());
        $this->assertStringStartsWith('product.8', $values[5]->getRecordTitle());
    }

    /**
     * @param string $eventName
     */
    protected function clearRestrictListeners($eventName)
    {
        foreach ($this->dispatcher->getListeners($eventName) as $listener) {
            $this->dispatcher->removeListener($eventName, $listener);
        }
    }
}
