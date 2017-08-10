<?php

namespace Oro\Bundle\ProductBundle\Tests\Functional\Api;

use Oro\Bundle\ApiBundle\Tests\Functional\RestJsonApiTestCase;
use Oro\Bundle\ProductBundle\Entity\ProductImage;
use Oro\Bundle\ProductBundle\Tests\Functional\DataFixtures\LoadProductData;

class ProductImageApiTest extends RestJsonApiTestCase
{
    /**
     * {@inheritdoc}
     */
    protected function setUp()
    {
        parent::setUp();
        $this->loadFixtures([LoadProductData::class]);
    }

    /**
     * @param array $parameters
     * @param string $expectedDataFileName
     *
     * @dataProvider getListDataProvider
     */
    public function testGetList(array $parameters, $expectedDataFileName)
    {
        $response = $this->cget(['entity' => 'productimages'], $parameters);

        $this->assertResponseContains($expectedDataFileName, $response);
    }

    /**
     * @return array
     */
    public function getListDataProvider()
    {
        return [
            'filter by Product' => [
                'parameters' => [
                    'filter' => [
                        'product' => '@product-1->id',
                    ],
                ],
                'expectedDataFileName' => 'cget_product_image_filter_by_product.yml',
            ],
        ];
    }

    public function testDeleteAction()
    {
        $product = $this->getReference(LoadProductData::PRODUCT_1);
        $firstProductImageId = (string) $product
            ->getImages()
            ->first()
            ->getId();

        $this->delete(
            ['entity' => 'productimages', 'id' => $firstProductImageId]);

        $this->assertNull(
            $this->getEntityManager()->find(ProductImage::class ,$firstProductImageId )
        );
    }
}
