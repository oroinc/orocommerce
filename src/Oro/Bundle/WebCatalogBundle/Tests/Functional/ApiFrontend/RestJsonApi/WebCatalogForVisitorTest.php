<?php

namespace Oro\Bundle\WebCatalogBundle\Tests\Functional\ApiFrontend\RestJsonApi;

use Oro\Bundle\CustomerBundle\Tests\Functional\ApiFrontend\DataFixtures\LoadCustomerData;
use Oro\Bundle\FrontendBundle\Tests\Functional\ApiFrontend\FrontendRestJsonApiTestCase;
use Symfony\Component\HttpFoundation\Response;

class WebCatalogForVisitorTest extends FrontendRestJsonApiTestCase
{
    #[\Override]
    protected function setUp(): void
    {
        parent::setUp();
        $this->initializeVisitor();
        $this->loadFixtures([
            LoadCustomerData::class,
            '@OroWebCatalogBundle/Tests/Functional/ApiFrontend/DataFixtures/web_catalog.yml'
        ]);
    }

    public function testGetOptionsForList(): void
    {
        $response = $this->options(
            $this->getListRouteName(),
            ['entity' => 'webcatalogs'],
            [],
            false
        );
        self::assertResponseStatusCodeEquals($response, Response::HTTP_NOT_FOUND);
    }

    public function testTryToOptionsForItem(): void
    {
        $response = $this->options(
            $this->getItemRouteName(),
            ['entity' => 'webcatalogs', 'id' => '<toString(@catalog1->id)>']
        );
        self::assertAllowResponseHeader($response, 'OPTIONS, GET');
    }

    public function testTryToGetList(): void
    {
        $response = $this->cget(
            ['entity' => 'webcatalogs'],
            [],
            [],
            false
        );
        self::assertResponseStatusCodeEquals($response, Response::HTTP_NOT_FOUND);
    }

    public function testGet(): void
    {
        $response = $this->get(
            ['entity' => 'webcatalogs', 'id' => '<toString(@catalog1->id)>']
        );
        $this->assertResponseContains(
            [
                'data' => [
                    'type' => 'webcatalogs',
                    'id' => '<toString(@catalog1->id)>',
                    'attributes' => [
                        'name' => 'Web Catalog 1',
                        'description' => 'Web Catalog 1 Description',
                        'createdAt' => '@catalog1->createdAt->format("Y-m-d\TH:i:s\Z")',
                        'updatedAt' => '@catalog1->updatedAt->format("Y-m-d\TH:i:s\Z")'
                    ]
                ]
            ],
            $response
        );
    }

    public function testTryToCreate(): void
    {
        $response = $this->post(
            ['entity' => 'webcatalogs'],
            [],
            [],
            false
        );
        self::assertResponseStatusCodeEquals($response, Response::HTTP_NOT_FOUND);
    }

    public function testTryToUpdate(): void
    {
        $response = $this->patch(
            ['entity' => 'webcatalogs', 'id' => '<toString(@catalog1->id)>'],
            [],
            [],
            false
        );
        self::assertMethodNotAllowedResponse($response, 'OPTIONS, GET');
    }

    public function testTryToDelete(): void
    {
        $response = $this->delete(
            ['entity' => 'webcatalogs', 'id' => '<toString(@catalog1->id)>'],
            [],
            [],
            false
        );
        self::assertMethodNotAllowedResponse($response, 'OPTIONS, GET');
    }

    public function testTryToDeleteList(): void
    {
        $response = $this->cdelete(
            ['entity' => 'webcatalogs'],
            ['filter' => ['id' => '<toString(@catalog1->id)>']],
            [],
            false
        );
        self::assertResponseStatusCodeEquals($response, Response::HTTP_NOT_FOUND);
    }
}
