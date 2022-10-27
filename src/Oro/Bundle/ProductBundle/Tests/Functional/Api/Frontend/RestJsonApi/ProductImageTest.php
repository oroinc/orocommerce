<?php

namespace Oro\Bundle\ProductBundle\Tests\Functional\Api\Frontend\RestJsonApi;

use Oro\Bundle\AttachmentBundle\Tests\Functional\WebpConfigurationTrait;
use Oro\Bundle\AttachmentBundle\Tools\WebpConfiguration;
use Oro\Bundle\CustomerBundle\Tests\Functional\Api\Frontend\DataFixtures\LoadAdminCustomerUserData;
use Oro\Bundle\FrontendBundle\Tests\Functional\Api\FrontendRestJsonApiTestCase;
use Oro\Bundle\ProductBundle\Entity\ProductImage;
use Symfony\Component\HttpFoundation\Response;

/**
 * @dbIsolationPerTest
 * @SuppressWarnings(PHPMD.TooManyMethods)
 * @SuppressWarnings(PHPMD.TooManyPublicMethods)
 * @SuppressWarnings(PHPMD.ExcessivePublicCount)
 */
class ProductImageTest extends FrontendRestJsonApiTestCase
{
    use WebpConfigurationTrait;

    protected function setUp(): void
    {
        parent::setUp();
        $this->loadFixtures([
            LoadAdminCustomerUserData::class,
            '@OroProductBundle/Tests/Functional/Api/Frontend/DataFixtures/product.yml'
        ]);
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

    public function testGetList()
    {
        $response = $this->cget(
            ['entity' => 'productimages']
        );

        $this->assertResponseContains('cget_product_image.yml', $response);
    }

    public function testGetListFilteredByProduct()
    {
        $file1Id = $this->getReference('product1_image1')->getImage()->getId();
        $file2Id = $this->getReference('product1_image2')->getImage()->getId();

        $response = $this->cget(
            ['entity' => 'productimages'],
            ['filter' => ['product' => '<toString(@product1->id)>']]
        );

        $expectedData = self::updateExpectedData(
            $this->getResponseData('cget_product_image_filter_by_product.yml'),
            ['{file1Id}' => (string)$file1Id, '{file2Id}' => (string)$file2Id]
        );
        $this->assertResponseContains($expectedData, $response);
    }

    public function testGetListFilteredByTypesWithOperatorEquals()
    {
        $response = $this->cget(
            ['entity' => 'productimages'],
            ['filter' => ['types' => ['main', 'listing']]]
        );

        $this->assertResponseContains(
            [
                'data' => [
                    [
                        'type'       => 'productimages',
                        'id'         => '<toString(@product1_image1->id)>',
                        'attributes' => ['types' => ['main', 'additional']]
                    ],
                    [
                        'type'       => 'productimages',
                        'id'         => '<toString(@product1_image2->id)>',
                        'attributes' => ['types' => ['listing', 'additional']]
                    ],
                    [
                        'type'       => 'productimages',
                        'id'         => '<toString(@product3_image1->id)>',
                        'attributes' => ['types' => ['main', 'listing']]
                    ]
                ]
            ],
            $response
        );
    }

    public function testGetListFilteredByTypesWithOperatorNotEquals()
    {
        $response = $this->cget(
            ['entity' => 'productimages'],
            ['filter' => ['types' => ['neq' => ['main', 'listing']]]]
        );

        $this->assertResponseContains(
            [
                'data' => [
                    [
                        'type'       => 'productimages',
                        'id'         => '<toString(@product3_image2->id)>',
                        'attributes' => ['types' => ['additional']]
                    ]
                ]
            ],
            $response
        );
    }

    public function testGetListFilteredByTypesWithOperatorExistsEqualsToTrue()
    {
        $response = $this->cget(
            ['entity' => 'productimages'],
            ['filter' => ['types' => ['exists' => 'yes']]]
        );

        $this->assertResponseContains(
            [
                'data' => [
                    [
                        'type'       => 'productimages',
                        'id'         => '<toString(@product1_image1->id)>',
                        'attributes' => ['types' => ['main', 'additional']]
                    ],
                    [
                        'type'       => 'productimages',
                        'id'         => '<toString(@product1_image2->id)>',
                        'attributes' => ['types' => ['listing', 'additional']]
                    ],
                    [
                        'type'       => 'productimages',
                        'id'         => '<toString(@product3_image1->id)>',
                        'attributes' => ['types' => ['main', 'listing']]
                    ],
                    [
                        'type'       => 'productimages',
                        'id'         => '<toString(@product3_image2->id)>',
                        'attributes' => ['types' => ['additional']]
                    ]
                ]
            ],
            $response
        );
    }

    public function testGetListFilteredByTypesWithOperatorExistsEqualsToFalse()
    {
        $response = $this->cget(
            ['entity' => 'productimages'],
            ['filter' => ['types' => ['exists' => 'no']]]
        );

        $this->assertResponseContains(
            [
                'data' => [
                    [
                        'type'       => 'productimages',
                        'id'         => '<toString(@product3_image3->id)>',
                        'attributes' => ['types' => []]
                    ]
                ]
            ],
            $response
        );
    }

    public function testGetListFilteredByTypesWithOperatorNotEqualsOrNull()
    {
        $response = $this->cget(
            ['entity' => 'productimages'],
            ['filter' => ['types' => ['neq_or_null' => ['main', 'listing']]]]
        );

        $this->assertResponseContains(
            [
                'data' => [
                    [
                        'type'       => 'productimages',
                        'id'         => '<toString(@product3_image2->id)>',
                        'attributes' => ['types' => ['additional']]
                    ],
                    [
                        'type'       => 'productimages',
                        'id'         => '<toString(@product3_image3->id)>',
                        'attributes' => ['types' => []]
                    ]
                ]
            ],
            $response
        );
    }

    public function testGetListFilteredByTypesWithOperatorContains()
    {
        $response = $this->cget(
            ['entity' => 'productimages'],
            ['filter' => ['types' => ['contains' => ['main', 'listing']]]]
        );

        $this->assertResponseContains(
            [
                'data' => [
                    [
                        'type'       => 'productimages',
                        'id'         => '<toString(@product3_image1->id)>',
                        'attributes' => ['types' => ['main', 'listing']]
                    ]
                ]
            ],
            $response
        );
    }

    public function testGetListFilteredByTypesWithOperatorNotContains()
    {
        $response = $this->cget(
            ['entity' => 'productimages'],
            ['filter' => ['types' => ['not_contains' => ['main', 'listing']]]]
        );

        $this->assertResponseContains(
            [
                'data' => [
                    [
                        'type'       => 'productimages',
                        'id'         => '<toString(@product3_image2->id)>',
                        'attributes' => ['types' => ['additional']]
                    ],
                    [
                        'type'       => 'productimages',
                        'id'         => '<toString(@product3_image3->id)>',
                        'attributes' => ['types' => []]
                    ]
                ]
            ],
            $response
        );
    }

    public function testGet()
    {
        /** @var ProductImage $productImage */
        $productImage = $this->getReference('product1_image1');
        $fileId = $productImage->getImage()->getId();

        $response = $this->get(
            ['entity' => 'productimages', 'id' => (string)$productImage->getId()]
        );

        $expectedData = self::updateExpectedData(
            $this->getResponseData('get_product_image.yml'),
            ['{fileId}' => (string)$fileId]
        );
        $this->assertResponseContains($expectedData, $response);
    }

    public function testGetAndWebpDisabled()
    {
        self::setWebpStrategy(WebpConfiguration::DISABLED);

        /** @var ProductImage $productImage */
        $productImage = $this->getReference('product1_image1');
        $fileId = $productImage->getImage()->getId();

        $response = $this->get(
            ['entity' => 'productimages', 'id' => (string)$productImage->getId()]
        );

        $expectedData = self::updateExpectedData(
            $this->getResponseData('get_product_image.yml'),
            ['{fileId}' => (string)$fileId]
        );
        foreach ($expectedData['data']['attributes']['files'] as &$file) {
            unset($file['url_webp']);
        }
        unset($file);
        $this->assertResponseContains($expectedData, $response);
    }

    public function testGetAndWebpEnabledForAll()
    {
        self::setWebpStrategy(WebpConfiguration::ENABLED_FOR_ALL);

        /** @var ProductImage $productImage */
        $productImage = $this->getReference('product1_image1');
        $fileId = $productImage->getImage()->getId();

        $response = $this->get(
            ['entity' => 'productimages', 'id' => (string)$productImage->getId()]
        );

        $expectedData = self::updateExpectedData(
            $this->getResponseData('get_product_image_webp_enabled_for_all.yml'),
            ['{fileId}' => (string)$fileId]
        );
        $this->assertResponseContains($expectedData, $response);
    }

    public function testTryToGetForNotAccessibleProduct()
    {
        $response = $this->get(
            ['entity' => 'productimages', 'id' => '<toString(@product2_image1->id)>'],
            [],
            [],
            false
        );

        self::assertResponseStatusCodeEquals($response, Response::HTTP_NOT_FOUND);
    }

    public function testTryToUpdate()
    {
        $data = [
            'data' => [
                'type'       => 'productimages',
                'id'         => '<toString(@product1_image1->id)>',
                'attributes' => [
                    'originalImageName' => 'newFileName1.jpg'
                ]
            ]
        ];

        $response = $this->patch(
            ['entity' => 'productimages', 'id' => '<toString(@product1_image1->id)>'],
            $data,
            [],
            false
        );

        self::assertMethodNotAllowedResponse($response, 'OPTIONS, GET');
    }

    public function testTryToCreate()
    {
        $data = [
            'data' => [
                'type'       => 'productimages',
                'attributes' => [
                    'originalImageName' => 'someFileName1.jpg'
                ]
            ]
        ];

        $response = $this->post(
            ['entity' => 'productimages'],
            $data,
            [],
            false
        );

        self::assertMethodNotAllowedResponse($response, 'OPTIONS, GET');
    }

    public function testTryToDelete()
    {
        $response = $this->delete(
            ['entity' => 'productimages', 'id' => '<toString(@product1_image1->id)>'],
            [],
            [],
            false
        );

        self::assertMethodNotAllowedResponse($response, 'OPTIONS, GET');
    }

    public function testTryToDeleteList()
    {
        $response = $this->cdelete(
            ['entity' => 'productimages'],
            ['filter' => ['id' => '<toString(@product1_image1->id)>']],
            [],
            false
        );

        self::assertMethodNotAllowedResponse($response, 'OPTIONS, GET');
    }

    public function testGetSubresourceForProducts()
    {
        $response = $this->getSubresource(
            ['entity' => 'productimages', 'id' => '<toString(@product1_image1->id)>', 'association' => 'product']
        );

        $this->assertResponseContains(
            [
                'data' => [
                    'type'       => 'products',
                    'id'         => '<toString(@product1->id)>',
                    'attributes' => [
                        'sku' => 'PSKU1'
                    ]
                ]
            ],
            $response
        );
    }

    public function testGetRelationshipForProducts()
    {
        $response = $this->getRelationship(
            ['entity' => 'productimages', 'id' => '<toString(@product1_image1->id)>', 'association' => 'product']
        );

        $this->assertResponseContains(
            [
                'data' => [
                    'type' => 'products',
                    'id'   => '<toString(@product1->id)>'
                ]
            ],
            $response
        );
    }
}
