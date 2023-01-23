<?php

namespace Oro\Bundle\ProductBundle\Tests\Functional\EventListener;

use Doctrine\ORM\EntityManagerInterface;
use Oro\Bundle\DataAuditBundle\Async\Topic\AuditChangedEntitiesTopic;
use Oro\Bundle\MessageQueueBundle\Test\Functional\MessageQueueExtension;
use Oro\Bundle\ProductBundle\Async\Topic\ResizeProductImageTopic;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\ProductBundle\Entity\ProductImage;
use Oro\Bundle\ProductBundle\Entity\ProductImageType;
use Oro\Bundle\ProductBundle\Tests\Functional\DataFixtures\LoadProductData;
use Oro\Bundle\RedirectBundle\Async\Topic\GenerateDirectUrlForEntitiesTopic;
use Oro\Bundle\SearchBundle\Async\Topic\IndexEntitiesByIdTopic;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;
use Oro\Bundle\WebsiteSearchBundle\Async\Topic\WebsiteSearchReindexTopic;
use Oro\Bundle\WebsiteSearchBundle\Engine\AbstractIndexer;
use Oro\Bundle\WebsiteSearchBundle\Tests\Functional\Traits\DefaultWebsiteIdTestTrait;
use Oro\Component\MessageQueue\Client\MessagePriority;
use Symfony\Component\HttpFoundation\File\File;

/**
 * @dbIsolationPerTest
 */
class ProductImageListenerTest extends WebTestCase
{
    use DefaultWebsiteIdTestTrait;
    use MessageQueueExtension;

    private EntityManagerInterface $em;

    protected function setUp(): void
    {
        $this->initClient();
        $this->client->useHashNavigation(true);

        $this->em = self::getContainer()->get('doctrine')->getManagerForClass(ProductImage::class);

        $optionalListenerManager = $this->getOptionalListenerManager();
        $optionalListenerManager->enableListener('oro_product.event_listener.product_image_resize_listener');
        $optionalListenerManager->enableListener('oro_redirect.event_listener.slug_prototype_change');
        $optionalListenerManager->enableListener('oro_redirect.event_listener.slug_change');
        $optionalListenerManager->enableListener('oro_dataaudit.listener.send_changed_entities_to_message_queue');

        $this->loadFixtures([LoadProductData::class]);
    }

    private function prepareProductImageResizeMessage(ProductImage $productImage): array
    {
        return [
            'productImageId' => $productImage->getId(),
            'force' => true,
            'dimensions' => null,
        ];
    }

    private function prepareProductsReindexMessage(array $products, array $expectedFieldGroups = null): array
    {
        $entityIds = [];
        /** @var Product $product */
        foreach ($products as $product) {
            $entityIds[] = $product->getId();
        }

        $context = [
            'entityIds' => $entityIds,
            'websiteIds' => [self::getDefaultWebsiteId()],
        ];
        if ($expectedFieldGroups) {
            $context[AbstractIndexer::CONTEXT_FIELD_GROUPS] = $expectedFieldGroups;
        }

        return [
            'class' => [Product::class],
            'granulize' => true,
            'context' => $context,
        ];
    }

    private function getProduct(string $reference): Product
    {
        return $this->getReference($reference);
    }

    private function getProductImage(Product $product): ProductImage
    {
        return $product->getImages()->first();
    }

    public function testCreateProductImage(): void
    {
        $product = $this->getProduct(LoadProductData::PRODUCT_1);
        $productImage = new ProductImage();
        $productImage->addType(ProductImageType::TYPE_MAIN);
        $productImage->setProduct($product);
        $this->em->persist($productImage);

        $this->em->flush();

        self::assertMessagesCount(ResizeProductImageTopic::getName(), 1);
        self::assertMessageSent(
            ResizeProductImageTopic::getName(),
            $this->prepareProductImageResizeMessage($productImage)
        );
    }

    public function testCreateProductImagesForSeveralProducts(): void
    {
        $product1 = $this->getProduct(LoadProductData::PRODUCT_1);
        $productImage1 = new ProductImage();
        $productImage1->addType(ProductImageType::TYPE_MAIN);
        $productImage1->setProduct($product1);
        $this->em->persist($productImage1);

        $product2 = $this->getProduct(LoadProductData::PRODUCT_2);
        $productImage2 = new ProductImage();
        $productImage2->addType(ProductImageType::TYPE_MAIN);
        $productImage2->setProduct($product2);
        $this->em->persist($productImage2);

        $product3 = $this->getProduct(LoadProductData::PRODUCT_3);
        $productImage3 = new ProductImage();
        $productImage3->addType(ProductImageType::TYPE_MAIN);
        $productImage3->setProduct($product3);
        $this->em->persist($productImage3);

        $this->em->flush();

        self::assertMessagesCount(ResizeProductImageTopic::getName(), 3);
        self::assertMessagesCount(WebsiteSearchReindexTopic::getName(), 1);

        self::assertMessageSent(
            ResizeProductImageTopic::getName(),
            $this->prepareProductImageResizeMessage($productImage1)
        );
        self::assertMessageSent(
            ResizeProductImageTopic::getName(),
            $this->prepareProductImageResizeMessage($productImage2)
        );
        self::assertMessageSent(
            ResizeProductImageTopic::getName(),
            $this->prepareProductImageResizeMessage($productImage3)
        );

        self::assertMessageSent(
            WebsiteSearchReindexTopic::getName(),
            $this->prepareProductsReindexMessage([$product1, $product2, $product3], ['image'])
        );
        self::assertMessageSentWithPriority(WebsiteSearchReindexTopic::getName(), MessagePriority::LOW);
    }

    public function testUpdateTypesOnProductImage(): void
    {
        $product1 = $this->getProduct(LoadProductData::PRODUCT_3);
        $productImage1 = new ProductImage();
        $productImage1->setProduct($product1);
        $this->em->persist($productImage1);

        $product2 = $this->getProduct(LoadProductData::PRODUCT_4);
        $productImage2 = new ProductImage();
        $productImage2->setProduct($product2);
        $this->em->persist($productImage2);

        $this->em->flush();

        /* nothing sent if product image have no types */
        self::assertEmptyMessages(ResizeProductImageTopic::getName());
        self::assertEmptyMessages(WebsiteSearchReindexTopic::getName());

        /* message sent if product image has been updated */
        $productImage1->addType(ProductImageType::TYPE_MAIN);
        $productImage1->addType(ProductImageType::TYPE_LISTING);
        $productImage1->addType(ProductImageType::TYPE_ADDITIONAL);

        $productImage2->addType(ProductImageType::TYPE_MAIN);
        $productImage2->addType(ProductImageType::TYPE_LISTING);
        $productImage2->addType(ProductImageType::TYPE_ADDITIONAL);

        $this->em->flush();

        self::assertMessagesCount(ResizeProductImageTopic::getName(), 2);
        self::assertMessagesCount(WebsiteSearchReindexTopic::getName(), 1);

        self::assertMessageSent(
            ResizeProductImageTopic::getName(),
            $this->prepareProductImageResizeMessage($productImage1)
        );
        self::assertMessageSent(
            ResizeProductImageTopic::getName(),
            $this->prepareProductImageResizeMessage($productImage2)
        );

        self::assertMessageSent(
            WebsiteSearchReindexTopic::getName(),
            $this->prepareProductsReindexMessage([$product1, $product2], ['image'])
        );
        self::assertMessageSentWithPriority(WebsiteSearchReindexTopic::getName(), MessagePriority::LOW);
    }

    public function testUpdateFileOnProductImage(): void
    {
        $product1 = $this->getProduct(LoadProductData::PRODUCT_1);
        $productImage1 = $this->getProductImage($product1);

        $image1 = $productImage1->getImage();
        $image1->setFile(new File('test1.file', false));
        $image1->preUpdate();

        $product2 = $this->getProduct(LoadProductData::PRODUCT_2);
        $productImage2 = $this->getProductImage($product2);

        $image2 = $productImage2->getImage();
        $image2->setFile(new File('test2.file', false));
        $image2->preUpdate();

        $this->em->flush();

        self::assertMessagesCount(ResizeProductImageTopic::getName(), 2);
        self::assertMessagesCount(WebsiteSearchReindexTopic::getName(), 1);

        self::assertMessageSent(
            ResizeProductImageTopic::getName(),
            $this->prepareProductImageResizeMessage($productImage1)
        );
        self::assertMessageSent(
            ResizeProductImageTopic::getName(),
            $this->prepareProductImageResizeMessage($productImage2)
        );

        self::assertMessageSent(
            WebsiteSearchReindexTopic::getName(),
            $this->prepareProductsReindexMessage([$product1, $product2], ['image'])
        );
        self::assertMessageSentWithPriority(WebsiteSearchReindexTopic::getName(), MessagePriority::LOW);
    }

    public function testUpdateFileAndTypesOnProductImage(): void
    {
        $product1 = $this->getProduct(LoadProductData::PRODUCT_1);
        $productImage1 = $this->getProductImage($product1);
        $productImage1->removeType(ProductImageType::TYPE_MAIN);
        $productImage1->addType(ProductImageType::TYPE_ADDITIONAL);

        $image1 = $productImage1->getImage();
        $image1->setFile(new File('test.file', false));
        $image1->preUpdate();

        $product2 = $this->getProduct(LoadProductData::PRODUCT_2);
        $productImage2 = $this->getProductImage($product2);
        $productImage2->removeType(ProductImageType::TYPE_MAIN);
        $productImage2->addType(ProductImageType::TYPE_ADDITIONAL);

        $image2 = $productImage2->getImage();
        $image2->setFile(new File('test.file', false));
        $image2->preUpdate();

        $this->em->flush();

        self::assertMessagesCount(ResizeProductImageTopic::getName(), 2);
        self::assertMessagesCount(WebsiteSearchReindexTopic::getName(), 1);

        self::assertMessageSent(
            ResizeProductImageTopic::getName(),
            $this->prepareProductImageResizeMessage($productImage1)
        );
        self::assertMessageSent(
            ResizeProductImageTopic::getName(),
            $this->prepareProductImageResizeMessage($productImage2)
        );

        self::assertMessageSent(
            WebsiteSearchReindexTopic::getName(),
            $this->prepareProductsReindexMessage([$product1, $product2], ['image'])
        );
        self::assertMessageSentWithPriority(WebsiteSearchReindexTopic::getName(), MessagePriority::LOW);
    }

    public function testDuplicateProductImage(): void
    {
        $duplicator = self::getContainer()->get('oro_product.service.duplicator');

        $product3 = $this->getProduct(LoadProductData::PRODUCT_3);
        $productCopy3 = $duplicator->duplicate($product3);
        $this->em->refresh($productCopy3);
        $productImageCopy1 = $this->getProductImage($productCopy3);

        $product8 = $this->getProduct(LoadProductData::PRODUCT_8);
        $productCopy8 = $duplicator->duplicate($product8);
        $this->em->refresh($productCopy8);
        $productImageCopy2 = $this->getProductImage($productCopy8);

        self::assertMessagesCount(ResizeProductImageTopic::getName(), 2);
        self::assertMessagesCount(WebsiteSearchReindexTopic::getName(), 2);
        self::assertMessagesCount(IndexEntitiesByIdTopic::getName(), 2);
        self::assertMessagesCount(GenerateDirectUrlForEntitiesTopic::getName(), 2);
        self::assertMessagesCount(AuditChangedEntitiesTopic::getName(), 4);

        self::assertMessageSent(
            ResizeProductImageTopic::getName(),
            $this->prepareProductImageResizeMessage($productImageCopy1)
        );
        self::assertMessageSent(
            ResizeProductImageTopic::getName(),
            $this->prepareProductImageResizeMessage($productImageCopy2)
        );

        // There are 3 of the same message after duplicate
        self::assertMessageSent(
            WebsiteSearchReindexTopic::getName(),
            $this->prepareProductsReindexMessage([$productCopy3])
        );

        // There are 3 of the same message after duplicate
        self::assertMessageSent(
            WebsiteSearchReindexTopic::getName(),
            $this->prepareProductsReindexMessage([$productCopy8])
        );
    }
}
