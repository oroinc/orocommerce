<?php

namespace Oro\Bundle\ProductBundle\Tests\Functional\EventListener;

use Doctrine\ORM\EntityManagerInterface;
use Oro\Bundle\DataAuditBundle\Async\Topics as DataAuditTopics;
use Oro\Bundle\MessageQueueBundle\Test\Functional\MessageQueueExtension;
use Oro\Bundle\ProductBundle\Async\Topics as ProductTopics;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\ProductBundle\Entity\ProductImage;
use Oro\Bundle\ProductBundle\Entity\ProductImageType;
use Oro\Bundle\ProductBundle\Tests\Functional\DataFixtures\LoadProductData;
use Oro\Bundle\RedirectBundle\Async\Topics as RedirectTopics;
use Oro\Bundle\SearchBundle\Async\Topics as SearchTopics;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;
use Oro\Bundle\WebsiteSearchBundle\Engine\AsyncIndexer as WebsiteSearchAsyncIndexerTopics;
use Oro\Bundle\WebsiteSearchBundle\Tests\Functional\Traits\DefaultWebsiteIdTestTrait;
use Oro\Component\MessageQueue\Client\Message;
use Oro\Component\MessageQueue\Client\MessagePriority;
use Symfony\Component\HttpFoundation\File\File;

/**
 * @dbIsolationPerTest
 */
class ProductImageListenerTest extends WebTestCase
{
    use DefaultWebsiteIdTestTrait;
    use MessageQueueExtension;

    /** @var EntityManagerInterface */
    private $em;

    protected function setUp(): void
    {
        $this->initClient();
        $this->client->useHashNavigation(true);

        $this->em = $this->getContainer()->get('doctrine')->getManagerForClass(ProductImage::class);

        $this->getOptionalListenerManager()->enableListener('oro_product.event_listener.product_image_resize_listener');
        $this->getOptionalListenerManager()->enableListener('oro_redirect.event_listener.slug_prototype_change');
        $this->getOptionalListenerManager()->enableListener('oro_redirect.event_listener.slug_change');
        $this->getOptionalListenerManager()->enableListener(
            'oro_dataaudit.listener.send_changed_entities_to_message_queue'
        );

        $this->loadFixtures([LoadProductData::class]);
    }

    /**
     * @param ProductImage $productImage
     *
     * @return array
     */
    private function prepareProductImageResizeMessage(ProductImage $productImage)
    {
        return [
            'productImageId' => $productImage->getId(),
            'force' => true,
            'dimensions' => null
        ];
    }

    /**
     * @param Product[] $products
     *
     * @return Message
     */
    private function prepareProductsReindexMessage(array $products)
    {
        $entityIds = [];
        foreach ($products as $product) {
            $entityIds[] = $product->getId();
        }

        return new Message(
            [
                'class' => [Product::class],
                'granulize' => true,
                'context' => [
                    'entityIds' => $entityIds,
                    'websiteIds' => [$this->getDefaultWebsiteId()]
                ],
            ],
            MessagePriority::LOW
        );
    }

    public function testCreateProductImage()
    {
        /** @var Product $product */
        $product = $this->getReference(LoadProductData::PRODUCT_1);
        $productImage = new ProductImage();
        $productImage->addType(ProductImageType::TYPE_MAIN);
        $productImage->setProduct($product);

        $this->em->persist($productImage);
        $this->em->flush();

        $this->assertMessagesCount(ProductTopics::PRODUCT_IMAGE_RESIZE, 1);
        $this->assertMessageSent(
            ProductTopics::PRODUCT_IMAGE_RESIZE,
            $this->prepareProductImageResizeMessage($productImage)
        );
    }

    public function testCreateProductImagesForSeveralProducts()
    {
        /** @var Product $product1 */
        $product1 = $this->getReference(LoadProductData::PRODUCT_1);
        $productImage1 = new ProductImage();
        $productImage1->addType(ProductImageType::TYPE_MAIN);
        $productImage1->setProduct($product1);
        $this->em->persist($productImage1);

        /** @var Product $product2 */
        $product2 = $this->getReference(LoadProductData::PRODUCT_2);
        $productImage2 = new ProductImage();
        $productImage2->addType(ProductImageType::TYPE_MAIN);
        $productImage2->setProduct($product2);
        $this->em->persist($productImage2);

        /** @var Product $product3 */
        $product3 = $this->getReference(LoadProductData::PRODUCT_3);
        $productImage3 = new ProductImage();
        $productImage3->addType(ProductImageType::TYPE_MAIN);
        $productImage3->setProduct($product3);
        $this->em->persist($productImage3);

        $this->em->flush();

        $this->assertMessagesCount(ProductTopics::PRODUCT_IMAGE_RESIZE, 3);
        $this->assertMessagesCount(WebsiteSearchAsyncIndexerTopics::TOPIC_REINDEX, 1);

        $this->assertMessageSent(
            ProductTopics::PRODUCT_IMAGE_RESIZE,
            $this->prepareProductImageResizeMessage($productImage1)
        );
        $this->assertMessageSent(
            ProductTopics::PRODUCT_IMAGE_RESIZE,
            $this->prepareProductImageResizeMessage($productImage2)
        );
        $this->assertMessageSent(
            ProductTopics::PRODUCT_IMAGE_RESIZE,
            $this->prepareProductImageResizeMessage($productImage3)
        );

        $this->assertMessageSent(
            WebsiteSearchAsyncIndexerTopics::TOPIC_REINDEX,
            $this->prepareProductsReindexMessage([$product1, $product2, $product3])
        );
    }

    public function testUpdateTypesOnProductImage()
    {
        /** @var Product $product1 */
        $product1 = $this->getReference(LoadProductData::PRODUCT_3);
        $productImage1 = new ProductImage();
        $productImage1->setProduct($product1);
        $this->em->persist($productImage1);

        /** @var Product $product2 */
        $product2 = $this->getReference(LoadProductData::PRODUCT_4);
        $productImage2 = new ProductImage();
        $productImage2->setProduct($product2);
        $this->em->persist($productImage2);

        $this->em->flush();

        /* nothing sent if product image have no types */
        $this->assertEmptyMessages(ProductTopics::PRODUCT_IMAGE_RESIZE);
        $this->assertEmptyMessages(WebsiteSearchAsyncIndexerTopics::TOPIC_REINDEX);

        /* message sent if product image has been updated */
        $productImage1->addType(ProductImageType::TYPE_MAIN);
        $productImage1->addType(ProductImageType::TYPE_LISTING);
        $productImage1->addType(ProductImageType::TYPE_ADDITIONAL);

        $productImage2->addType(ProductImageType::TYPE_MAIN);
        $productImage2->addType(ProductImageType::TYPE_LISTING);
        $productImage2->addType(ProductImageType::TYPE_ADDITIONAL);

        $this->em->flush();

        $this->assertMessagesCount(ProductTopics::PRODUCT_IMAGE_RESIZE, 2);
        $this->assertMessagesCount(WebsiteSearchAsyncIndexerTopics::TOPIC_REINDEX, 1);

        $this->assertMessageSent(
            ProductTopics::PRODUCT_IMAGE_RESIZE,
            $this->prepareProductImageResizeMessage($productImage1)
        );
        $this->assertMessageSent(
            ProductTopics::PRODUCT_IMAGE_RESIZE,
            $this->prepareProductImageResizeMessage($productImage2)
        );

        $this->assertMessageSent(
            WebsiteSearchAsyncIndexerTopics::TOPIC_REINDEX,
            $this->prepareProductsReindexMessage([$product1, $product2])
        );
    }

    public function testUpdateFileOnProductImage()
    {
        /** @var Product $product1 */
        $product1 = $this->getReference(LoadProductData::PRODUCT_1);
        /** @var ProductImage $productImage1 */
        $productImage1 = $product1->getImages()->first();

        $image1 = $productImage1->getImage();
        $image1->setFile(new File('test1.file', false));
        $image1->preUpdate();

        /** @var Product $product2 */
        $product2 = $this->getReference(LoadProductData::PRODUCT_2);
        /** @var ProductImage $productImage2 */
        $productImage2 = $product2->getImages()->first();

        $image2 = $productImage2->getImage();
        $image2->setFile(new File('test2.file', false));
        $image2->preUpdate();

        $this->em->flush();

        $this->assertMessagesCount(ProductTopics::PRODUCT_IMAGE_RESIZE, 2);
        $this->assertMessagesCount(WebsiteSearchAsyncIndexerTopics::TOPIC_REINDEX, 1);

        $this->assertMessageSent(
            ProductTopics::PRODUCT_IMAGE_RESIZE,
            $this->prepareProductImageResizeMessage($productImage1)
        );
        $this->assertMessageSent(
            ProductTopics::PRODUCT_IMAGE_RESIZE,
            $this->prepareProductImageResizeMessage($productImage2)
        );

        $this->assertMessageSent(
            WebsiteSearchAsyncIndexerTopics::TOPIC_REINDEX,
            $this->prepareProductsReindexMessage([$product1, $product2])
        );
    }

    public function testUpdateFileAndTypesOnProductImage()
    {
        /** @var Product $product1 */
        $product1 = $this->getReference(LoadProductData::PRODUCT_1);
        /** @var ProductImage $productImage1 */
        $productImage1 = $product1->getImages()->first();
        $productImage1->removeType(ProductImageType::TYPE_MAIN);
        $productImage1->addType(ProductImageType::TYPE_ADDITIONAL);

        $image1 = $productImage1->getImage();
        $image1->setFile(new File('test.file', false));
        $image1->preUpdate();

        /** @var Product $product2 */
        $product2 = $this->getReference(LoadProductData::PRODUCT_2);
        /** @var ProductImage $productImage2 */
        $productImage2 = $product2->getImages()->first();
        $productImage2->removeType(ProductImageType::TYPE_MAIN);
        $productImage2->addType(ProductImageType::TYPE_ADDITIONAL);

        $image2 = $productImage2->getImage();
        $image2->setFile(new File('test.file', false));
        $image2->preUpdate();

        $this->em->flush();

        $this->assertMessagesCount(ProductTopics::PRODUCT_IMAGE_RESIZE, 2);
        $this->assertMessagesCount(WebsiteSearchAsyncIndexerTopics::TOPIC_REINDEX, 1);

        $this->assertMessageSent(
            ProductTopics::PRODUCT_IMAGE_RESIZE,
            $this->prepareProductImageResizeMessage($productImage1)
        );
        $this->assertMessageSent(
            ProductTopics::PRODUCT_IMAGE_RESIZE,
            $this->prepareProductImageResizeMessage($productImage2)
        );

        $this->assertMessageSent(
            WebsiteSearchAsyncIndexerTopics::TOPIC_REINDEX,
            $this->prepareProductsReindexMessage([$product1, $product2])
        );
    }

    public function testDuplicateProductImage()
    {
        /** @var Product $product3 */
        $product3 = $this->getReference(LoadProductData::PRODUCT_3);
        $productCopy3 = $this->getContainer()->get('oro_product.service.duplicator')->duplicate($product3);
        $this->em->refresh($productCopy3);
        /** @var ProductImage $productImageCopy1 */
        $productImageCopy1 = $productCopy3->getImages()->first();

        /** @var Product $product8 */
        $product8 = $this->getReference(LoadProductData::PRODUCT_8);
        $productCopy8 = $this->getContainer()->get('oro_product.service.duplicator')->duplicate($product8);
        $this->em->refresh($productCopy8);
        /** @var ProductImage $productImageCopy2 */
        $productImageCopy2 = $productCopy8->getImages()->first();

        $this->assertMessagesCount(ProductTopics::PRODUCT_IMAGE_RESIZE, 2);
        $this->assertMessagesCount(WebsiteSearchAsyncIndexerTopics::TOPIC_REINDEX, 2);
        $this->assertMessagesCount(SearchTopics::INDEX_ENTITIES, 2);
        $this->assertMessagesCount(RedirectTopics::GENERATE_DIRECT_URL_FOR_ENTITIES, 2);
        $this->assertMessagesCount(DataAuditTopics::ENTITIES_CHANGED, 4);

        $this->assertMessageSent(
            ProductTopics::PRODUCT_IMAGE_RESIZE,
            $this->prepareProductImageResizeMessage($productImageCopy1)
        );
        $this->assertMessageSent(
            ProductTopics::PRODUCT_IMAGE_RESIZE,
            $this->prepareProductImageResizeMessage($productImageCopy2)
        );

        // There are 3 of the same message after duplicate
        $this->assertMessageSent(
            WebsiteSearchAsyncIndexerTopics::TOPIC_REINDEX,
            $this->prepareProductsReindexMessage([$productCopy3])
        );

        // There are 3 of the same message after duplicate
        $this->assertMessageSent(
            WebsiteSearchAsyncIndexerTopics::TOPIC_REINDEX,
            $this->prepareProductsReindexMessage([$productCopy8])
        );
    }
}
