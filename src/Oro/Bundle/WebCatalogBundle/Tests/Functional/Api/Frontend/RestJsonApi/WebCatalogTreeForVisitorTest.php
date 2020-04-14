<?php

namespace Oro\Bundle\WebCatalogBundle\Tests\Functional\Api\Frontend\RestJsonApi;

use Oro\Bundle\CustomerBundle\Tests\Functional\Api\Frontend\DataFixtures\LoadCustomerData;

class WebCatalogTreeForVisitorTest extends WebCatalogTreeTestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->enableVisitor();
        $this->loadFixtures([
            LoadCustomerData::class,
            '@OroWebCatalogBundle/Tests/Functional/Api/Frontend/DataFixtures/content_node.yml'
        ]);
        $this->switchToWebCatalog();
    }

    public function testGetList()
    {
        $response = $this->cget(
            ['entity' => 'webcatalogtree']
        );
        $this->assertResponseContains('cget_content_node.yml', $response);
    }

    public function testGet()
    {
        $response = $this->get(
            ['entity' => 'webcatalogtree', 'id' => '<toString(@catalog1_node11->id)>']
        );
        $this->assertResponseContains('get_content_node.yml', $response);
    }

    public function testTryToUpdate()
    {
        $response = $this->patch(
            ['entity' => 'webcatalogtree', 'id' => '<toString(@catalog1_node11->id)>'],
            [
                'data' => [
                    'type'       => 'webcatalogtree',
                    'id'         => '<toString(@catalog1_node11->id)>',
                    'attributes' => [
                        'title' => 'Updated Node'
                    ]
                ]
            ],
            [],
            false
        );
        self::assertMethodNotAllowedResponse($response, 'OPTIONS, GET');
    }

    public function testTryToCreate()
    {
        $response = $this->post(
            ['entity' => 'webcatalogtree'],
            [
                'data' => [
                    'type'       => 'webcatalogtree',
                    'attributes' => [
                        'title' => 'New Node'
                    ]
                ]
            ],
            [],
            false
        );
        self::assertMethodNotAllowedResponse($response, 'OPTIONS, GET');
    }

    public function testTryToDelete()
    {
        $response = $this->delete(
            ['entity' => 'webcatalogtree', 'id' => '<toString(@catalog1_node11->id)>'],
            [],
            [],
            false
        );
        self::assertMethodNotAllowedResponse($response, 'OPTIONS, GET');
    }

    public function testTryToDeleteList()
    {
        $response = $this->cdelete(
            ['entity' => 'webcatalogtree'],
            ['filter' => ['id' => '<toString(@catalog1_node11->id)>']],
            [],
            false
        );
        self::assertMethodNotAllowedResponse($response, 'OPTIONS, GET');
    }
}
