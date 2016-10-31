<?php

namespace Oro\Bundle\CustomerBundle\Tests\Functional\EventListener;

use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\HttpFoundation\Request;

use Oro\Bundle\ConfigBundle\Config\ConfigManager;
use Oro\Bundle\CustomerBundle\Entity\Visibility\VisibilityInterface;
use Oro\Bundle\CustomerBundle\EventListener\RestrictProductsIndexEventListener;
use Oro\Bundle\CustomerBundle\Tests\Functional\DataFixtures\LoadProductVisibilityData;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\SearchBundle\Query\Query;
use Oro\Bundle\SearchBundle\Query\Result;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;
use Oro\Bundle\WebsiteSearchBundle\Engine\AbstractIndexer;
use Oro\Bundle\WebsiteSearchBundle\Event\RestrictIndexEntityEvent;
use Oro\Bundle\WebsiteSearchBundle\Tests\Functional\Traits\DefaultWebsiteIdTestTrait;

/**
 * @dbIsolationPerTest
 */
class RestrictProductsIndexEventListenerTest extends WebTestCase
{
    use DefaultWebsiteIdTestTrait;

    const PRODUCT_VISIBILITY_CONFIGURATION_PATH = 'oro_customer.product_visibility';
    const CATEGORY_VISIBILITY_CONFIGURATION_PATH = 'oro_customer.category_visibility';

    /** @var ConfigManager|\PHPUnit_Framework_MockObject_MockObject */
    private $configManager;

    /** @var EventDispatcherInterface */
    private $dispatcher;

    protected function setUp()
    {
        $this->initClient();
        $this->getContainer()->get('request_stack')->push(Request::create(''));
        $this->dispatcher = $this->getContainer()->get('event_dispatcher');

        $this->configManager = $this->getMockBuilder(ConfigManager::class)
            ->disableOriginalConstructor()
            ->getMock();

        $listener = new RestrictProductsIndexEventListener(
            $this->getContainer()->get('oro_entity.doctrine_helper'),
            $this->configManager,
            self::PRODUCT_VISIBILITY_CONFIGURATION_PATH,
            self::CATEGORY_VISIBILITY_CONFIGURATION_PATH
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

        $this->getContainer()->get('oro_customer.visibility.cache.product.cache_builder')->buildCache();
    }

    /**
     * @return Result\Item[]
     */
    private function runIndexationAndSearch()
    {
        $indexer = $this->getContainer()->get('oro_website_search.indexer');
        $searchEngine = $this->getContainer()->get('oro_website_search.engine');
        $indexer->reindex(
            Product::class,
            [
                AbstractIndexer::CONTEXT_WEBSITE_IDS => [$this->getDefaultWebsiteId()]
            ]
        );

        $query = new Query();
        $query->from('oro_product_WEBSITE_ID');
        $query->select('recordTitle');
        $query->getCriteria()->orderBy(['title_' . $this->getDefaultWebsiteId() => Query::ORDER_ASC]);

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

        $values = $this->runIndexationAndSearch();

        $this->assertCount(8, $values);
        $this->assertStringStartsWith('product.1', $values[0]->getRecordTitle());
        $this->assertStringStartsWith('product.2', $values[1]->getRecordTitle());
        $this->assertStringStartsWith('product.3', $values[2]->getRecordTitle());
        $this->assertStringStartsWith('product.4', $values[3]->getRecordTitle());
        $this->assertStringStartsWith('product.5', $values[4]->getRecordTitle());
        $this->assertStringStartsWith('product.6', $values[5]->getRecordTitle());
        $this->assertStringStartsWith('product.7', $values[6]->getRecordTitle());
        $this->assertStringStartsWith('product.8', $values[7]->getRecordTitle());
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

        $values = $this->runIndexationAndSearch();

        $this->assertCount(5, $values);
        $this->assertStringStartsWith('product.1', $values[0]->getRecordTitle());
        $this->assertStringStartsWith('product.2', $values[1]->getRecordTitle());
        $this->assertStringStartsWith('product.3', $values[2]->getRecordTitle());
        $this->assertStringStartsWith('product.4', $values[3]->getRecordTitle());
        $this->assertStringStartsWith('product.5', $values[4]->getRecordTitle());
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

        $values = $this->runIndexationAndSearch();

        $this->assertCount(5, $values);
        $this->assertStringStartsWith('product.1', $values[0]->getRecordTitle());
        $this->assertStringStartsWith('product.2', $values[1]->getRecordTitle());
        $this->assertStringStartsWith('product.3', $values[2]->getRecordTitle());
        $this->assertStringStartsWith('product.4', $values[3]->getRecordTitle());
        $this->assertStringStartsWith('product.5', $values[4]->getRecordTitle());
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

        $values = $this->runIndexationAndSearch();

        $this->assertCount(8, $values);
        $this->assertStringStartsWith('product.1', $values[0]->getRecordTitle());
        $this->assertStringStartsWith('product.2', $values[1]->getRecordTitle());
        $this->assertStringStartsWith('product.3', $values[2]->getRecordTitle());
        $this->assertStringStartsWith('product.4', $values[3]->getRecordTitle());
        $this->assertStringStartsWith('product.5', $values[4]->getRecordTitle());
        $this->assertStringStartsWith('product.6', $values[5]->getRecordTitle());
        $this->assertStringStartsWith('product.7', $values[6]->getRecordTitle());
        $this->assertStringStartsWith('product.8', $values[7]->getRecordTitle());
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
