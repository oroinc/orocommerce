<?php

declare(strict_types=1);

namespace Oro\Bundle\VisibilityBundle\Tests\Functional\Async\Visibility;

use Oro\Bundle\CatalogBundle\Entity\Category;
use Oro\Bundle\CatalogBundle\Tests\Functional\DataFixtures\LoadCategoryData;
use Oro\Bundle\ConfigBundle\Tests\Functional\Traits\ConfigManagerAwareTestTrait;
use Oro\Bundle\CustomerBundle\Entity\CustomerUser;
use Oro\Bundle\CustomerBundle\Tests\Functional\DataFixtures\LoadCustomerUserData;
use Oro\Bundle\MessageQueueBundle\Test\Functional\MessageQueueExtension;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\ProductBundle\Tests\Functional\DataFixtures\LoadProductData;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;
use Oro\Bundle\VisibilityBundle\Async\Topic\VisibilityOnChangeProductCategoryTopic;
use Oro\Bundle\VisibilityBundle\Entity\Visibility\VisibilityInterface;
use Oro\Bundle\VisibilityBundle\Tests\Functional\VisibilityAwareTestTrait;
use Oro\Bundle\WebsiteSearchBundle\Async\Topic\WebsiteSearchReindexTopic;
use Oro\Bundle\WebsiteSearchBundle\Engine\AbstractIndexer;
use Oro\Component\MessageQueue\Consumption\MessageProcessorInterface;

/**
 * @dbIsolationPerTest
 */
class ProductProcessorTest extends WebTestCase
{
    use MessageQueueExtension;
    use ConfigManagerAwareTestTrait;
    use VisibilityAwareTestTrait;

    protected function setUp(): void
    {
        $this->initClient();
        $this->loadFixtures([
            LoadProductData::class,
            LoadCategoryData::class,
            LoadCustomerUserData::class,
        ]);

        self::getConfigManager()->set('oro_visibility.product_visibility', VisibilityInterface::VISIBLE);
        self::getConfigManager()->set('oro_visibility.category_visibility', VisibilityInterface::VISIBLE);
        self::getConfigManager()->flush();

        self::getContainer()
            ->get('oro_visibility.visibility.cache.cache_builder')
            ->buildCache();
    }

    protected function tearDown(): void
    {
        self::getConfigManager()->set('oro_visibility.product_visibility', VisibilityInterface::VISIBLE);
        self::getConfigManager()->set('oro_visibility.category_visibility', VisibilityInterface::VISIBLE);
        self::getConfigManager()->flush();

        parent::tearDown();
    }

    public function testProcess(): void
    {
        /** @var Product $product8 */
        $product8 = $this->getReference(LoadProductData::PRODUCT_8);
        /** @var CustomerUser $customerUser */
        $customerUser = $this->getReference(LoadCustomerUserData::EMAIL);
        /** @var Category $category */
        $category = $this->getReference(LoadCategoryData::FIRST_LEVEL);
        self::createCategoryVisibility($category, VisibilityInterface::HIDDEN);
        self::getContainer()
            ->get('oro_visibility.visibility.cache.cache_builder')
            ->buildCache();

        self::assertProductVisibility(self::isTrue(), $product8, $customerUser);

        $this->changeCategory($product8, $category);

        // Ensures MQ is empty before adding the message we are going to consume.
        self::purgeMessageQueue();
        self::clearMessageCollector();

        $sentMessage = self::sendMessage(
            VisibilityOnChangeProductCategoryTopic::getName(),
            ['id' => $product8->getId()]
        );
        self::consume(1);
        self::assertProcessedMessageStatus(MessageProcessorInterface::ACK, $sentMessage);
        self::assertProcessedMessageProcessor('oro_visibility.async.visibility.product_processor', $sentMessage);

        self::assertMessagesEmpty(WebsiteSearchReindexTopic::getName());

        self::assertProductVisibility(self::isFalse(), $product8, $customerUser);
    }

    public function testProcessWhenScheduleReindex(): void
    {
        /** @var Product $product8 */
        $product8 = $this->getReference(LoadProductData::PRODUCT_8);
        /** @var CustomerUser $customerUser */
        $customerUser = $this->getReference(LoadCustomerUserData::EMAIL);
        /** @var Category $category */
        $category = $this->getReference(LoadCategoryData::FIRST_LEVEL);
        self::createCategoryVisibility($category, VisibilityInterface::HIDDEN);
        self::getContainer()
            ->get('oro_visibility.visibility.cache.cache_builder')
            ->buildCache();

        self::assertProductVisibility(self::isTrue(), $product8, $customerUser);

        $this->changeCategory($product8, $category);

        // Ensures MQ is empty before adding the message we are going to consume.
        self::purgeMessageQueue();
        self::clearMessageCollector();

        $sentMessage = self::sendMessage(
            VisibilityOnChangeProductCategoryTopic::getName(),
            ['id' => $product8->getId(), 'scheduleReindex' => true]
        );
        self::consume(1);
        self::assertProcessedMessageStatus(MessageProcessorInterface::ACK, $sentMessage);
        self::assertProcessedMessageProcessor('oro_visibility.async.visibility.product_processor', $sentMessage);

        self::assertMessageSent(WebsiteSearchReindexTopic::getName(), [
            'class' => [Product::class],
            'granulize' => true,
            'context' => [
                AbstractIndexer::CONTEXT_WEBSITE_IDS => [self::getDefaultWebsiteId()],
                AbstractIndexer::CONTEXT_ENTITIES_IDS_KEY => [$product8->getId()],
                AbstractIndexer::CONTEXT_FIELD_GROUPS => ['visibility'],
            ],
        ]);

        self::assertProductVisibility(self::isFalse(), $product8, $customerUser);
    }

    private function changeCategory(Product $product, Category $category): void
    {
        $product->setCategory($category);
        $entityManager = self::getContainer()
            ->get('doctrine')
            ->getManagerForClass(Product::class);
        $entityManager->persist($product);
        $entityManager->flush();
    }
}
