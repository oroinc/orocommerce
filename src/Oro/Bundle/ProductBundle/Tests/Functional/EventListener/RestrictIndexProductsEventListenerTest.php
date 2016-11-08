<?php

namespace Oro\Bundle\ProductBundle\Tests\Functional\EventListener;

use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\HttpFoundation\Request;

use Oro\Bundle\WebsiteSearchBundle\Tests\Functional\Traits\DefaultLocalizationIdTestTrait;
use Oro\Bundle\WebsiteSearchBundle\Tests\Functional\Traits\DefaultWebsiteIdTestTrait;
use Oro\Bundle\SearchBundle\Query\Query;
use Oro\Bundle\SearchBundle\Query\Result\Item;
use Oro\Bundle\SearchBundle\Tests\Functional\SearchExtensionTrait;
use Oro\Bundle\ProductBundle\Tests\Functional\DataFixtures\LoadProductData;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\WebsiteSearchBundle\Engine\AbstractIndexer;
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
    use SearchExtensionTrait;

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
        $context = [
            AbstractIndexer::CONTEXT_WEBSITE_IDS => [$this->getDefaultWebsiteId()]
        ];

        $expectedCount = 6;

        $indexer = $this->getContainer()->get('oro_website_search.indexer');
        $indexer->resetIndex(Product::class, $context);

        $this->getContainer()->get('event_dispatcher')->dispatch(
            ReindexationRequestEvent::EVENT_NAME,
            new ReindexationRequestEvent([Product::class], [$this->getDefaultWebsiteId()], [], false)
        );

        $alias = 'oro_product_' . $this->getDefaultWebsiteId();
        $this->ensureItemsLoaded($alias, $expectedCount, 'oro_website_search.engine');

        $query = new Query();
        $query->from('oro_product_WEBSITE_ID');
        $query->select('name_LOCALIZATION_ID');
        $query->getCriteria()->orderBy(['name_' . $this->getDefaultLocalizationId() => Query::ORDER_ASC]);

        $searchEngine = $this->getContainer()->get('oro_website_search.engine');
        $result = $searchEngine->search($query);
        $values = $result->getElements();

        $this->assertEquals($expectedCount, $result->getRecordsCount());
        $this->assertSearchItems('product.1', $values[0]);
        $this->assertSearchItems('product.2', $values[1]);
        $this->assertSearchItems('product.3', $values[2]);
        $this->assertSearchItems('product.6', $values[3]);
        $this->assertSearchItems('product.7', $values[4]);
        $this->assertSearchItems('product.8', $values[5]);
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

    /**
     * @param mixed $expectedValue
     * @param Item $value
     */
    protected function assertSearchItems($expectedValue, Item $value)
    {
        $selectedData = $value->getSelectedData();
        $field = 'name_' . $this->getDefaultLocalizationId();

        if (!array_key_exists($field, $selectedData)) {
            throw new \RuntimeException(
                sprintf('Field "%s" could not be found in selected data array', $field)
            );
        }

        $this->assertStringStartsWith($expectedValue, $selectedData[$field]);
    }
}
