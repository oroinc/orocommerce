<?php

namespace Oro\Bundle\ProductBundle\Tests\Functional\Controller\Frontend;

use Oro\Bundle\FrontendTestFrameworkBundle\Migrations\Data\ORM\LoadCustomerUserData;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\ProductBundle\Tests\Functional\DataFixtures\LoadFrontendProductData;
use Oro\Bundle\ProductBundle\Tests\Functional\DataFixtures\LoadProductData;
use Oro\Bundle\ProductBundle\Tests\Functional\DataFixtures\LoadProductImageData;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;

class AjaxProductControllerTest extends WebTestCase
{
    protected function setUp()
    {
        $this->initClient(
            [],
            $this->generateBasicAuthHeader(LoadCustomerUserData::AUTH_USER, LoadCustomerUserData::AUTH_PW)
        );

        $this->loadFixtures([
            LoadFrontendProductData::class,
            LoadProductImageData::class,
        ]);
    }

    /**
     * @dataProvider productNamesBySkusDataProvider
     * @param array $skus
     * @param array $expectedData
     */
    public function testProductNamesBySkus(array $skus, array $expectedData)
    {
        $this->client->request(
            'POST',
            $this->getUrl('oro_product_frontend_ajax_names_by_skus'),
            ['skus' => $skus]
        );
        $result = $this->client->getResponse();
        $this->assertJsonResponseStatusCodeEquals($result, 200);

        $data = json_decode($result->getContent(), true);
        $this->assertEquals($expectedData, $data);
    }

    public function productNamesBySkusDataProvider()
    {
        return [
            'restricted' => [
                'skus'         => [
                    'not a sku',
                    LoadProductData::PRODUCT_1,
                    LoadProductData::PRODUCT_2,
                    LoadProductData::PRODUCT_3,
                    LoadProductData::PRODUCT_4,
                ],
                'expectedData' => [
                    LoadProductData::PRODUCT_1 => ['name' => 'product-1.names.default'],
                    LoadProductData::PRODUCT_2 => ['name' => 'product-2.names.default'],
                    LoadProductData::PRODUCT_3 => ['name' => 'product-3.names.default'],
                ],
            ],
            'allowed'    => [
                'skus'         => [
                    'not a sku',
                    LoadProductData::PRODUCT_1,
                    LoadProductData::PRODUCT_2,
                ],
                'expectedData' => [
                    LoadProductData::PRODUCT_1 => ['name' => 'product-1.names.default'],
                    LoadProductData::PRODUCT_2 => ['name' => 'product-2.names.default'],
                ],
            ],
        ];
    }

    /**
     * @dataProvider productImagesByIdDataProvider
     *
     * @param string|int $productReference
     * @param array      $expectedData
     */
    public function testProductImagesById($productReference, array $expectedData)
    {
        /** @var Product $product */
        $product = $this->getReference($productReference);
        $this->client->request(
            'GET',
            $this->getUrl(
                'oro_product_frontend_ajax_images_by_id',
                [
                    'id' => $product->getId(),
                    'filters' => ['product_gallery_popup'],
                ]
            )
        );
        $result = $this->client->getResponse();
        $this->assertJsonResponseStatusCodeEquals($result, 200);

        $data = json_decode($result->getContent(), true);
        array_walk_recursive($expectedData, function (&$value) use ($product) {
            if (!is_array($value)) {
                $value = sprintf($value, $this->getReference('img.'.$product->getSku())->getId());
            }
        });

        $this->assertEquals($expectedData, $data);
    }

    /**
     * @return array
     */
    public function productImagesByIdDataProvider()
    {
        return [
            'product has image' => [
                'productId' => LoadProductData::PRODUCT_1,
                'expectedData' => [
                    [
                        'product_gallery_popup' => '/media/cache/attachment/resize/%d/product_gallery_popup/product-1',
                    ],
                ],
            ],
            'product has no images' => [
                'productId' => LoadProductData::PRODUCT_3,
                'expectedData' => [],
            ],
        ];
    }

    public function testProductImagesByIdWhenProductIsMissing()
    {
        $this->client->request(
            'GET',
            $this->getUrl(
                'oro_product_frontend_ajax_images_by_id',
                [
                    'id' => 123456,
                    'filters' => ['product_gallery_popup'],
                ]
            )
        );
        $result = $this->client->getResponse();
        $this->assertJsonResponseStatusCodeEquals($result, 200);

        $data = json_decode($result->getContent(), true);
        $this->assertEquals([], $data);
    }

    public function testProductImagesByIdWhenFiltersNamesAreMissing()
    {
        /** @var Product $product */
        $product = $this->getReference(LoadProductData::PRODUCT_1);
        $this->client->request(
            'GET',
            $this->getUrl(
                'oro_product_frontend_ajax_images_by_id',
                [
                    'id' => $product->getId(),
                ]
            )
        );
        $result = $this->client->getResponse();
        $this->assertJsonResponseStatusCodeEquals($result, 200);

        $data = json_decode($result->getContent(), true);
        $this->assertEquals([], $data);
    }
}
