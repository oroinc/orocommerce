<?php

namespace Oro\Bundle\ProductBundle\Tests\Functional\Api\RestJsonApi;

use Oro\Bundle\ApiBundle\Tests\Functional\RestJsonApiTestCase;
use Oro\Bundle\AttachmentBundle\Tests\Functional\WebpConfigurationTrait;
use Oro\Bundle\AttachmentBundle\Tools\WebpConfiguration;
use Oro\Bundle\MessageQueueBundle\Test\Functional\MessageQueueExtension;
use Oro\Bundle\ProductBundle\Async\Topic\ResizeProductImageTopic;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\ProductBundle\Entity\ProductImage;
use Oro\Bundle\ProductBundle\Tests\Functional\DataFixtures\LoadProductData;
use Oro\Bundle\WebsiteSearchBundle\Async\Topic\WebsiteSearchReindexTopic;
use Oro\Bundle\WebsiteSearchBundle\Engine\AbstractIndexer;
use Oro\Bundle\WebsiteSearchBundle\Tests\Functional\Traits\DefaultWebsiteIdTestTrait;
use Symfony\Component\HttpFoundation\Response;

/**
 * @dbIsolationPerTest
 */
class ProductImageTest extends RestJsonApiTestCase
{
    use DefaultWebsiteIdTestTrait;
    use MessageQueueExtension;
    use WebpConfigurationTrait;

    private const ONE_PIXEL_JPEG_BASE64 = '/9j/4AAQSkZJRgABAQEAYABgAAD/2wBDAAIBAQIBAQICAgICAgICAwUDAwMDAwYEBAMFBw'
        . 'YHBwcGBwcICQsJCAgKCAcHCg0KCgsMDAwMBwkODw0MDgsMDAz/2wBDAQICAgMDAwYDAwYM'
        . 'CAcIDAwMDAwMDAwMDAwMDAwMDAwMDAwMDAwMDAwMDAwMDAwMDAwMDAwMDAwMDAwMDAwMDA'
        . 'z/wAARCAABAAEDASIAAhEBAxEB/8QAHwAAAQUBAQEBAQEAAAAAAAAAAAECAwQFBgcICQoL'
        . '/8QAtRAAAgEDAwIEAwUFBAQAAAF9AQIDAAQRBRIhMUEGE1FhByJxFDKBkaEII0KxwRVS0f'
        . 'AkM2JyggkKFhcYGRolJicoKSo0NTY3ODk6Q0RFRkdISUpTVFVWV1hZWmNkZWZnaGlqc3R1'
        . 'dnd4eXqDhIWGh4iJipKTlJWWl5iZmqKjpKWmp6ipqrKztLW2t7i5usLDxMXGx8jJytLT1N'
        . 'XW19jZ2uHi4+Tl5ufo6erx8vP09fb3+Pn6/8QAHwEAAwEBAQEBAQEBAQAAAAAAAAECAwQF'
        . 'BgcICQoL/8QAtREAAgECBAQDBAcFBAQAAQJ3AAECAxEEBSExBhJBUQdhcRMiMoEIFEKRob'
        . 'HBCSMzUvAVYnLRChYkNOEl8RcYGRomJygpKjU2Nzg5OkNERUZHSElKU1RVVldYWVpjZGVm'
        . 'Z2hpanN0dXZ3eHl6goOEhYaHiImKkpOUlZaXmJmaoqOkpaanqKmqsrO0tba3uLm6wsPExc'
        . 'bHyMnK0tPU1dbX2Nna4uPk5ebn6Onq8vP09fb3+Pn6/9oADAMBAAIRAxEAPwD+f+iiigD/'
        . '2Q==';

    #[\Override]
    protected function setUp(): void
    {
        parent::setUp();
        $this->loadFixtures([LoadProductData::class]);
    }

    /**
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     */
    #[\Override]
    protected function assertResponseContains(
        array|string $expectedContent,
        Response $response,
        bool $ignoreOrder = false
    ): void {
        $data = $this->getResponseData($expectedContent);
        $additionalData = [];
        if (isset($data['data'])) {
            if (array_is_list($data['data'])) {
                foreach ($data['data'] as $i => $item) {
                    if ('files' === $item['type'] && isset($item['attributes']['filePath'])) {
                        $additionalData['data'][$i]['attributes']['filePath'] = $item['attributes']['filePath'];
                        unset($data['data'][$i]['attributes']['filePath']);
                    }
                }
            } else {
                $item = $data['data'];
                if ('files' === $item['type'] && isset($item['attributes']['filePath'])) {
                    $additionalData['data']['attributes']['filePath'] = $item['attributes']['filePath'];
                    unset($data['data']['attributes']['filePath']);
                }
            }
        }
        if (isset($data['included'])) {
            foreach ($data['included'] as $i => $item) {
                if ('files' === $item['type'] && isset($item['attributes']['filePath'])) {
                    $additionalData['included'][$i]['attributes']['filePath'] = $item['attributes']['filePath'];
                    unset($data['included'][$i]['attributes']['filePath']);
                }
            }
        }
        parent::assertResponseContains($data, $response, $ignoreOrder);
        if ($additionalData) {
            self::assertArrayContains($additionalData, self::jsonToArray($response->getContent()));
        }
    }

    private static function updateExpectedData(array $expectedData, array $replace): array
    {
        array_walk_recursive(
            $expectedData,
            function (&$val) use ($replace) {
                if (is_string($val)) {
                    $val = strtr($val, $replace);
                }
            }
        );

        return self::processTemplateData($expectedData);
    }

    private function getProductImage(string $productReference): ProductImage
    {
        /** @var Product $product */
        $product = $this->getReference($productReference);

        return $product->getImages()->first();
    }

    public function testGetList(): void
    {
        $response = $this->cget(
            ['entity' => 'productimages'],
            ['filter' => ['product' => '@product-1->id']]
        );

        $this->assertResponseContains('cget_product_image_filter_by_product.yml', $response);
    }

    public function testGetWithIncludedImage(): void
    {
        $productImage = $this->getProductImage(LoadProductData::PRODUCT_1);
        $productImageId = $productImage->getId();
        $fileId = $productImage->getImage()->getId();

        $response = $this->get(
            ['entity' => 'productimages', 'id' => (string)$productImageId],
            ['include' => 'image']
        );

        $expectedData = self::updateExpectedData(
            $this->getResponseData('get_product_image_include.yml'),
            ['{fileId}' => (string)$fileId]
        );
        $this->assertResponseContains($expectedData, $response);
    }

    public function testGetWithIncludedImageAndOnlyFilePathIsRequested(): void
    {
        $productImage = $this->getProductImage(LoadProductData::PRODUCT_1);
        $productImageId = $productImage->getId();
        $fileId = $productImage->getImage()->getId();

        $response = $this->get(
            ['entity' => 'productimages', 'id' => (string)$productImageId],
            ['include' => 'image', 'fields[files]' => 'filePath']
        );

        $expectedData = self::updateExpectedData(
            $this->getResponseData('get_product_image_include_path_only.yml'),
            ['{fileId}' => (string)$fileId]
        );
        $this->assertResponseContains($expectedData, $response);
    }

    public function testGetWithIncludedImageAndOnlyFilePathIsRequestedAndOriginalNamesEnabled(): void
    {
        $configManager = self::getConfigManager();
        $configManager->set('oro_attachment.original_file_names_enabled', false);
        $configManager->set('oro_product.original_file_names_enabled', true);
        $configManager->flush();

        $productImage = $this->getProductImage(LoadProductData::PRODUCT_1);
        $productImageId = $productImage->getId();
        $fileId = $productImage->getImage()->getId();

        $response = $this->get(
            ['entity' => 'productimages', 'id' => (string)$productImageId],
            ['include' => 'image', 'fields[files]' => 'filePath']
        );

        $expectedData = self::updateExpectedData(
            $this->getResponseData('get_product_image_include_path_only_with_original_names.yml'),
            ['{fileId}' => (string)$fileId]
        );
        $this->assertResponseContains($expectedData, $response);
    }

    public function testGetWithIncludedImageAndWebpDisabled(): void
    {
        self::setWebpStrategy(WebpConfiguration::DISABLED);

        $productImage = $this->getProductImage(LoadProductData::PRODUCT_1);
        $productImageId = $productImage->getId();
        $fileId = $productImage->getImage()->getId();

        $response = $this->get(
            ['entity' => 'productimages', 'id' => (string)$productImageId],
            ['include' => 'image']
        );

        $expectedData = self::updateExpectedData(
            $this->getResponseData('get_product_image_include.yml'),
            ['{fileId}' => (string)$fileId]
        );
        foreach ($expectedData['included'][0]['attributes']['filePath'] as &$filePath) {
            unset($filePath['url_webp']);
        }
        unset($filePath);
        $this->assertResponseContains($expectedData, $response);
    }

    public function testGetWithIncludedImageAndWebpEnabledForAll(): void
    {
        self::setWebpStrategy(WebpConfiguration::ENABLED_FOR_ALL);

        $productImage = $this->getProductImage(LoadProductData::PRODUCT_1);
        $productImageId = $productImage->getId();
        $fileId = $productImage->getImage()->getId();

        $response = $this->get(
            ['entity' => 'productimages', 'id' => (string)$productImageId],
            ['include' => 'image']
        );

        $expectedData = self::updateExpectedData(
            $this->getResponseData('get_product_image_include_webp_enabled_for_all.yml'),
            ['{fileId}' => (string)$fileId]
        );
        $this->assertResponseContains($expectedData, $response);
    }

    public function testGetProductImageFile(): void
    {
        $fileId = $this->getProductImage(LoadProductData::PRODUCT_1)->getImage()->getId();

        $response = $this->get(
            ['entity' => 'files', 'id' => (string)$fileId],
            ['include' => 'image']
        );

        $expectedData = self::updateExpectedData(
            $this->getResponseData('get_product_image_include.yml'),
            ['{fileId}' => (string)$fileId]
        );
        $expectedData = ['data' => $expectedData['included'][0]];
        $this->assertResponseContains($expectedData, $response);
    }

    public function testDeleteAction(): void
    {
        /** @var Product $product */
        $product = $this->getReference(LoadProductData::PRODUCT_1);
        $productImageId = $this->getProductImage(LoadProductData::PRODUCT_1)->getId();

        $this->delete(['entity' => 'productimages', 'id' => (string)$productImageId]);

        self::assertNull(
            $this->getEntityManager()->find(ProductImage::class, $productImageId)
        );

        // Deletion is an orphan removal too; the product must still be reindexed.
        self::assertMessagesCount(WebsiteSearchReindexTopic::getName(), 1);
        self::assertMessageSent(
            WebsiteSearchReindexTopic::getName(),
            [
                'class' => [Product::class],
                'granulize' => true,
                'context' => [
                    'entityIds' => [$product->getId()],
                    'websiteIds' => [self::getDefaultWebsiteId()],
                    AbstractIndexer::CONTEXT_FIELD_GROUPS => ['image'],
                ],
            ]
        );
    }

    public function testCreateProductImageWithoutTypes(): void
    {
        /** @var Product $product */
        $product = $this->getReference(LoadProductData::PRODUCT_2);

        $this->post(
            ['entity' => 'productimages'],
            [
                'data' => [
                    'type' => 'productimages',
                    'relationships' => [
                        'product' => [
                            'data' => ['type' => 'products', 'id' => (string)$product->getId()],
                        ],
                        'image' => [
                            'data' => ['type' => 'files', 'id' => 'new-file-1'],
                        ],
                    ],
                ],
                'included' => [
                    [
                        'type' => 'files',
                        'id' => 'new-file-1',
                        'attributes' => [
                            'mimeType' => 'image/jpeg',
                            'originalFilename' => 'bb-27671.jpg',
                            'fileSize' => 631,
                            'content' => self::ONE_PIXEL_JPEG_BASE64,
                        ],
                    ],
                ],
            ]
        );

        // No resize without types, but the product is still reindexed.
        self::assertEmptyMessages(ResizeProductImageTopic::getName());
        self::assertMessagesCount(WebsiteSearchReindexTopic::getName(), 1);
        self::assertMessageSent(
            WebsiteSearchReindexTopic::getName(),
            [
                'class' => [Product::class],
                'granulize' => true,
                'context' => [
                    'entityIds' => [$product->getId()],
                    'websiteIds' => [self::getDefaultWebsiteId()],
                    AbstractIndexer::CONTEXT_FIELD_GROUPS => ['image'],
                ],
            ]
        );
    }
}
