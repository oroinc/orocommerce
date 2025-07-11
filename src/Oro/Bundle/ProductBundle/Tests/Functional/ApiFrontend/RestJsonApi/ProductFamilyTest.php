<?php

namespace Oro\Bundle\ProductBundle\Tests\Functional\ApiFrontend\RestJsonApi;

use Oro\Bundle\CustomerBundle\Tests\Functional\ApiFrontend\DataFixtures\LoadAdminCustomerUserData;
use Oro\Bundle\FrontendBundle\Tests\Functional\ApiFrontend\FrontendRestJsonApiTestCase;
use Symfony\Component\HttpFoundation\Response;

class ProductFamilyTest extends FrontendRestJsonApiTestCase
{
    #[\Override]
    protected function setUp(): void
    {
        parent::setUp();
        $this->loadFixtures([
            LoadAdminCustomerUserData::class,
            '@OroProductBundle/Tests/Functional/ApiFrontend/DataFixtures/product_family.yml'
        ]);
    }

    public function testGetList()
    {
        $response = $this->cget(
            ['entity' => 'productfamilies']
        );

        $this->assertResponseContains('cget_product_family.yml', $response);
    }

    public function testGetListFilterBySeveralIds()
    {
        $response = $this->cget(
            ['entity' => 'productfamilies'],
            ['filter' => ['id' => ['<toString(@family1->id)>', '<toString(@family2->id)>']]]
        );

        $this->assertResponseContains('cget_product_family_filter_by_ids.yml', $response);
    }

    public function testGet()
    {
        $response = $this->get(
            ['entity' => 'productfamilies', 'id' => '<toString(@family1->id)>']
        );

        $this->assertResponseContains('get_product_family.yml', $response);

        $responseContent = self::jsonToArray($response->getContent());
        self::assertArrayNotHasKey('entityClass', $responseContent['data']['attributes']);
        self::assertArrayNotHasKey('isEnabled', $responseContent['data']['attributes']);
        self::assertArrayNotHasKey('code', $responseContent['data']['attributes']);
        self::assertArrayNotHasKey('image', $responseContent['data']['attributes']);
        self::assertArrayNotHasKey('relationships', $responseContent['data']);
    }

    public function testTryToGetNotProductFamily()
    {
        $response = $this->get(
            ['entity' => 'productfamilies', 'id' => '<toString(@not_product_family->id)>'],
            [],
            [],
            false
        );
        self::assertResponseStatusCodeEquals($response, Response::HTTP_NOT_FOUND);
    }

    public function testTryToUpdate()
    {
        $data = [
            'data' => [
                'type'       => 'productfamilies',
                'id'         => '<toString(@family1->id)>',
                'attributes' => [
                    'name' => 'test'
                ]
            ]
        ];

        $response = $this->patch(
            ['entity' => 'productfamilies', 'id' => '<toString(@family1->id)>'],
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
                'type'       => 'productfamilies',
                'attributes' => [
                    'name' => 'test'
                ]
            ]
        ];

        $response = $this->post(
            ['entity' => 'productfamilies'],
            $data,
            [],
            false
        );

        self::assertMethodNotAllowedResponse($response, 'OPTIONS, GET');
    }

    public function testTryToDelete()
    {
        $response = $this->delete(
            ['entity' => 'productfamilies', 'id' => '<toString(@family1->id)>'],
            [],
            [],
            false
        );

        self::assertMethodNotAllowedResponse($response, 'OPTIONS, GET');
    }

    public function testTryToDeleteList()
    {
        $response = $this->cdelete(
            ['entity' => 'productfamilies'],
            ['filter' => ['id' => '<toString(@family1->id)>']],
            [],
            false
        );

        self::assertMethodNotAllowedResponse($response, 'OPTIONS, GET');
    }
}
