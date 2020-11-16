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
        $response = $this->cget(
            ['entity' => 'products'],
            ['filter' => ['id' => ['<toString(@product1->id)>', '<toString(@product3->id)>']]]
        );

        $this->assertResponseContains(
            [
                'data' => [
                    [
                        'type'       => 'products',
                        'id'         => '<toString(@product1->id)>',
                        'attributes' => [
                            'productAttributes' => [
                                'wysiwyg' => [
                                    'value' => 'Product 1 WYSIWYG Text',
                                    'style' => '<style></style>'
                                ]
                            ]
                        ]
                    ],
                    [
                        'type'       => 'products',
                        'id'         => '<toString(@product3->id)>',
                        'attributes' => [
                            'productAttributes' => [
                                'wysiwyg' => null
                            ]
                        ]
                    ]
                ]
            ],
            $response
        );
    }
}
