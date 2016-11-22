<?php

namespace Oro\Bundle\ProductBundle\Tests\Functional\Command;

use Oro\Bundle\LayoutBundle\Model\ThemeImageTypeDimension;
use Oro\Bundle\ProductBundle\Entity\ProductImage;
use Oro\Bundle\ProductBundle\Entity\ProductImageType;
use Oro\Bundle\ProductBundle\MessageProcessor\ImageResizeMessageProcessor;
use Oro\Bundle\ProductBundle\Tests\Functional\DataFixtures\LoadProductData;
use Oro\Bundle\ProductBundle\Tests\Functional\DataFixtures\LoadProductImageData;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;

use Oro\Component\MessageQueue\Consumption\MessageProcessorInterface;
use Oro\Component\MessageQueue\Transport\Dbal\DbalMessage;
use Oro\Component\MessageQueue\Transport\Null\NullSession;
use Oro\Component\MessageQueue\Util\JSON;

/**
 * @dbIsolation
 */
class ImageResizeMessageProcessorTest extends WebTestCase
{
    const EXAMPLE_IMAGE_PATH = '/../DataFixtures/files/example.gif';
    const PRODUCT_LARGE_FILTER = 'product_large';
    const PRODUCT_SMALL_FILTER = 'product_small';
    const PRODUCT_ORIGINAL_FILTER = 'product_original';

    /** @var ImageResizeMessageProcessor */
    protected $processor;

    protected function setUp()
    {
        $this->initClient();
        $this->loadFixtures([LoadProductImageData::class]);

        $this->processor = self::getContainer()->get('oro.product.message_processor.image_resize');
    }

    public function testResizeProductImage()
    {
        $productImage = $this->createProductImage();

        $message = new DbalMessage();
        $message->setBody(JSON::encode([
            'productImageId' => $productImage->getId(),
            'force' => $force = true
        ]));

        $this->assertEquals(MessageProcessorInterface::ACK, $this->processor->process($message, new NullSession()));

        $this->assertValidImage($productImage, self::PRODUCT_LARGE_FILTER);
        $this->assertValidImage($productImage, self::PRODUCT_SMALL_FILTER);
        $this->assertValidImage($productImage, self::PRODUCT_ORIGINAL_FILTER);
    }

    /**
     * @return ProductImage
     */
    private function createProductImage()
    {
        $productImage = new ProductImage();
        $productImage->addType(ProductImageType::TYPE_MAIN);
        $productImage->addType(ProductImageType::TYPE_LISTING);
        $productImage->addType(ProductImageType::TYPE_ADDITIONAL);

        $productImage->setImage(
            self::getContainer()
                ->get('oro_attachment.file_manager')
                ->createFileEntity(__DIR__ . self::EXAMPLE_IMAGE_PATH)
        );
        $productImage->setProduct($this->getReference(LoadProductData::PRODUCT_1));

        $em = self::getContainer()->get('doctrine')->getManagerForClass(ProductImage::class);
        $em->persist($productImage);
        $em->flush();

        return $productImage;
    }

    private function assertValidImage(ProductImage $productImage, $filterName)
    {
        $dimensions = $this->getAllDimensions();
        $filteredPath = $this->getFilteredImagePath($productImage, $filterName);

        $this->assertFileExists($filteredPath);

        $image = self::getContainer()->get('liip_imagine')->open($filteredPath);
        $originalImage = self::getContainer()->get('liip_imagine')->open(
            $productImage->getImage()->getFile()->getPathname()
        );

        $imageSize = $image->getSize();
        $originalImageSize = $originalImage->getSize();

        /** @var ThemeImageTypeDimension $dimension */
        $dimension = $dimensions[$filterName];

        $this->assertEquals(
            [
                $dimension->getWidth() ? : $originalImageSize->getWidth(),
                $dimension->getHeight() ? : $originalImageSize->getHeight()
            ],
            [
                $imageSize->getWidth(),
                $imageSize->getHeight()
            ]
        );
    }

    /**
     * @return array
     */
    private function getAllDimensions()
    {
        $dimensions = [];
        $imageTypeProvider = self::getContainer()->get('oro_layout.provider.image_type');

        foreach ($imageTypeProvider->getImageTypes() as $imageType) {
            $dimensions = array_merge($dimensions, $imageType->getDimensions());
        }

        return $dimensions;
    }

    /**
     * @param ProductImage $productImage
     * @param string $filterName
     * @return string
     */
    private function getFilteredImagePath(ProductImage $productImage, $filterName)
    {
        $filteredUrl = self::getContainer()
            ->get('oro_attachment.manager')
            ->getFilteredImageUrl($productImage->getImage(), $filterName);

        $filteredPath = self::getContainer()->getParameter('kernel.root_dir') . '/../web' . $filteredUrl;

        return $filteredPath;
    }
}
