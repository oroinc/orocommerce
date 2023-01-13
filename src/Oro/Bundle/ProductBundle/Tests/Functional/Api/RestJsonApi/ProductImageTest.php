<?php

namespace Oro\Bundle\ProductBundle\Tests\Functional\Api\RestJsonApi;

use Oro\Bundle\ApiBundle\Tests\Functional\RestJsonApiTestCase;
use Oro\Bundle\AttachmentBundle\Tests\Functional\WebpConfigurationTrait;
use Oro\Bundle\AttachmentBundle\Tools\WebpConfiguration;
use Oro\Bundle\ConfigBundle\Tests\Functional\Traits\ConfigManagerAwareTestTrait;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\ProductBundle\Entity\ProductImage;
use Oro\Bundle\ProductBundle\Tests\Functional\DataFixtures\LoadProductData;

/**
 * @dbIsolationPerTest
 */
class ProductImageTest extends RestJsonApiTestCase
{
    use ConfigManagerAwareTestTrait;
    use WebpConfigurationTrait;

    protected function setUp(): void
    {
        parent::setUp();
        $this->loadFixtures([LoadProductData::class]);
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

    public function testGetList()
    {
        $response = $this->cget(
            ['entity' => 'productimages'],
            ['filter' => ['product' => '@product-1->id']]
        );

        $this->assertResponseContains('cget_product_image_filter_by_product.yml', $response);
    }

    public function testGetWithIncludedImage()
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

    public function testGetWithIncludedImageAndOnlyFilePathIsRequested()
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

    public function testGetWithIncludedImageAndOnlyFilePathIsRequestedAndOriginalNamesEnabled()
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

    public function testGetWithIncludedImageAndWebpDisabled()
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

    public function testGetWithIncludedImageAndWebpEnabledForAll()
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

    public function testGetProductImageFile()
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

    public function testDeleteAction()
    {
        $productImageId = $this->getProductImage(LoadProductData::PRODUCT_1)->getId();

        $this->delete(['entity' => 'productimages', 'id' => (string)$productImageId]);

        self::assertNull(
            $this->getEntityManager()->find(ProductImage::class, $productImageId)
        );
    }
}
