<?php

namespace Oro\Bundle\ProductBundle\Tests\Functional\Api\RestJsonApi;

use Oro\Bundle\ApiBundle\Tests\Functional\RestJsonApiTestCase;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\ProductBundle\Tests\Functional\DataFixtures\LoadProductDataWithAttributeFamilies;
use Oro\Bundle\ProductBundle\Tests\Functional\DataFixtures\LoadProductVariantsWithAttributeFamilies;

class DefaultProductVariantTest extends RestJsonApiTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->loadFixtures([
            LoadProductVariantsWithAttributeFamilies::class,
        ]);
    }

    public function testSetDefaultVariantValid()
    {
        $this->patch(
            ['entity' => 'products', 'id' => '<toString(@product-8->id)>'],
            'update_product_default_variant_valid.yml'
        );

        /** @var Product $configurableProduct */
        $configurableProduct = $this->getReference(LoadProductDataWithAttributeFamilies::PRODUCT_8);
        $productVariant = $this->getReference(LoadProductDataWithAttributeFamilies::PRODUCT_1);

        $this->assertSame($productVariant, $configurableProduct->getDefaultVariant());
    }

    public function testSetDefaultVariantInvalid()
    {
        $response = $this->patch(
            ['entity' => 'products', 'id' => '<toString(@product-8->id)>'],
            'update_product_default_variant_invalid.yml',
            [],
            false
        );

        $this->assertResponseValidationError(
            [
                'title'  => 'default product variant constraint',
                'detail' => 'The default variant must be one of the selected product variants.'
            ],
            $response
        );
    }
}
