<?php

namespace Oro\Bundle\ProductBundle\Tests\Functional\Async;

use Oro\Bundle\GaufretteBundle\FileManager;
use Oro\Bundle\LayoutBundle\Layout\Extension\ThemeConfiguration;
use Oro\Bundle\LayoutBundle\Model\ThemeImageTypeDimension;
use Oro\Bundle\ProductBundle\Async\ResizeProductImageMessageProcessor;
use Oro\Bundle\ProductBundle\Async\Topic\ResizeProductImageTopic;
use Oro\Bundle\ProductBundle\Entity\ProductImage;
use Oro\Bundle\ProductBundle\Entity\ProductImageType;
use Oro\Bundle\ProductBundle\Tests\Functional\DataFixtures\LoadProductData;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;
use Oro\Bundle\WebsiteBundle\Entity\Website;
use Oro\Component\MessageQueue\Consumption\MessageProcessorInterface;
use Oro\Component\MessageQueue\Transport\Message;
use Oro\Component\MessageQueue\Transport\MessageInterface;
use Oro\Component\MessageQueue\Transport\SessionInterface;

/**
 * @group CommunityEdition
 */
class ResizeProductImageMessageProcessorTest extends WebTestCase
{
    private const EXAMPLE_IMAGE_PATH = '/../DataFixtures/files/example.gif';
    private const PRODUCT_LARGE_FILTER = 'product_large';
    private const PRODUCT_SMALL_FILTER = 'product_small';
    private const PRODUCT_ORIGINAL_FILTER = 'product_original';
    private const PRODUCT_GALLERY_MAIN = 'product_gallery_main';

    /** @var ResizeProductImageMessageProcessor */
    private $processor;

    protected function setUp(): void
    {
        $this->initClient();
        $this->loadFixtures([LoadProductData::class]);

        $this->processor = self::getContainer()->get('oro_product.async.resize_product_image_processor');
    }

    private function getSessionMock(): SessionInterface
    {
        return $this->createMock(SessionInterface::class);
    }

    private function getMessage(array $body): MessageInterface
    {
        $message = new Message();
        $message->setBody($body);

        return $message;
    }

    private function createProductImage(): ProductImage
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

    private function assertValidImage(ProductImage $productImage, string $filterName): void
    {
        $dimensions = $this->getAllDimensions();
        $filteredPath = $this->getFilteredImagePath($productImage, $filterName);
        /** @var FileManager $mediacacheManager */
        $mediacacheManager = self::getContainer()->get('oro_attachment.manager.public_mediacache');
        self::assertTrue(
            $mediacacheManager->hasFile($filteredPath),
            sprintf('Failed assert that "%s" file exists.', $filteredPath)
        );

        $image = self::getContainer()->get('liip_imagine')->load($mediacacheManager->getFileContent($filteredPath));
        $originalImage = self::getContainer()->get('liip_imagine')->open(
            $productImage->getImage()->getFile()->getPathname()
        );

        $imageSize = $image->getSize();
        $originalImageSize = $originalImage->getSize();

        /** @var ThemeImageTypeDimension $dimension */
        $dimension = $dimensions[$filterName];

        $expectedWidth = $dimension->getWidth() ?: $originalImageSize->getWidth();
        $expectedHeight = $dimension->getHeight() ?: $originalImageSize->getHeight();

        if (ThemeConfiguration::AUTO === $expectedWidth) {
            $expectedWidth = round(
                $originalImageSize->getWidth() * $dimension->getHeight() / $originalImageSize->getHeight()
            );
        }

        if (ThemeConfiguration::AUTO === $expectedHeight) {
            $expectedHeight = round(
                $originalImageSize->getHeight() * $dimension->getWidth() / $originalImageSize->getWidth()
            );
        }

        self::assertEquals(
            [$expectedWidth, $expectedHeight],
            [$imageSize->getWidth(), $imageSize->getHeight()]
        );
    }

    private function getAllDimensions(): array
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
    private function getFilteredImagePath(ProductImage $productImage, $filterName): string
    {
        /** @var Website $defaultWebsite */
        $defaultWebsite = self::getContainer()
            ->get('doctrine')->getRepository(Website::class)
            ->findOneBy(['default' => true]);
        $websiteManager = self::getContainer()
            ->get('oro_website.manager');

        $websiteManager->setCurrentWebsite($defaultWebsite);

        $filteredUrl = self::getContainer()
            ->get('oro_attachment.manager')
            ->getFilteredImageUrl($productImage->getImage(), $filterName);

        $websiteManager->onClear();

        return str_replace('/media/cache/', '', $filteredUrl);
    }

    public function testResizeProductImage(): void
    {
        $productImage = $this->createProductImage();

        $message = $this->getMessage([
            ResizeProductImageTopic::PRODUCT_IMAGE_ID_OPTION => $productImage->getId(),
            ResizeProductImageTopic::FORCE_OPTION => true,
            ResizeProductImageTopic::DIMENSIONS_OPTION => [],
        ]);

        self::assertEquals(
            MessageProcessorInterface::ACK,
            $this->processor->process($message, $this->getSessionMock())
        );

        $this->assertValidImage($productImage, self::PRODUCT_LARGE_FILTER);
        $this->assertValidImage($productImage, self::PRODUCT_SMALL_FILTER);
        $this->assertValidImage($productImage, self::PRODUCT_ORIGINAL_FILTER);
        $this->assertValidImage($productImage, self::PRODUCT_GALLERY_MAIN);
    }
}
