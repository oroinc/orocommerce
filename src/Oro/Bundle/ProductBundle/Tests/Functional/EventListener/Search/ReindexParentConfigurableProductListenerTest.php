<?php

namespace Oro\Bundle\ProductBundle\Tests\Functional\EventListener\Search;

use Oro\Bundle\MessageQueueBundle\Test\Functional\MessageQueueAssertTrait;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\ProductBundle\Tests\Functional\DataFixtures\LoadConfigurableProductWithVariants;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;
use Oro\Bundle\WebsiteSearchBundle\Async\Topic\WebsiteSearchReindexTopic;
use Oro\Bundle\WebsiteSearchBundle\Tests\Functional\Traits\DefaultWebsiteIdTestTrait;
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
        self::assertEmptyMessages(WebsiteSearchReindexTopic::getName());

        /** @var Product $productVariant */
        $productVariant = $this->getReference(LoadConfigurableProductWithVariants::FIRST_VARIANT_SKU);
        $productVariant->setSku($productVariant->getSku() . '_changed');

        /** @var Product $configurableProduct */
        $configurableProduct = $this->getReference(LoadConfigurableProductWithVariants::CONFIGURABLE_SKU);

        self::getContainer()->get('oro_entity.doctrine_helper')
            ->getEntityManagerForClass(Product::class)
            ->flush();

        self::assertMessagesCount(WebsiteSearchReindexTopic::getName(), 1);
        self::assertMessageSent(
            WebsiteSearchReindexTopic::getName(),
            [
                'class' => [Product::class],
                'granulize' => true,
                'context' => [
                    'websiteIds' => [self::getDefaultWebsiteId()],
                    'entityIds' => [$productVariant->getId(), $configurableProduct->getId()],
                ],
            ]
        );
        self::assertMessageSentWithPriority(WebsiteSearchReindexTopic::getName(), MessagePriority::LOW);
    }
}
