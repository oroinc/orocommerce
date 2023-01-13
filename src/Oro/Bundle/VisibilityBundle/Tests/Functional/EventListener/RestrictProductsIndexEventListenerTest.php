<?php

namespace Oro\Bundle\VisibilityBundle\Tests\Functional\EventListener;

use Oro\Bundle\ConfigBundle\Config\ConfigManager;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\SearchBundle\Engine\EngineInterface;
use Oro\Bundle\SearchBundle\Query\Query;
use Oro\Bundle\SearchBundle\Query\Result\Item;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;
use Oro\Bundle\VisibilityBundle\Entity\Visibility\VisibilityInterface;
use Oro\Bundle\VisibilityBundle\EventListener\RestrictProductsIndexEventListener;
use Oro\Bundle\VisibilityBundle\Tests\Functional\DataFixtures\LoadProductVisibilityData;
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
class RestrictProductsIndexEventListenerTest extends WebTestCase
{
    use DefaultLocalizationIdTestTrait;
    use WebsiteSearchExtensionTrait;

    private const PRODUCT_VISIBILITY_CONFIGURATION_PATH = 'oro_visibility.product_visibility';
    private const CATEGORY_VISIBILITY_CONFIGURATION_PATH = 'oro_visibility.category_visibility';

    /** @var ConfigManager|\PHPUnit\Framework\MockObject\MockObject */
    private $configManager;

    /** @var EventDispatcherInterface */
    private $dispatcher;

    protected function setUp(): void
    {
        $this->initClient();

        self::getContainer()->get('request_stack')->push(Request::create(''));
        $this->dispatcher = self::getContainer()->get('event_dispatcher');

        $this->configManager = $this->createMock(ConfigManager::class);

        $listener = new RestrictProductsIndexEventListener(
            self::getContainer()->get('oro_entity.doctrine_helper'),
            $this->configManager,
            self::PRODUCT_VISIBILITY_CONFIGURATION_PATH,
            self::CATEGORY_VISIBILITY_CONFIGURATION_PATH,
            self::getContainer()->get('oro_website_search.manager.website_context_manager')
        );

        $listener->setVisibilityScopeProvider(
            self::getContainer()->get('oro_visibility.provider.visibility_scope_provider')
        );

        $restrictEntityEventName = sprintf('%s.%s', RestrictIndexEntityEvent::NAME, 'product');
        $this->clearRestrictListeners($restrictEntityEventName);
        $this->clearRestrictListeners('oro_product.product_search_query.restriction');

        $this->dispatcher->addListener(
            $restrictEntityEventName,
            [$listener, 'onRestrictIndexEntityEvent'],
            -255
        );

        $this->loadFixtures([LoadProductVisibilityData::class]);

        self::getContainer()->get('oro_visibility.visibility.cache.product.cache_builder')->buildCache();
    }

    /**
     * @return Item[]
     */
    private function runIndexationAndSearch(int $expectedItems): array
    {
        $context = [
            AbstractIndexer::CONTEXT_WEBSITE_IDS => [self::getDefaultWebsiteId()]
        ];

        self::resetIndex(Product::class, $context);
        self::ensureItemsLoaded(Product::class, 0);

        self::getContainer()->get('event_dispatcher')->dispatch(
            new ReindexationRequestEvent([Product::class], [self::getDefaultWebsiteId()], [], false),
            ReindexationRequestEvent::EVENT_NAME
        );

        self::ensureItemsLoaded(Product::class, $expectedItems);

        $query = new Query();
        $query->from('oro_product_WEBSITE_ID');
        $query->select('names_LOCALIZATION_ID');
        $query->getCriteria()->orderBy(['sku' => Query::ORDER_ASC]);

        /** @var EngineInterface $searchEngine */
        $searchEngine = self::getContainer()->get('oro_website_search.engine');

        return $searchEngine->search($query)->getElements();
    }

    public function testRestrictIndexEntityEventListenerWhenAllFallBacksAreVisible(): void
    {
        $this->configManager->expects(self::exactly(2))
            ->method('get')
            ->withConsecutive(
                [self::PRODUCT_VISIBILITY_CONFIGURATION_PATH],
                [self::CATEGORY_VISIBILITY_CONFIGURATION_PATH]
            )
            ->willReturnOnConsecutiveCalls(VisibilityInterface::VISIBLE, VisibilityInterface::VISIBLE);

        $expectedCount = 9;
        $values = $this->runIndexationAndSearch($expectedCount);

        self::assertCount($expectedCount, $values);
        $this->assertSearchItems('product-1', $values[0]);
        $this->assertSearchItems('product-2', $values[1]);
        $this->assertSearchItems('product-3', $values[2]);
        $this->assertSearchItems('product-4', $values[3]);
        $this->assertSearchItems('product-5', $values[4]);
        $this->assertSearchItems('product-6', $values[5]);
        $this->assertSearchItems('product-8', $values[6]);
        $this->assertSearchItems('продукт-7', $values[7]);
        $this->assertSearchItems('продукт-9', $values[8]);
    }

    public function testRestrictIndexEntityEventListenerWhenAllFallBacksAreHidden(): void
    {
        $this->configManager->expects($this->exactly(2))
            ->method('get')
            ->withConsecutive(
                [self::PRODUCT_VISIBILITY_CONFIGURATION_PATH],
                [self::CATEGORY_VISIBILITY_CONFIGURATION_PATH]
            )
            ->willReturnOnConsecutiveCalls(VisibilityInterface::HIDDEN, VisibilityInterface::HIDDEN);

        $expectedCount = 5;
        $values = $this->runIndexationAndSearch($expectedCount);

        self::assertCount($expectedCount, $values);
        $this->assertSearchItems('product-1', $values[0]);
        $this->assertSearchItems('product-2', $values[1]);
        $this->assertSearchItems('product-3', $values[2]);
        $this->assertSearchItems('product-4', $values[3]);
        $this->assertSearchItems('product-5', $values[4]);
    }

    public function testRestrictIndexEntityEventListenerWhenProductFallBackIsVisibleAndCategoryFallBackIsHidden(): void
    {
        $this->configManager->expects($this->exactly(2))
            ->method('get')
            ->withConsecutive(
                [self::PRODUCT_VISIBILITY_CONFIGURATION_PATH],
                [self::CATEGORY_VISIBILITY_CONFIGURATION_PATH]
            )
            ->willReturnOnConsecutiveCalls(VisibilityInterface::VISIBLE, VisibilityInterface::HIDDEN);

        $expectedCount = 9;
        $values = $this->runIndexationAndSearch($expectedCount);

        self::assertCount($expectedCount, $values);
        $this->assertSearchItems('product-1', $values[0]);
        $this->assertSearchItems('product-2', $values[1]);
        $this->assertSearchItems('product-3', $values[2]);
        $this->assertSearchItems('product-4', $values[3]);
        $this->assertSearchItems('product-5', $values[4]);
        $this->assertSearchItems('product-6', $values[5]);
        $this->assertSearchItems('product-8', $values[6]);
        $this->assertSearchItems('продукт-7', $values[7]);
        $this->assertSearchItems('продукт-9', $values[8]);
    }

    public function testRestrictIndexEntityEventListenerWhenProductFallBackIsHiddenAndCategoryFallBackIsVisible(): void
    {
        $this->configManager->expects(self::exactly(2))
            ->method('get')
            ->withConsecutive(
                [self::PRODUCT_VISIBILITY_CONFIGURATION_PATH],
                [self::CATEGORY_VISIBILITY_CONFIGURATION_PATH]
            )
            ->willReturnOnConsecutiveCalls(VisibilityInterface::HIDDEN, VisibilityInterface::VISIBLE);

        $expectedCount = 7;
        $values = $this->runIndexationAndSearch($expectedCount);

        self::assertCount($expectedCount, $values);
        $this->assertSearchItems('product-1', $values[0]);
        $this->assertSearchItems('product-2', $values[1]);
        $this->assertSearchItems('product-3', $values[2]);
        $this->assertSearchItems('product-4', $values[3]);
        $this->assertSearchItems('product-5', $values[4]);
        $this->assertSearchItems('product-8', $values[5]);
        $this->assertSearchItems('продукт-7', $values[6]);
    }

    private function assertSearchItems(mixed $expectedValue, Item $value): void
    {
        $selectedData = $value->getSelectedData();
        $field = 'names_' . $this->getDefaultLocalizationId();
        if (!array_key_exists($field, $selectedData)) {
            throw new \RuntimeException(sprintf('Field "%s" could not be found in selected data array', $field));
        }

        self::assertStringStartsWith($expectedValue, $selectedData[$field]);
    }

    private function clearRestrictListeners(string $eventName): void
    {
        foreach ($this->dispatcher->getListeners($eventName) as $listener) {
            $this->dispatcher->removeListener($eventName, $listener);
        }
    }
}
