<?php

declare(strict_types=1);

namespace Oro\Bundle\VisibilityBundle\Tests\Functional\Async\Visibility;

use Oro\Bundle\CatalogBundle\Tests\Functional\DataFixtures\LoadCategoryProductData;
use Oro\Bundle\ConfigBundle\Tests\Functional\Traits\ConfigManagerAwareTestTrait;
use Oro\Bundle\CustomerBundle\Entity\CustomerUser;
use Oro\Bundle\CustomerBundle\Tests\Functional\DataFixtures\LoadCustomerUserData;
use Oro\Bundle\MessageQueueBundle\Test\Functional\MessageQueueExtension;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\ProductBundle\Tests\Functional\DataFixtures\LoadProductData;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;
use Oro\Bundle\VisibilityBundle\Async\Topic\ResolveProductVisibilityTopic;
use Oro\Bundle\VisibilityBundle\Entity\Visibility\CustomerGroupProductVisibility;
use Oro\Bundle\VisibilityBundle\Entity\Visibility\CustomerProductVisibility;
use Oro\Bundle\VisibilityBundle\Entity\Visibility\ProductVisibility;
use Oro\Bundle\VisibilityBundle\Entity\Visibility\VisibilityInterface;
use Oro\Bundle\VisibilityBundle\Tests\Functional\VisibilityAwareTestTrait;
use Oro\Component\MessageQueue\Consumption\MessageProcessorInterface;
use PHPUnit\Framework\Constraint\Constraint;

/**
 * @dbIsolationPerTest
 *
 * @SuppressWarnings(PHPMD.TooManyPublicMethods)
 */
class ProductVisibilityProcessorTest extends WebTestCase
{
    use MessageQueueExtension;
    use ConfigManagerAwareTestTrait;
    use VisibilityAwareTestTrait;

    protected function setUp(): void
    {
        $this->initClient();
        $this->loadFixtures([
            LoadProductData::class,
            LoadCategoryProductData::class,
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

    /**
     * @dataProvider processVisibilityDataProvider
     */
    public function testProcessWhenProductVisibility(
        string $productReference,
        string $visibility,
        Constraint $expected
    ): void {
        /** @var Product $product */
        $product = $this->getReference($productReference);
        /** @var CustomerUser $customerUser */
        $customerUser = $this->getReference(LoadCustomerUserData::EMAIL);

        self::assertProductVisibility(self::isTrue(), $product, $customerUser);

        $productVisibility = self::createProductVisibility($product, $visibility);

        // Ensures MQ is empty before adding the message we are going to consume.
        self::purgeMessageQueue();
        self::clearMessageCollector();

        $sentMessage = self::sendMessage(
            ResolveProductVisibilityTopic::getName(),
            ['entity_class_name' => ProductVisibility::class, 'id' => $productVisibility->getId()]
        );
        self::consume(1);
        self::assertProcessedMessageStatus(MessageProcessorInterface::ACK, $sentMessage);
        self::assertProcessedMessageProcessor(
            'oro_visibility.async.visibility.product_visibility_processor',
            $sentMessage
        );

        self::assertProductVisibility($expected, $product, $customerUser);
    }

    public function processVisibilityDataProvider(): array
    {
        return [
            'visible' => [
                'productReference' => LoadProductData::PRODUCT_1,
                'visibility' => VisibilityInterface::VISIBLE,
                'expected' => self::isTrue(),
            ],
            'hidden' => [
                'productReference' => LoadProductData::PRODUCT_1,
                'visibility' => VisibilityInterface::HIDDEN,
                'expected' => self::isFalse(),
            ],
        ];
    }

    public function testProcessWhenProductVisibilityIsConfig(): void
    {
        /** @var Product $product */
        $product = $this->getReference(LoadProductData::PRODUCT_1);
        /** @var CustomerUser $customerUser */
        $customerUser = $this->getReference(LoadCustomerUserData::EMAIL);

        self::assertProductVisibility(self::isTrue(), $product, $customerUser);

        $productVisibility = self::createProductVisibility($product, ProductVisibility::CONFIG);

        self::getConfigManager()->set('oro_visibility.product_visibility', VisibilityInterface::HIDDEN);
        self::getConfigManager()->flush();

        // Ensures MQ is empty before adding the message we are going to consume.
        self::purgeMessageQueue();
        self::clearMessageCollector();

        $sentMessage = self::sendMessage(
            ResolveProductVisibilityTopic::getName(),
            ['entity_class_name' => ProductVisibility::class, 'id' => $productVisibility->getId()]
        );
        self::consume(1);
        self::assertProcessedMessageStatus(MessageProcessorInterface::ACK, $sentMessage);
        self::assertProcessedMessageProcessor(
            'oro_visibility.async.visibility.product_visibility_processor',
            $sentMessage
        );

        self::assertProductVisibility(self::isFalse(), $product, $customerUser);
    }

    public function testProcessWhenProductVisibilityIsCategory(): void
    {
        /** @var Product $product */
        $product = $this->getReference(LoadProductData::PRODUCT_1);
        /** @var CustomerUser $customerUser */
        $customerUser = $this->getReference(LoadCustomerUserData::EMAIL);

        self::assertProductVisibility(self::isTrue(), $product, $customerUser);

        self::getConfigManager()->set('oro_visibility.category_visibility', VisibilityInterface::HIDDEN);
        self::getConfigManager()->flush();

        // Ensures MQ is empty before adding the message we are going to consume.
        self::purgeMessageQueue();
        self::clearMessageCollector();

        $sentMessage = self::sendMessage(
            ResolveProductVisibilityTopic::getName(),
            [
                'entity_class_name' => ProductVisibility::class,
                'target_class_name' => Product::class,
                'target_id' => $product->getId(),
                'scope_id' => self::getScopeForProductVisibility()->getId(),
            ]
        );
        self::consume(1);
        self::assertProcessedMessageStatus(MessageProcessorInterface::ACK, $sentMessage);
        self::assertProcessedMessageProcessor(
            'oro_visibility.async.visibility.product_visibility_processor',
            $sentMessage
        );

        self::assertProductVisibility(self::isFalse(), $product, $customerUser);
    }

    /**
     * @dataProvider processVisibilityDataProvider
     */
    public function testProcessWhenCustomerGroupProductVisibility(
        string $productReference,
        string $visibility,
        Constraint $expected
    ): void {
        /** @var Product $product */
        $product = $this->getReference($productReference);
        /** @var CustomerUser $customerUser */
        $customerUser = $this->getReference(LoadCustomerUserData::EMAIL);

        self::assertProductVisibility(self::isTrue(), $product, $customerUser);

        $customerGroupProductVisibility = self::createCustomerGroupProductVisibility(
            $product,
            $customerUser->getCustomer()->getGroup(),
            $visibility
        );

        // Ensures MQ is empty before adding the message we are going to consume.
        self::purgeMessageQueue();
        self::clearMessageCollector();

        $sentMessage = self::sendMessage(
            ResolveProductVisibilityTopic::getName(),
            [
                'entity_class_name' => CustomerGroupProductVisibility::class,
                'id' => $customerGroupProductVisibility->getId(),
            ]
        );
        self::consume(1);
        self::assertProcessedMessageStatus(MessageProcessorInterface::ACK, $sentMessage);
        self::assertProcessedMessageProcessor(
            'oro_visibility.async.visibility.product_visibility_processor',
            $sentMessage
        );

        self::assertProductVisibility($expected, $product, $customerUser);
    }

    public function testProcessWhenCustomerGroupProductVisibilityIsCategory(): void
    {
        /** @var Product $product */
        $product = $this->getReference(LoadProductData::PRODUCT_1);
        /** @var CustomerUser $customerUser */
        $customerUser = $this->getReference(LoadCustomerUserData::EMAIL);

        self::assertProductVisibility(self::isTrue(), $product, $customerUser);

        self::getConfigManager()->set('oro_visibility.category_visibility', VisibilityInterface::HIDDEN);
        self::getConfigManager()->flush();

        $customerGroupProductVisibility = self::createCustomerGroupProductVisibility(
            $product,
            $customerUser->getCustomer()->getGroup(),
            CustomerGroupProductVisibility::CATEGORY
        );

        // Ensures MQ is empty before adding the message we are going to consume.
        self::purgeMessageQueue();
        self::clearMessageCollector();

        $sentMessage = self::sendMessage(
            ResolveProductVisibilityTopic::getName(),
            [
                'entity_class_name' => CustomerGroupProductVisibility::class,
                'id' => $customerGroupProductVisibility->getId(),
            ]
        );
        self::consume(1);
        self::assertProcessedMessageStatus(MessageProcessorInterface::ACK, $sentMessage);
        self::assertProcessedMessageProcessor(
            'oro_visibility.async.visibility.product_visibility_processor',
            $sentMessage
        );

        self::assertProductVisibility(self::isFalse(), $product, $customerUser);
    }

    public function testProcessWhenCustomerGroupProductVisibilityIsCurrentProduct(): void
    {
        /** @var Product $product */
        $product = $this->getReference(LoadProductData::PRODUCT_1);
        /** @var CustomerUser $customerUser */
        $customerUser = $this->getReference(LoadCustomerUserData::EMAIL);

        self::assertProductVisibility(self::isTrue(), $product, $customerUser);

        self::createProductVisibility($product, VisibilityInterface::HIDDEN);
        self::getContainer()
            ->get('oro_visibility.visibility.cache.cache_builder')
            ->buildCache();

        // Ensures MQ is empty before adding the message we are going to consume.
        self::purgeMessageQueue();
        self::clearMessageCollector();

        $sentMessage = self::sendMessage(
            ResolveProductVisibilityTopic::getName(),
            [
                'entity_class_name' => CustomerGroupProductVisibility::class,
                'target_class_name' => Product::class,
                'target_id' => $product->getId(),
                'scope_id' => self::getScopeForCustomerGroupVisibility($customerUser->getCustomer()->getGroup())
                    ->getId(),
            ]
        );
        self::consume(1);
        self::assertProcessedMessageStatus(MessageProcessorInterface::ACK, $sentMessage);
        self::assertProcessedMessageProcessor(
            'oro_visibility.async.visibility.product_visibility_processor',
            $sentMessage
        );

        self::assertProductVisibility(self::isFalse(), $product, $customerUser);
    }

    /**
     * @dataProvider processVisibilityDataProvider
     */
    public function testProcessWhenCustomerProductVisibility(
        string $productReference,
        string $visibility,
        Constraint $expected
    ): void {
        /** @var Product $product */
        $product = $this->getReference($productReference);
        /** @var CustomerUser $customerUser */
        $customerUser = $this->getReference(LoadCustomerUserData::EMAIL);

        self::assertProductVisibility(self::isTrue(), $product, $customerUser);

        $customerProductVisibility = self::createCustomerProductVisibility(
            $product,
            $customerUser->getCustomer(),
            $visibility
        );

        // Ensures MQ is empty before adding the message we are going to consume.
        self::purgeMessageQueue();
        self::clearMessageCollector();

        $sentMessage = self::sendMessage(
            ResolveProductVisibilityTopic::getName(),
            [
                'entity_class_name' => CustomerProductVisibility::class,
                'id' => $customerProductVisibility->getId(),
            ]
        );
        self::consume(1);
        self::assertProcessedMessageStatus(MessageProcessorInterface::ACK, $sentMessage);
        self::assertProcessedMessageProcessor(
            'oro_visibility.async.visibility.product_visibility_processor',
            $sentMessage
        );

        self::assertProductVisibility($expected, $product, $customerUser);
    }

    public function testProcessWhenCustomerProductVisibilityIsCategory(): void
    {
        /** @var Product $product */
        $product = $this->getReference(LoadProductData::PRODUCT_1);
        /** @var CustomerUser $customerUser */
        $customerUser = $this->getReference(LoadCustomerUserData::EMAIL);

        self::assertProductVisibility(self::isTrue(), $product, $customerUser);

        self::getConfigManager()->set('oro_visibility.category_visibility', VisibilityInterface::HIDDEN);
        self::getConfigManager()->flush();

        $customerProductVisibility = self::createCustomerProductVisibility(
            $product,
            $customerUser->getCustomer(),
            CustomerProductVisibility::CATEGORY
        );

        // Ensures MQ is empty before adding the message we are going to consume.
        self::purgeMessageQueue();
        self::clearMessageCollector();

        $sentMessage = self::sendMessage(
            ResolveProductVisibilityTopic::getName(),
            [
                'entity_class_name' => CustomerProductVisibility::class,
                'id' => $customerProductVisibility->getId(),
            ]
        );
        self::consume(1);
        self::assertProcessedMessageStatus(MessageProcessorInterface::ACK, $sentMessage);
        self::assertProcessedMessageProcessor(
            'oro_visibility.async.visibility.product_visibility_processor',
            $sentMessage
        );

        self::assertProductVisibility(self::isFalse(), $product, $customerUser);
    }

    public function testProcessWhenCustomerProductVisibilityIsCurrentProduct(): void
    {
        /** @var Product $product */
        $product = $this->getReference(LoadProductData::PRODUCT_1);
        /** @var CustomerUser $customerUser */
        $customerUser = $this->getReference(LoadCustomerUserData::EMAIL);

        self::assertProductVisibility(self::isTrue(), $product, $customerUser);

        self::createProductVisibility($product, VisibilityInterface::HIDDEN);
        self::getContainer()
            ->get('oro_visibility.visibility.cache.cache_builder')
            ->buildCache();

        $customerProductVisibility = self::createCustomerProductVisibility(
            $product,
            $customerUser->getCustomer(),
            CustomerProductVisibility::CURRENT_PRODUCT
        );

        // Ensures MQ is empty before adding the message we are going to consume.
        self::purgeMessageQueue();
        self::clearMessageCollector();

        $sentMessage = self::sendMessage(
            ResolveProductVisibilityTopic::getName(),
            [
                'entity_class_name' => CustomerProductVisibility::class,
                'id' => $customerProductVisibility->getId(),
            ]
        );
        self::consume(1);
        self::assertProcessedMessageStatus(MessageProcessorInterface::ACK, $sentMessage);
        self::assertProcessedMessageProcessor(
            'oro_visibility.async.visibility.product_visibility_processor',
            $sentMessage
        );

        self::assertProductVisibility(self::isFalse(), $product, $customerUser);
    }

    public function testProcessWhenCustomerGroupProductVisibilityIsCustomerGroup(): void
    {
        /** @var Product $product */
        $product = $this->getReference(LoadProductData::PRODUCT_1);
        /** @var CustomerUser $customerUser */
        $customerUser = $this->getReference(LoadCustomerUserData::EMAIL);

        self::assertProductVisibility(self::isTrue(), $product, $customerUser);

        self::createCustomerGroupProductVisibility(
            $product,
            $customerUser->getCustomer()->getGroup(),
            VisibilityInterface::HIDDEN
        );
        self::getContainer()
            ->get('oro_visibility.visibility.cache.cache_builder')
            ->buildCache();

        // Ensures MQ is empty before adding the message we are going to consume.
        self::purgeMessageQueue();
        self::clearMessageCollector();

        $sentMessage = self::sendMessage(
            ResolveProductVisibilityTopic::getName(),
            [
                'entity_class_name' => CustomerProductVisibility::class,
                'target_class_name' => Product::class,
                'target_id' => $product->getId(),
                'scope_id' => self::getScopeForCustomerVisibility($customerUser->getCustomer())->getId(),
            ]
        );
        self::consume(1);
        self::assertProcessedMessageStatus(MessageProcessorInterface::ACK, $sentMessage);
        self::assertProcessedMessageProcessor(
            'oro_visibility.async.visibility.product_visibility_processor',
            $sentMessage
        );

        self::assertProductVisibility(self::isFalse(), $product, $customerUser);
    }
}
