<?php

namespace Oro\Bundle\ProductBundle\Tests\Functional\EventListener;

use Doctrine\ORM\EntityManagerInterface;
use Oro\Bundle\AttachmentBundle\Async\Topic\AttachmentRemoveImageTopic;
use Oro\Bundle\AttachmentBundle\Entity\File;
use Oro\Bundle\MessageQueueBundle\Test\Functional\MessageQueueExtension;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\ProductBundle\Entity\ProductImage;
use Oro\Bundle\ProductBundle\Entity\ProductImageType;
use Oro\Bundle\ProductBundle\Tests\Functional\DataFixtures\LoadProductData;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;

class RemoveProductImageListenerTest extends WebTestCase
{
    use MessageQueueExtension;

    private EntityManagerInterface $entityManager;

    protected function setUp(): void
    {
        $this->initClient();
        $this->client->useHashNavigation(true);

        $this->entityManager = self::getContainer()->get('doctrine')->getManagerForClass(ProductImage::class);

        $this->loadFixtures([LoadProductData::class]);
    }

    public function testRemoveProductImageWhenStoredExternally(): void
    {
        $fileRepository = self::getContainer()->get('doctrine')->getRepository(File::class);

        /** @var Product $product */
        $product = $this->getReference(LoadProductData::PRODUCT_1);

        $imageFile = new File();
        $imageFile->setFilename('123.jpg');
        $imageFile->setOriginalFilename('123-original.jpg');
        $imageFile->setParentEntityClass(ProductImage::class);
        $imageFile->setExternalUrl('http://example.org/image.png');

        $productImage = new ProductImage();
        $productImage->addType(ProductImageType::TYPE_MAIN);
        $productImage->setProduct($product);
        $productImage->setImage($imageFile);

        $this->entityManager->persist($productImage);
        $this->entityManager->flush();

        $files = $fileRepository->findAll();
        self::assertContains($imageFile, $files);

        $this->entityManager->remove($productImage);
        $this->entityManager->flush();

        self::assertMessagesCount(AttachmentRemoveImageTopic::getName(), 0);
    }

    public function testRemoveProductImage(): void
    {
        $fileRepository = self::getContainer()->get('doctrine')->getRepository(File::class);

        /** @var Product $product */
        $product = $this->getReference(LoadProductData::PRODUCT_1);

        $imageFile = new File();
        $imageFile->setFilename('123.jpg');
        $imageFile->setOriginalFilename('123-original.jpg');
        $imageFile->setParentEntityClass(ProductImage::class);

        $productImage = new ProductImage();
        $productImage->addType(ProductImageType::TYPE_MAIN);
        $productImage->setProduct($product);
        $productImage->setImage($imageFile);

        $this->entityManager->persist($productImage);
        $this->entityManager->flush();

        $files = $fileRepository->findAll();
        self::assertContains($imageFile, $files);

        $this->entityManager->remove($productImage);
        $this->entityManager->flush();

        self::assertMessagesCount(AttachmentRemoveImageTopic::getName(), 1);
        self::assertMessageSent(
            AttachmentRemoveImageTopic::getName(),
            [
                'images' => [
                    [
                        'id' => $imageFile->getId(),
                        'fileName' => $imageFile->getFilename(),
                        'originalFileName' => $imageFile->getOriginalFilename(),
                        'parentEntityClass' => $imageFile->getParentEntityClass()
                    ],
                ],
            ]
        );
    }
}
