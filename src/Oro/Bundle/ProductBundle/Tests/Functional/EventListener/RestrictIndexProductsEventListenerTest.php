<?php

namespace Oro\Bundle\ProductBundle\Tests\Functional\EventListener;

use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\ProductBundle\Tests\Functional\DataFixtures\LoadProductData;
use Oro\Bundle\SearchBundle\Query\Query;
use Oro\Bundle\SearchBundle\Query\Result\Item;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;
use Oro\Bundle\WebsiteSearchBundle\Engine\AbstractIndexer;
use Oro\Bundle\WebsiteSearchBundle\Event\ReindexationRequestEvent;
use Oro\Bundle\WebsiteSearchBundle\Event\RestrictIndexEntityEvent;
use Oro\Bundle\WebsiteSearchBundle\Tests\Functional\Traits\DefaultLocalizationIdTestTrait;
use Oro\Bundle\WebsiteSearchBundle\Tests\Functional\WebsiteSearchExtensionTrait;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\HttpFoundation\Request;

/**
 * @dbIsolationPerTest
 */
class RestrictIndexProductsEventListenerTest extends WebTestCase
{
    use DefaultLocalizationIdTestTrait;
    use WebsiteSearchExtensionTrait;

    private EventDispatcherInterface $dispatcher;

    protected function setUp(): void
    {
        $this->initClient();
        self::getContainer()->get('request_stack')->push(Request::create(''));
        $this->dispatcher = self::getContainer()->get('event_dispatcher');

        $listener = self::getContainer()->get('oro_product.event_listener.restrict_index_products');
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

    public function testRestrictIndexProductsEventListener(): void
    {
        $context = [
            AbstractIndexer::CONTEXT_WEBSITE_IDS => [self::getDefaultWebsiteId()]
        ];

        $expectedCount = 7;

        $indexer = self::getContainer()->get('oro_website_search.indexer');
        $indexer->resetIndex(Product::class, $context);

        self::getContainer()->get('event_dispatcher')->dispatch(
            new ReindexationRequestEvent([Product::class], [self::getDefaultWebsiteId()], [], false),
            ReindexationRequestEvent::EVENT_NAME
        );

        self::ensureItemsLoaded(Product::class, $expectedCount);

        $query = new Query();
        $query->from('oro_product_WEBSITE_ID');
        $query->select('names_LOCALIZATION_ID');
        $query->getCriteria()->orderBy(['names_' . $this->getDefaultLocalizationId() => Query::ORDER_ASC]);

        $searchEngine = self::getContainer()->get('oro_website_search.engine');
        $result = $searchEngine->search($query);
        $values = $result->getElements();

        self::assertEquals($expectedCount, $result->getRecordsCount());
        $this->assertSearchItems('product-1', $values[0]);
        $this->assertSearchItems('product-2', $values[1]);
        $this->assertSearchItems('product-3', $values[2]);
        $this->assertSearchItems('product-6', $values[3]);
        $this->assertSearchItems('product-8', $values[4]);
        $this->assertSearchItems('продукт-7', $values[5]);
    }

    /**
     * @param string $eventName
     */
    protected function clearRestrictListeners($eventName): void
    {
        foreach ($this->dispatcher->getListeners($eventName) as $listener) {
            $this->dispatcher->removeListener($eventName, $listener);
        }
    }

    /**
     * @param mixed $expectedValue
     * @param Item $value
     */
    protected function assertSearchItems($expectedValue, Item $value): void
    {
        $selectedData = $value->getSelectedData();
        $field = 'names_' . $this->getDefaultLocalizationId();

        if (!array_key_exists($field, $selectedData)) {
            throw new \RuntimeException(
                sprintf('Field "%s" could not be found in selected data array', $field)
            );
        }

        self::assertStringStartsWith($expectedValue, $selectedData[$field]);
    }
}
