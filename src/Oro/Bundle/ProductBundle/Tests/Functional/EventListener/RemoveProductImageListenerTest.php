<?php

namespace Oro\Bundle\ProductBundle\Tests\Functional\EventListener;

use Doctrine\ORM\EntityManager;
use Oro\Bundle\AttachmentBundle\Entity\File;
use Oro\Bundle\AttachmentBundle\MessageProcessor\ImageRemoveMessageProcessor;
use Oro\Bundle\MessageQueueBundle\Test\Functional\MessageQueueExtension;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\ProductBundle\Entity\ProductImage;
use Oro\Bundle\ProductBundle\Entity\ProductImageType;
use Oro\Bundle\ProductBundle\Tests\Functional\DataFixtures\LoadProductData;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;

class RemoveProductImageListenerTest extends WebTestCase
{
    use MessageQueueExtension;

    /** @var EntityManager */
    private $em;

    /** @var string */
    private $imageRemoveTopic;

    /**
     * {@inheritdoc}
     */
    protected function setUp(): void
    {
        $this->initClient();
        $this->client->useHashNavigation(true);

        $this->em = $this->getContainer()->get('doctrine')->getManagerForClass(ProductImage::class);
        $this->imageRemoveTopic = ImageRemoveMessageProcessor::IMAGE_REMOVE_TOPIC;

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

        $message = $this->prepareExpectedMessage($imageFile);

        $this->em->remove($productImage);
        $this->em->flush();

        $this->assertMessagesCount($this->imageRemoveTopic, 1);
        $this->assertMessageSent(
            $this->imageRemoveTopic,
            $message
        );
    }

    /**
     * @param File $imageFile
     * @return array
     */
    private function prepareExpectedMessage(File $imageFile)
    {
        return [
            [
                'id' => $imageFile->getId(),
                'fileName' => $imageFile->getFilename(),
                'originalFileName' => $imageFile->getOriginalFilename(),
                'parentEntityClass' => $imageFile->getParentEntityClass()
            ]
        ];
    }
}
