<?php

namespace Oro\Bundle\CatalogBundle\Tests\Functional\Api\Frontend\RestJsonApi;

use Oro\Bundle\CustomerBundle\Tests\Functional\Api\Frontend\DataFixtures\LoadCustomerData;
use Oro\Bundle\FrontendBundle\Tests\Functional\Api\FrontendRestJsonApiTestCase;

class CategoryForVisitorTest extends FrontendRestJsonApiTestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->enableVisitor();
        $this->loadFixtures([
            LoadCustomerData::class,
            '@OroCatalogBundle/Tests/Functional/Api/Frontend/DataFixtures/category.yml'
        ]);
    }

    public function testGetList()
    {
        $response = $this->cget(
            ['entity' => 'mastercatalogcategories']
        );

        $this->assertResponseContains('cget_category.yml', $response);
    }

    public function testGet()
    {
        $response = $this->get(
            ['entity' => 'mastercatalogcategories', 'id' => '<toString(@category1->id)>']
        );

        $this->assertResponseContains('get_category.yml', $response);
    }

    public function testTryToUpdate()
    {
        $data = [
            'data' => [
                'type'       => 'mastercatalogcategories',
                'id'         => '<toString(@category1->id)>',
                'attributes' => [
                    'title' => 'Updated Category'
                ]
            ]
        ];

        $response = $this->patch(
            ['entity' => 'mastercatalogcategories', 'id' => '<toString(@category1->id)>'],
            $data,
            [],
            false
        );

        self::assertMethodNotAllowedResponse($response, 'OPTIONS, GET');
    }

    public function testTryToCreate()
    {
        $data = [
            'data' => [
                'type'       => 'mastercatalogcategories',
                'attributes' => [
                    'title' => 'New Category'
                ]
            ]
        ];

        $response = $this->post(
            ['entity' => 'mastercatalogcategories'],
            $data,
            [],
            false
        );

        self::assertMethodNotAllowedResponse($response, 'OPTIONS, GET');
    }

    public function testTryToDelete()
    {
        $response = $this->delete(
            ['entity' => 'mastercatalogcategories', 'id' => '<toString(@category1->id)>'],
            [],
            [],
            false
        );

        self::assertMethodNotAllowedResponse($response, 'OPTIONS, GET');
    }

    public function testTryToDeleteList()
    {
        $response = $this->cdelete(
            ['entity' => 'mastercatalogcategories'],
            ['filter' => ['id' => '<toString(@category1->id)>']],
            [],
            false
        );

        self::assertMethodNotAllowedResponse($response, 'OPTIONS, GET');
    }
}
