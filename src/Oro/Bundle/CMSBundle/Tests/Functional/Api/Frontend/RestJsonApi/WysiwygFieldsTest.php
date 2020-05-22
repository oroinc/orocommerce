<?php

namespace Oro\Bundle\CMSBundle\Tests\Functional\Api\Frontend\RestJsonApi;

use Oro\Bundle\CustomerBundle\Tests\Functional\Api\Frontend\DataFixtures\LoadAdminCustomerUserData;
use Oro\Bundle\FrontendBundle\Tests\Functional\Api\FrontendRestJsonApiTestCase;

class WysiwygFieldsTest extends FrontendRestJsonApiTestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->loadFixtures([
            LoadAdminCustomerUserData::class,
            '@OroProductBundle/Tests/Functional/Api/Frontend/DataFixtures/product.yml'
        ]);
    }

    public function testGetEntityWithWYSIWYGFields(): void
    {
        $response = $this->get(
            ['entity' => 'products', 'id' => '<toString(@product1->id)>']
        );

        $this->assertResponseContains(
            [
                'data' => [
                    'attributes' => [
                        'productAttributes' => [
                            'wysiwyg' => [
                                'value' => null,
                                'style' => null
                            ]
                        ]
                    ]
                ]
            ],
            $response
        );
    }
}
