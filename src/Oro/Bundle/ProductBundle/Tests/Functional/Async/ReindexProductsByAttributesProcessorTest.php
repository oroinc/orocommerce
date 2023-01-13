<?php

namespace Oro\Bundle\ProductBundle\Tests\Functional\Async;

use Oro\Bundle\EntityConfigBundle\Attribute\Entity\AttributeGroupRelation;
use Oro\Bundle\MessageQueueBundle\Test\Functional\MessageQueueExtension;
use Oro\Bundle\ProductBundle\Async\ReindexProductsByAttributesProcessor;
use Oro\Bundle\ProductBundle\Async\Topic\ReindexProductsByAttributesTopic;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\ProductBundle\Tests\Functional\DataFixtures\LoadProductCollectionData;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;
use Oro\Bundle\WebsiteSearchBundle\Tests\Functional\WebsiteSearchExtensionTrait;
use Oro\Component\MessageQueue\Consumption\MessageProcessorInterface;

class ReindexProductsByAttributesProcessorTest extends WebTestCase
{
    use MessageQueueExtension;
    use WebsiteSearchExtensionTrait;

    private ReindexProductsByAttributesProcessor $processor;

    protected function setUp(): void
    {
        $this->initClient();
        self::purgeMessageQueue();
        $this->loadFixtures([LoadProductCollectionData::class]);

        $this->processor = self::getContainer()->get('oro_product.async.reindex_products_by_attributes_processor');
    }

    public function testProcess(): void
    {
        self::resetIndex(Product::class);

        self::assertMessagesEmpty(ReindexProductsByAttributesTopic::getName());

        self::ensureItemsLoaded(Product::class, 0);

        /** @var AttributeGroupRelation $attr */
        $attr = self::getContainer()->get('doctrine')
            ->getRepository(AttributeGroupRelation::class)
            ->findOneBy([]);

        $entityConfigFieldId = $attr->getEntityConfigFieldId();

        /** @var Product[] $products */
        $products = self::getContainer()->get('doctrine')
            ->getRepository(Product::class)
            ->getProductIdsByAttributesId([$entityConfigFieldId]);

        $message = self::sendMessage(ReindexProductsByAttributesTopic::getName(), [
            ReindexProductsByAttributesTopic::ATTRIBUTE_IDS_OPTION => [$entityConfigFieldId],
        ]);

        self::consume();

        self::assertProcessedMessageStatus(MessageProcessorInterface::ACK, $message);

        self::assertProcessedMessageProcessor(
            'oro_product.async.reindex_products_by_attributes_processor',
            $message
        );

        self::ensureItemsLoaded(Product::class, count($products));
    }

    public function testProcessWithEmptyAttributeIds(): void
    {
        self::resetIndex(Product::class);

        self::assertMessagesEmpty(ReindexProductsByAttributesTopic::getName());

        self::ensureItemsLoaded(Product::class, 0);

        $message = self::sendMessage(ReindexProductsByAttributesTopic::getName(), []);

        self::consume();

        self::assertProcessedMessageStatus(MessageProcessorInterface::REJECT, $message);

        self::assertProcessedMessageProcessor(
            'oro_product.async.reindex_products_by_attributes_processor',
            $message
        );

        self::assertTrue(
            self::getLoggerTestHandler()->hasErrorThatContains(
                'Message is rejected. ' .
                'Message of topic "oro_product.reindex_products_by_attributes" has invalid body: []. ' .
                'The required option "attributeIds" is missing.'
            )
        );

        self::ensureItemsLoaded(Product::class, 0);
    }
}
