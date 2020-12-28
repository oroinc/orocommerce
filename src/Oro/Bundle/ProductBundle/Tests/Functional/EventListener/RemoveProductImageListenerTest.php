<?php

namespace Oro\Bundle\ProductBundle\Tests\Functional\EventListener;

use Doctrine\ORM\EntityManagerInterface;
use Oro\Bundle\AttachmentBundle\Async\Topics;
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

    /** @var EntityManagerInterface */
    private $em;

    protected function setUp(): void
    {
        $this->initClient();
        $this->client->useHashNavigation(true);

        $this->em = $this->getContainer()->get('doctrine')->getManagerForClass(ProductImage::class);

        $this->loadFixtures([LoadProductData::class]);
    }

    public function testRemoveProductImage()
    {
        $fileRepository = $this->getContainer()->get('doctrine')->getRepository(File::class);

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

        $this->em->persist($productImage);
        $this->em->flush();

        $files = $fileRepository->findAll();
        $this->assertContains($imageFile, $files);

        $this->em->remove($productImage);
        $this->em->flush();

        $this->assertMessagesCount(Topics::ATTACHMENT_REMOVE_IMAGE, 1);
        $this->assertMessageSent(
            Topics::ATTACHMENT_REMOVE_IMAGE,
            [
                [
                    'id' => $imageFile->getId(),
                    'fileName' => $imageFile->getFilename(),
                    'originalFileName' => $imageFile->getOriginalFilename(),
                    'parentEntityClass' => $imageFile->getParentEntityClass()
                ]
            ]
        );
    }
}
