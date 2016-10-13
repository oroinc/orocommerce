<?php

namespace Oro\Bundle\AccountBundle\Tests\Functional\EventListener;

use Doctrine\ORM\EntityManager;

use Oro\Bundle\MessageQueueBundle\Test\Functional\MessageCollector;
use Oro\Bundle\MessageQueueBundle\Test\Functional\MessageQueueExtension;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\ProductBundle\Entity\ProductImage;
use Oro\Bundle\ProductBundle\Entity\ProductImageType;
use Oro\Bundle\ProductBundle\EventListener\ProductImageResizeListener;
use Oro\Bundle\ProductBundle\Tests\Functional\DataFixtures\LoadProductData;
use Oro\Bundle\ProductBundle\Tests\Functional\DataFixtures\LoadProductImageData;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;

use Symfony\Component\HttpFoundation\File\File;

/**
 * @dbIsolation
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

    protected function setUp()
    {
        $this->initClient();
        $this->client->useHashNavigation(true);

        $this->em = $this->getContainer()->get('doctrine')->getManagerForClass(ProductImage::class);

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

        $this->assertCount(1, $this->getSentMessages());
        $this->assertMessageSent(
            ProductImageResizeListener::IMAGE_RESIZE_TOPIC,
            $this->prepareExpectedMessage($productImage)
        );
    }

    public function testUpdateTypesOnProductImage()
    {
        /** @var Product $product1 */
        $product1 = $this->getReference(LoadProductData::PRODUCT_1);
        $productImage = new ProductImage();
        $productImage->setProduct($product1);

        $this->em->persist($productImage);
        $this->em->flush($productImage);

        /* nothing sent if product image have no types */
        $this->assertEmptyMessages(ProductImageResizeListener::IMAGE_RESIZE_TOPIC);

        /* message sent if product image has been updated */
        $productImage->addType(ProductImageType::TYPE_MAIN);
        $productImage->addType(ProductImageType::TYPE_LISTING);
        $productImage->addType(ProductImageType::TYPE_ADDITIONAL);

        $this->em->flush($productImage);

        $this->assertCount(1, $this->getSentMessages());
        $this->assertMessageSent(
            ProductImageResizeListener::IMAGE_RESIZE_TOPIC,
            $this->prepareExpectedMessage($productImage)
        );
    }

    public function testUpdateFileOnProductImage()
    {
        /** @var Product $product */
        $product = $this->getReference(LoadProductData::PRODUCT_2);
        /** @var ProductImage $productImage */
        $productImage = $product ->getImages()->first();

        $productImage->getImage()->setFile(new File('test.file', false));
        $this->em->flush($productImage);

        $this->assertCount(1, $this->getSentMessages());
        $this->assertMessageSent(
            ProductImageResizeListener::IMAGE_RESIZE_TOPIC,
            $this->prepareExpectedMessage($productImage)

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
