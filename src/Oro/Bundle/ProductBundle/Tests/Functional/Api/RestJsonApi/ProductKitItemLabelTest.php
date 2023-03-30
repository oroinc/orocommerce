<?php

namespace Oro\Bundle\ProductBundle\Tests\Functional\Api\RestJsonApi;

use Oro\Bundle\ApiBundle\Tests\Functional\RestJsonApiTestCase;
use Oro\Bundle\CustomerBundle\Tests\Functional\Api\Frontend\DataFixtures\LoadAdminCustomerUserData;

class ProductKitItemLabelTest extends RestJsonApiTestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->loadFixtures([
            LoadAdminCustomerUserData::class,
            '@OroProductBundle/Tests/Functional/Api/DataFixtures/product.yml',
        ]);
    }

    public function testTryCreateProductKitItemLabelSeparatelyFromKitItem(): void
    {
        $content = $this->getRequestData('create_product_kit_item_label.yml');

        $response = $this->post(['entity' => 'productkititemlabels'], $content, [], false);

        $this->assertResponseContainsValidationErrors(
            [
                [
                    'title' => 'access denied exception',
                    'detail' => 'Use API resource to create a product kit item. A product kit item label can be'
                        . ' created only together with a product kit item.',
                ],
            ],
            $response,
            403
        );
    }
}
