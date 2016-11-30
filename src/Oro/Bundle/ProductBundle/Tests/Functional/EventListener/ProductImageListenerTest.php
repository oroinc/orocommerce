<?php

namespace Oro\Bundle\ProductBundle\Tests\Functional\EventListener;

use Doctrine\ORM\EntityManager;

use Symfony\Component\HttpFoundation\File\File;

use Oro\Bundle\MessageQueueBundle\Test\Functional\MessageCollector;
use Oro\Bundle\MessageQueueBundle\Test\Functional\MessageQueueExtension;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\ProductBundle\Entity\ProductImage;
use Oro\Bundle\ProductBundle\Entity\ProductImageType;
use Oro\Bundle\ProductBundle\EventListener\ProductImageResizeListener;
use Oro\Bundle\ProductBundle\Tests\Functional\DataFixtures\LoadProductData;
use Oro\Bundle\ProductBundle\Tests\Functional\DataFixtures\LoadProductImageData;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;

/**
 * @dbIsolationPerTest
 */
class ProductImageListenerTest extends WebTestCase
{
    use MessageQueueExtension;

    /**
     * @var EntityManager
     */
    protected $em;

    /**
     * @var MessageCollector
     */
    protected $messageCollector;

    /**
     * @var string
     */
    protected $imageResizeTopic;

    protected function setUp()
    {
        $this->initClient();
        $this->client->useHashNavigation(true);

        $this->em = $this->getContainer()->get('doctrine')->getManagerForClass(ProductImage::class);
        $this->imageResizeTopic = ProductImageResizeListener::IMAGE_RESIZE_TOPIC;

        $this->loadFixtures([LoadProductImageData::class]);

        $this->setUpMessageCollector();
        $this->getMessageCollector()->clear();
    }

    protected function tearDown()
    {
        parent::tearDown();
        $this->tearDownMessageCollector();
    }

    public function testCreateProductImage()
    {
        /** @var Product $product */
        $product = $this->getReference(LoadProductData::PRODUCT_1);
        $productImage = new ProductImage();
        $productImage->addType(ProductImageType::TYPE_MAIN);
        $productImage->setProduct($product);

        $this->em->persist($productImage);
        $this->em->flush($productImage);

        $this->assertMessagesCount($this->imageResizeTopic, 1);
        $this->assertMessageSent(
            $this->imageResizeTopic,
            $this->prepareExpectedMessage($productImage)
        );
    }

    public function testUpdateTypesOnProductImage()
    {
        /** @var Product $product1 */
        $product = $this->getReference(LoadProductData::PRODUCT_1);
        $productImage = new ProductImage();
        $productImage->setProduct($product);

        $this->em->persist($productImage);
        $this->em->flush();

        /* nothing sent if product image have no types */
        $this->assertEmptyMessages($this->imageResizeTopic);

        /* message sent if product image has been updated */
        $productImage->addType(ProductImageType::TYPE_MAIN);
        $productImage->addType(ProductImageType::TYPE_LISTING);
        $productImage->addType(ProductImageType::TYPE_ADDITIONAL);

        $this->em->flush();

        $this->assertMessagesCount($this->imageResizeTopic, 1);
        $this->assertMessageSent(
            $this->imageResizeTopic,
            $this->prepareExpectedMessage($productImage)
        );
    }

    public function testUpdateFileOnProductImage()
    {
        /** @var Product $product */
        $product = $this->getReference(LoadProductData::PRODUCT_1);
        /** @var ProductImage $productImage */
        $productImage = $product ->getImages()->first();

        $image = $productImage->getImage();
        $image->setFile(new File('test.file', false));
        $image->preUpdate();

        $this->em->flush();

        $this->assertMessagesCount($this->imageResizeTopic, 1);
        $this->assertMessageSent(
            $this->imageResizeTopic,
            $this->prepareExpectedMessage($productImage)
        );
    }

    public function testUpdateFileAndTypesOnProductImage()
    {
        /** @var Product $product */
        $product = $this->getReference(LoadProductData::PRODUCT_1);
        /** @var ProductImage $productImage */
        $productImage = $product->getImages()->first();
        $productImage->removeType(ProductImageType::TYPE_MAIN);
        $productImage->addType(ProductImageType::TYPE_ADDITIONAL);

        $image = $productImage->getImage();
        $image->setFile(new File('test.file', false));
        $image->preUpdate();

        $this->em->flush();

        $this->assertMessagesCount($this->imageResizeTopic, 1);
        $this->assertMessageSent(
            $this->imageResizeTopic,
            $this->prepareExpectedMessage($productImage)
        );
    }

    public function testDuplicateProductImage()
    {
        /** @var Product $product */
        $product = $this->getReference(LoadProductData::PRODUCT_1);
        $productCopy = $this->getContainer()->get('oro_product.service.duplicator')->duplicate($product);
        $this->em->refresh($productCopy);
        /** @var ProductImage $productImageCopy */
        $productImageCopy = $productCopy->getImages()->first();

        $this->assertMessagesCount($this->imageResizeTopic, 1);
        $this->assertMessageSent(
            $this->imageResizeTopic,
            $this->prepareExpectedMessage($productImageCopy)
        );
    }

    /**
     * @param ProductImage $productImage
     * @return array
     */
    private function prepareExpectedMessage(ProductImage $productImage)
    {
        return [
            'productImageId' => $productImage->getId(),
            'force' => true
        ];
    }
}
