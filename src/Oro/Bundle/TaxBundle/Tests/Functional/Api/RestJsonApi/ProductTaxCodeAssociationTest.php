<?php

namespace Oro\Bundle\TaxBundle\Tests\Functional\Api\RestJsonApi;

use Oro\Bundle\ApiBundle\Tests\Functional\RestJsonApiTestCase;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\TaxBundle\Tests\Functional\DataFixtures\LoadProductTaxCodes;

/**
 * @dbIsolationPerTest
 */
class ProductTaxCodeAssociationTest extends RestJsonApiTestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->loadFixtures([LoadProductTaxCodes::class]);
    }

    public function testGetProduct(): void
    {
        $response = $this->get(
            ['entity' => 'products', 'id' => '<toString(@product-1->id)>']
        );
        $this->assertResponseContains(
            [
                'data' => [
                    'type'          => 'products',
                    'id'            => '<toString(@product-1->id)>',
                    'relationships' => [
                        'taxCode' => [
                            'data' => [
                                'type' => 'producttaxcodes',
                                'id'   => '<toString(@product_tax_code.TAX1->id)>'
                            ]
                        ]
                    ]
                ]
            ],
            $response
        );
    }

    public function testUpdateProduct(): void
    {
        $productId = $this->getReference('product-1')->getId();
        $productTaxCodeId = $this->getReference('product_tax_code.TAX2')->getId();
        $data = [
            'data' => [
                'type'          => 'products',
                'id'            => (string)$productId,
                'relationships' => [
                    'taxCode' => [
                        'data' => [
                            'type' => 'producttaxcodes',
                            'id'   => (string)$productTaxCodeId
                        ]
                    ]
                ]
            ]
        ];
        $response = $this->patch(
            ['entity' => 'products', 'id' => '<toString(@product-1->id)>'],
            $data
        );

        $this->assertResponseContains($data, $response);

        $product = $this->getEntityManager()->find(Product::class, $productId);
        self::assertEquals($productTaxCodeId, $product->getTaxCode()->getId());
    }
}
