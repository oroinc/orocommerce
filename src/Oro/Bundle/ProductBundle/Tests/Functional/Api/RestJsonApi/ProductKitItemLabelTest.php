<?php

namespace Oro\Bundle\ProductBundle\Tests\Functional\Api\RestJsonApi;

use Oro\Bundle\ApiBundle\Tests\Functional\RestJsonApiTestCase;
use Symfony\Component\HttpFoundation\Response;

class ProductKitItemLabelTest extends RestJsonApiTestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->loadFixtures([
            '@OroProductBundle/Tests/Functional/Api/DataFixtures/product.yml'
        ]);
    }

    public function testTryToCreateProductKitItemLabelSeparatelyFromKitItem(): void
    {
        $response = $this->post(
            ['entity' => 'productkititemlabels'],
            'create_product_kit_item_label.yml',
            [],
            false
        );

        $this->assertResponseContainsValidationErrors(
            [
                [
                    'title' => 'access denied exception',
                    'detail' => 'Use API resource to create a product kit item. A product kit item label can be'
                        . ' created only together with a product kit item.'
                ]
            ],
            $response,
            Response::HTTP_FORBIDDEN
        );
    }
}
