<?php

namespace Oro\Bundle\VisibilityBundle\Tests\Functional\EventListener;

use Oro\Bundle\ConfigBundle\Config\ConfigManager;
use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\SearchBundle\Query\Query;
use Oro\Bundle\SearchBundle\Query\Result\Item;
use Oro\Bundle\SearchBundle\Tests\Functional\SearchExtensionTrait;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;
use Oro\Bundle\VisibilityBundle\Entity\Visibility\VisibilityInterface;
use Oro\Bundle\VisibilityBundle\EventListener\RestrictProductsIndexEventListener;
use Oro\Bundle\VisibilityBundle\Tests\Functional\DataFixtures\LoadProductVisibilityData;
use Oro\Bundle\WebsiteSearchBundle\Engine\AbstractIndexer;
use Oro\Bundle\WebsiteSearchBundle\Event\ReindexationRequestEvent;
use Oro\Bundle\WebsiteSearchBundle\Event\RestrictIndexEntityEvent;
use Oro\Bundle\WebsiteSearchBundle\Manager\WebsiteContextManager;
use Oro\Bundle\WebsiteSearchBundle\Tests\Functional\Traits\DefaultLocalizationIdTestTrait;
use Oro\Bundle\WebsiteSearchBundle\Tests\Functional\Traits\DefaultWebsiteIdTestTrait;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\HttpFoundation\Request;

/**
 * @dbIsolationPerTest
 */
class RestrictProductsIndexEventListenerTest extends WebTestCase
{
    use DefaultWebsiteIdTestTrait;
    use DefaultLocalizationIdTestTrait;
    use SearchExtensionTrait;

    const PRODUCT_VISIBILITY_CONFIGURATION_PATH = 'oro_visibility.product_visibility';
    const CATEGORY_VISIBILITY_CONFIGURATION_PATH = 'oro_visibility.category_visibility';

    /** @var ConfigManager|\PHPUnit\Framework\MockObject\MockObject */
    private $configManager;

    /** @var EventDispatcherInterface */
    private $dispatcher;

    protected function setUp(): void
    {
        $this->initClient();

        $this->getContainer()->get('request_stack')->push(Request::create(''));
        $this->dispatcher = $this->getContainer()->get('event_dispatcher');

        $this->configManager = $this->getMockBuilder(ConfigManager::class)
            ->disableOriginalConstructor()
            ->getMock();

        /** @var DoctrineHelper $doctrineHelper */
        $doctrineHelper = $this->getContainer()->get('oro_entity.doctrine_helper');
        $websiteContextManager = new WebsiteContextManager($doctrineHelper);

        $listener = new RestrictProductsIndexEventListener(
            $doctrineHelper,
            $this->configManager,
            self::PRODUCT_VISIBILITY_CONFIGURATION_PATH,
            self::CATEGORY_VISIBILITY_CONFIGURATION_PATH,
            $websiteContextManager
        );

        $listener->setVisibilityScopeProvider(
            $this->getContainer()->get('oro_visibility.provider.visibility_scope_provider')
        );

        $this->clearRestrictListeners($this->getRestrictEntityEventName());
        $this->clearRestrictListeners('oro_product.product_search_query.restriction');

        $this->dispatcher->addListener(
            $this->getRestrictEntityEventName(),
            [
                $listener,
                'onRestrictIndexEntityEvent'
            ],
            -255
        );

        $this->loadFixtures([LoadProductVisibilityData::class]);

        $this->getContainer()->get('oro_visibility.visibility.cache.product.cache_builder')->buildCache();
    }

    /**
     * @param int $expectedItems
     * @return Item[]
     */
    protected function runIndexationAndSearch($expectedItems)
    {
        $context = [
            AbstractIndexer::CONTEXT_WEBSITE_IDS => [$this->getDefaultWebsiteId()]
        ];

        $alias = 'oro_product_' . $this->getDefaultWebsiteId();

        $indexer = $this->getContainer()->get('oro_website_search.indexer');
        $indexer->resetIndex(Product::class, $context);
        $this->ensureItemsLoaded($alias, 0, 'oro_website_search.engine');

        $this->getContainer()->get('event_dispatcher')->dispatch(
            new ReindexationRequestEvent([Product::class], [$this->getDefaultWebsiteId()], [], false),
            ReindexationRequestEvent::EVENT_NAME
        );

        $this->ensureItemsLoaded($alias, $expectedItems, 'oro_website_search.engine');

        $query = new Query();
        $query->from('oro_product_WEBSITE_ID');
        $query->select('names_LOCALIZATION_ID');
        $query->getCriteria()->orderBy(['sku' => Query::ORDER_ASC]);

        $searchEngine = $this->getContainer()->get('oro_website_search.engine');
        $result = $searchEngine->search($query);

        return $result->getElements();
    }

    public function testRestrictIndexEntityEventListenerWhenAllFallBacksAreVisible()
    {
        $this->configManager
            ->expects($this->exactly(2))
            ->method('get')
            ->withConsecutive(
                [self::PRODUCT_VISIBILITY_CONFIGURATION_PATH],
                [self::CATEGORY_VISIBILITY_CONFIGURATION_PATH]
            )
            ->willReturnOnConsecutiveCalls(VisibilityInterface::VISIBLE, VisibilityInterface::VISIBLE);

        $expectedCount = 9;
        $values = $this->runIndexationAndSearch($expectedCount);

        $this->assertCount($expectedCount, $values);
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

    public function testRestrictIndexEntityEventListenerWhenAllFallBacksAreHidden()
    {
        $this->configManager
            ->expects($this->exactly(2))
            ->method('get')
            ->withConsecutive(
                [self::PRODUCT_VISIBILITY_CONFIGURATION_PATH],
                [self::CATEGORY_VISIBILITY_CONFIGURATION_PATH]
            )
            ->willReturnOnConsecutiveCalls(VisibilityInterface::HIDDEN, VisibilityInterface::HIDDEN);

        $expectedCount = 5;
        $values = $this->runIndexationAndSearch($expectedCount);

        $this->assertCount($expectedCount, $values);
        $this->assertSearchItems('product-1', $values[0]);
        $this->assertSearchItems('product-2', $values[1]);
        $this->assertSearchItems('product-3', $values[2]);
        $this->assertSearchItems('product-4', $values[3]);
        $this->assertSearchItems('product-5', $values[4]);
    }

    public function testRestrictIndexEntityEventListenerWhenProductFallBackIsVisibleAndCategoryFallBackIsHidden()
    {
        $this->configManager
            ->expects($this->exactly(2))
            ->method('get')
            ->withConsecutive(
                [self::PRODUCT_VISIBILITY_CONFIGURATION_PATH],
                [self::CATEGORY_VISIBILITY_CONFIGURATION_PATH]
            )
            ->willReturnOnConsecutiveCalls(VisibilityInterface::VISIBLE, VisibilityInterface::HIDDEN);

        $expectedCount = 9;
        $values = $this->runIndexationAndSearch($expectedCount);

        $this->assertCount($expectedCount, $values);
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

    public function testRestrictIndexEntityEventListenerWhenProductFallBackIsHiddenAndCategoryFallBackIsVisible()
    {
        $this->configManager
            ->expects($this->exactly(2))
            ->method('get')
            ->withConsecutive(
                [self::PRODUCT_VISIBILITY_CONFIGURATION_PATH],
                [self::CATEGORY_VISIBILITY_CONFIGURATION_PATH]
            )
            ->willReturnOnConsecutiveCalls(VisibilityInterface::HIDDEN, VisibilityInterface::VISIBLE);

        $expectedCount = 7;
        $values = $this->runIndexationAndSearch($expectedCount);

        $this->assertCount($expectedCount, $values);
        $this->assertSearchItems('product-1', $values[0]);
        $this->assertSearchItems('product-2', $values[1]);
        $this->assertSearchItems('product-3', $values[2]);
        $this->assertSearchItems('product-4', $values[3]);
        $this->assertSearchItems('product-5', $values[4]);
        $this->assertSearchItems('product-8', $values[5]);
        $this->assertSearchItems('продукт-7', $values[6]);
    }

    /**
     * @param mixed $expectedValue
     * @param Item $value
     */
    protected function assertSearchItems($expectedValue, Item $value)
    {
        $selectedData = $value->getSelectedData();
        $field = 'names_' . $this->getDefaultLocalizationId();

        if (!array_key_exists($field, $selectedData)) {
            throw new \RuntimeException(
                sprintf('Field "%s" could not be found in selected data array', $field)
            );
        }

        $this->assertStringStartsWith($expectedValue, $selectedData[$field]);
    }

    /**
     * {@inheritdoc}
     */
    protected function getRestrictEntityEventName()
    {
        return sprintf('%s.%s', RestrictIndexEntityEvent::NAME, 'product');
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
