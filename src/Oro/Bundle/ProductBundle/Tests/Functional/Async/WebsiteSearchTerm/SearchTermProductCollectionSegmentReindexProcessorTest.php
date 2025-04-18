<?php

namespace Oro\Bundle\ProductBundle\Tests\Functional\Async\WebsiteSearchTerm;

use Oro\Bundle\MessageQueueBundle\Test\Functional\MessageQueueExtension;
use Oro\Bundle\ProductBundle\Async\Topic\SearchTermProductCollectionSegmentReindexTopic;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\ProductBundle\Tests\Functional\DataFixtures\LoadProductCollectionData;
use Oro\Bundle\ProductBundle\Tests\Functional\DataFixtures\LoadSearchTermData;
use Oro\Bundle\SearchBundle\Query\Criteria\Comparison;
use Oro\Bundle\SearchBundle\Query\Criteria\Criteria;
use Oro\Bundle\SearchBundle\Query\Query;
use Oro\Bundle\SearchBundle\Query\Result;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;
use Oro\Bundle\WebsiteSearchBundle\Placeholder\WebsiteIdPlaceholder;
use Oro\Bundle\WebsiteSearchBundle\Tests\Functional\WebsiteSearchExtensionTrait;
use Oro\Component\MessageQueue\Consumption\MessageProcessorInterface;

/**
 * @dbIsolationPerTest
 */
class SearchTermProductCollectionSegmentReindexProcessorTest extends WebTestCase
{
    use MessageQueueExtension;
    use WebsiteSearchExtensionTrait;

    #[\Override]
    protected function setUp(): void
    {
        $this->initClient();

        $this->loadFixtures([LoadSearchTermData::class]);

        self::purgeMessageQueue();
    }

    public function testProcessWhenNoSearchTerm(): void
    {
        self::resetIndex(Product::class);

        self::assertMessagesEmpty(SearchTermProductCollectionSegmentReindexTopic::getName());

        $message = self::sendMessage(SearchTermProductCollectionSegmentReindexTopic::getName(), [
            SearchTermProductCollectionSegmentReindexTopic::SEARCH_TERM_ID => self::BIGINT,
        ]);

        self::consume();

        self::assertProcessedMessageStatus(MessageProcessorInterface::REJECT, $message);

        self::assertProcessedMessageProcessor(
            'oro_product.async.website_search_term.search_term_product_collection_segment_reindex',
            $message
        );
    }

    public function testProcessWhenNoProductCollectionSegment(): void
    {
        self::resetIndex(Product::class);

        self::assertMessagesEmpty(SearchTermProductCollectionSegmentReindexTopic::getName());

        self::ensureItemsLoaded(Product::class, 0);

        $searchTerm = $this->getReference(LoadSearchTermData::REDIRECT_TO_URI);

        $message = self::sendMessage(SearchTermProductCollectionSegmentReindexTopic::getName(), [
            SearchTermProductCollectionSegmentReindexTopic::SEARCH_TERM_ID => $searchTerm->getId(),
        ]);

        self::consume();

        self::assertProcessedMessageStatus(MessageProcessorInterface::REJECT, $message);

        self::assertProcessedMessageProcessor(
            'oro_product.async.website_search_term.search_term_product_collection_segment_reindex',
            $message
        );
    }

    public function testProcess(): void
    {
        self::resetIndex(Product::class);

        self::assertMessagesEmpty(SearchTermProductCollectionSegmentReindexTopic::getName());

        self::ensureItemsLoaded(Product::class, 0);

        $searchTerm = $this->getReference(LoadSearchTermData::SHOW_PRODUCT_COLLECTION);

        $message = self::sendMessage(SearchTermProductCollectionSegmentReindexTopic::getName(), [
            SearchTermProductCollectionSegmentReindexTopic::SEARCH_TERM_ID => $searchTerm->getId(),
        ]);

        self::consume();

        self::assertProcessedMessageStatus(MessageProcessorInterface::ACK, $message);

        self::assertProcessedMessageProcessor(
            'oro_product.async.website_search_term.search_term_product_collection_segment_reindex',
            $message
        );

        self::ensureItemsLoaded(Product::class, 7);

        $websiteId = self::getDefaultWebsiteId();
        $alias = self::getIndexAlias(Product::class, [WebsiteIdPlaceholder::NAME => $websiteId]);

        $query = (new Query())
            ->from($alias)
            ->select('assigned_to.search_term_' . $searchTerm->getId());

        $criteria = new Criteria();
        $whereExpression = new Comparison('sku', Comparison::EQ, LoadProductCollectionData::PRODUCT);
        $criteria->where($whereExpression);
        $query->setCriteria($criteria);

        /** @var Result $result */
        $result = self::getContainer()->get('oro_website_search.engine')->search($query);
        self::assertEquals(1, $result->getRecordsCount());
    }
}
