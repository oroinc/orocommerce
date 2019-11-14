<?php

namespace Oro\Bundle\ProductBundle\Tests\Functional\Api\RestJsonApi;

use Oro\Bundle\ApiBundle\Tests\Functional\RestJsonApiTestCase;
use Oro\Bundle\CustomerBundle\Tests\Functional\Api\Frontend\DataFixtures\LoadAdminCustomerUserData;
use Oro\Bundle\ProductBundle\Entity\Product;

class ProductVariantUniqueTest extends RestJsonApiTestCase
{
    /**
     * {@inheritdoc}
     */
    protected function setUp()
    {
        parent::setUp();
        $this->loadFixtures([
            LoadAdminCustomerUserData::class,
            '@OroProductBundle/Tests/Functional/Api/DataFixtures/product.yml',
            '@OroProductBundle/Tests/Functional/Api/DataFixtures/simple_product.yml',
        ]);
    }

    public function testAddSimpleProductWithoutAttributes()
    {
        $response = $this->post(
            ['entity' => 'productvariantlinks'],
            'create_product_variant_link_fail_no_attributes.yml',
            [],
            false
        );

        $this->assertResponseValidationError(
            [
                'status' => '400',
                'title' => 'product variant links constraint',
                'detail' => 'Can\'t save product variants. ' .
                    'Product "PSKU2" has no filled field(s) "testAttrInteger, testAttrFloat" ',
            ],
            $response
        );
    }

    public function testAddSimpleProductWithTheSameConfigureAttribute()
    {
        $response = $this->post(
            ['entity' => 'productvariantlinks'],
            'create_product_variant_link_fail.yml',
            [],
            false
        );

        $this->assertResponseValidationError(
            [
                'status' => '400',
                'title' => 'unique product variant links constraint',
                'detail' => "Can't save product variants. Configurable attribute combinations should be unique."
            ],
            $response
        );
    }

    /**
     * @dataProvider failedUpdateDataProvider
     * @param string $requestFile
     */
    public function testChangeSimpleProductToProductWithTheSameConfigureAttribute($requestFile)
    {
        $response = $this->patch(
            ['entity' => 'productvariantlinks', 'id' => '<toString(@configurable_product1_variant1_link->id)>'],
            $requestFile,
            [],
            false
        );

        $this->assertResponseValidationError(
            [
                'status' => '400',
                'title' => 'unique product variant links constraint',
                'detail' => "Can't save product variants. Configurable attribute combinations should be unique."
            ],
            $response
        );
    }

    /**
     * @return array
     */
    public function failedUpdateDataProvider()
    {
        return [
            ['update_product_variant_link_fail.yml'],
            ['update_partial_product_variant_link_fail.yml']
        ];
    }

    /**
     * @dataProvider failedUpdateWithoutAttributesDataProvider
     * @param string $requestFile
     */
    public function testChangeSimpleProductToProductWithNoAttributes($requestFile)
    {
        $response = $this->patch(
            ['entity' => 'productvariantlinks', 'id' => '<toString(@configurable_product1_variant2_link->id)>'],
            $requestFile,
            [],
            false
        );

        $this->assertResponseValidationError(
            [
                'status' => '400',
                'title' => 'product variant links constraint',
                'detail' => 'Can\'t save product variants. ' .
                    'Product "PSKU2" has no filled field(s) "testAttrInteger, testAttrFloat" ',
            ],
            $response
        );
    }

    /**
     * @return array
     */
    public function failedUpdateWithoutAttributesDataProvider()
    {
        return [
            ['update_product_variant_link_fail_no_attributes.yml'],
            ['update_partial_product_variant_link_fail_no_attributes.yml']
        ];
    }

    public function testAddSimpleProductWithAnotherConfigureAttribute()
    {
        $this->post(
            ['entity' => 'productvariantlinks'],
            'create_product_variant_link_success.yml'
        );

        /** @var Product $productSimpleWhichWasAdd */
        $productSimpleWhichWasAdd = $this->getReference('simple_product4');
        /** @var Product $productConf */
        $productConf = $this->getReference('configurable_product1');

        $simpleProductIds = [];
        foreach ($productConf->getVariantLinks() as $productVariantLink) {
            $simpleProductIds[] = $productVariantLink->getProduct()->getId();
        }

        $this->assertContains($productSimpleWhichWasAdd->getId(), $simpleProductIds);
    }

    public function testChangeSimpleProductToProductWithAnotherConfigureAttribute()
    {
        $this->patch(
            ['entity' => 'productvariantlinks', 'id' => '<toString(@configurable_product1_variant2_link->id)>'],
            'update_product_variant_link_success.yml'
        );

        /** @var Product $productSimpleWhichWasChange */
        $productSimpleWhichWasChange = $this->getReference('simple_product4');
        /** @var Product $productConf */
        $productConf = $this->getReference('configurable_product1');

        $simpleProductIds = [];
        foreach ($productConf->getVariantLinks() as $productVariantLink) {
            $simpleProductIds[] = $productVariantLink->getProduct()->getId();
        }

        $this->assertContains($productSimpleWhichWasChange->getId(), $simpleProductIds);
    }
}
