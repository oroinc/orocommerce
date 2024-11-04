<?php

namespace Oro\Bundle\CMSBundle\Tests\Functional\ApiFrontend\RestJsonApi;

use Oro\Bundle\CustomerBundle\Tests\Functional\ApiFrontend\DataFixtures\LoadAdminCustomerUserData;
use Oro\Bundle\FrontendBundle\Tests\Functional\ApiFrontend\FrontendRestJsonApiTestCase;

class WysiwygFieldsTest extends FrontendRestJsonApiTestCase
{
    #[\Override]
    protected function setUp(): void
    {
        parent::setUp();
        $this->loadFixtures([
            LoadAdminCustomerUserData::class,
            '@OroProductBundle/Tests/Functional/ApiFrontend/DataFixtures/product.yml'
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
                                'wysiwyg'     => '<style type="text/css">.test {color: red}</style>'
                                    . 'Product 1 WYSIWYG Text. Twig Expr: "test".',
                                'wysiwygAttr' => '<style type="text/css">.test {color: red}</style>'
                                    . 'Product 1 WYSIWYG Attr Text. Twig Expr: "test".'
                            ]
                        ]
                    ],
                    [
                        'type'       => 'products',
                        'id'         => '<toString(@product3->id)>',
                        'attributes' => [
                            'productAttributes' => [
                                'wysiwyg'     => null,
                                'wysiwygAttr' => null
                            ]
                        ]
                    ]
                ]
            ],
            $response
        );
    }
}
