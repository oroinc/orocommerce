<?php

namespace Oro\Bundle\ProductBundle\Tests\Functional\EventListener\Search;

use Oro\Bundle\MessageQueueBundle\Test\Functional\MessageQueueAssertTrait;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\ProductBundle\Tests\Functional\DataFixtures\LoadConfigurableProductWithVariants;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;
use Oro\Bundle\WebsiteSearchBundle\Engine\AsyncIndexer;
use Oro\Bundle\WebsiteSearchBundle\Tests\Functional\Traits\DefaultWebsiteIdTestTrait;
use Oro\Component\MessageQueue\Client\Message;
use Oro\Component\MessageQueue\Client\MessagePriority;

class ReindexParentConfigurableProductListenerTest extends WebTestCase
{
    use MessageQueueAssertTrait;
    use DefaultWebsiteIdTestTrait;

    protected function setUp(): void
    {
        parent::setUp();

        $this->initClient();
        $this->loadFixtures([LoadConfigurableProductWithVariants::class]);
    }

    public function testParentConfigurableProductReindexation()
    {
        self::assertEmptyMessages(AsyncIndexer::TOPIC_REINDEX);

        /** @var Product $productVariant */
        $productVariant = $this->getReference(LoadConfigurableProductWithVariants::FIRST_VARIANT_SKU);
        $productVariant->setSku($productVariant->getSku() . '_changed');

        /** @var Product $configurableProduct */
        $configurableProduct = $this->getReference(LoadConfigurableProductWithVariants::CONFIGURABLE_SKU);

        self::getContainer()->get('oro_entity.doctrine_helper')
            ->getEntityManagerForClass(Product::class)
            ->flush();

        self::assertMessagesCount(AsyncIndexer::TOPIC_REINDEX, 1);
        self::assertMessageSent(
            AsyncIndexer::TOPIC_REINDEX,
            new Message(
                [
                    'class' => [Product::class],
                    'granulize' => true,
                    'context' => [
                        'websiteIds' => [$this->getDefaultWebsiteId()],
                        'entityIds' => [$productVariant->getId(), $configurableProduct->getId()]
                    ],
                ],
                MessagePriority::LOW
            )
        );
    }
}
