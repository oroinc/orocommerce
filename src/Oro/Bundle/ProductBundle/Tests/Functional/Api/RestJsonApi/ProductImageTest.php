<?php

namespace Oro\Bundle\ProductBundle\Tests\Functional\Api\RestJsonApi;

use Oro\Bundle\ApiBundle\Tests\Functional\RestJsonApiTestCase;
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

    /**
     * {@inheritdoc}
     */
    protected function setUp(): void
    {
        parent::setUp();
        $this->loadFixtures([LoadProductData::class]);
    }

    /**
     * @param array $expectedData
     * @param int   $fileId
     *
     * @return array
     */
    private static function updateExpectedData(array $expectedData, $fileId)
    {
        array_walk_recursive(
            $expectedData,
            function (&$val) use ($fileId) {
                if (is_string($val)) {
                    $val = str_replace('{fileId}', (string)$fileId, $val);
                }
            }
        );

        return self::processTemplateData($expectedData);
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
        /** @var Product $product */
        $product = $this->getReference(LoadProductData::PRODUCT_1);
        /** @var ProductImage $productImage */
        $productImage = $product->getImages()->first();
        $productImageId = $productImage->getId();
        $fileId = $productImage->getImage()->getId();

        $response = $this->get(
            ['entity' => 'productimages', 'id' => (string)$productImageId],
            ['include' => 'image']
        );

        $expectedData = self::updateExpectedData(
            $this->getResponseData('get_product_image_include.yml'),
            $fileId
        );
        $this->assertResponseContains($expectedData, $response);
    }

    public function testGetWithIncludedImageAndOnlyFilePathIsRequested()
    {
        /** @var Product $product */
        $product = $this->getReference(LoadProductData::PRODUCT_1);
        /** @var ProductImage $productImage */
        $productImage = $product->getImages()->first();
        $productImageId = $productImage->getId();
        $fileId = $productImage->getImage()->getId();

        $response = $this->get(
            ['entity' => 'productimages', 'id' => (string)$productImageId],
            ['include' => 'image', 'fields[files]' => 'filePath']
        );

        $expectedData = self::updateExpectedData(
            $this->getResponseData('get_product_image_include_path_only.yml'),
            $fileId
        );
        $this->assertResponseContains($expectedData, $response);
    }

    public function testGetWithIncludedImageAndOnlyFilePathIsRequestedAndOriginalNamesEnabled()
    {
        $configManager = self::getConfigManager('global');
        $configManager->set('oro_product.original_file_names_enabled', true);
        $configManager->flush();

        /** @var Product $product */
        $product = $this->getReference(LoadProductData::PRODUCT_1);
        /** @var ProductImage $productImage */
        $productImage = $product->getImages()->first();
        $productImageId = $productImage->getId();
        $fileId = $productImage->getImage()->getId();

        $response = $this->get(
            ['entity' => 'productimages', 'id' => (string)$productImageId],
            ['include' => 'image', 'fields[files]' => 'filePath']
        );

        $expectedData = self::updateExpectedData(
            $this->getResponseData('get_product_image_include_path_only_with_original_names.yml'),
            $fileId
        );
        $this->assertResponseContains($expectedData, $response);
    }

    public function testDeleteAction()
    {
        /** @var Product $product */
        $product = $this->getReference(LoadProductData::PRODUCT_1);
        $productImageId = $product->getImages()->first()->getId();

        $this->delete(['entity' => 'productimages', 'id' => (string)$productImageId]);

        self::assertNull(
            $this->getEntityManager()->find(ProductImage::class, $productImageId)
        );
    }
}
