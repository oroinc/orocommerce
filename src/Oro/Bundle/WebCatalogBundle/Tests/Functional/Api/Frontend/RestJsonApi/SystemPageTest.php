<?php

namespace Oro\Bundle\WebCatalogBundle\Tests\Functional\Api\Frontend\RestJsonApi;

use Oro\Bundle\CustomerBundle\Tests\Functional\Api\Frontend\DataFixtures\LoadAdminCustomerUserData;
use Oro\Bundle\FrontendBundle\Tests\Functional\Api\FrontendRestJsonApiTestCase;
use Symfony\Component\HttpFoundation\Response;

/**
 * @SuppressWarnings(PHPMD.TooManyMethods)
 * @SuppressWarnings(PHPMD.TooManyPublicMethods)
 * @SuppressWarnings(PHPMD.ExcessivePublicCount)
 */
class SystemPageTest extends FrontendRestJsonApiTestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->loadFixtures([LoadAdminCustomerUserData::class]);
    }

    public function testGetFrontendPage()
    {
        $response = $this->get(
            ['entity' => 'systempages', 'id' => 'oro_product_frontend_product_index']
        );
        $this->assertResponseContains(
            [
                'data' => [
                    'type'       => 'systempages',
                    'id'         => 'oro_product_frontend_product_index',
                    'attributes' => [
                        'url' => '/product/'
                    ]
                ]
            ],
            $response
        );
    }

    public function testGetDefaultFrontendPage()
    {
        $response = $this->get(
            ['entity' => 'systempages', 'id' => 'oro_frontend_root']
        );
        $this->assertResponseContains(
            [
                'data' => [
                    'type'       => 'systempages',
                    'id'         => 'oro_frontend_root',
                    'attributes' => [
                        'url' => '/'
                    ]
                ]
            ],
            $response
        );
    }

    public function testTryToGetFrontendPageWithParameters()
    {
        $response = $this->get(
            ['entity' => 'systempages', 'id' => 'oro_product_frontend_product_view'],
            [],
            [],
            false
        );
        self::assertResponseStatusCodeEquals($response, Response::HTTP_NOT_FOUND);
    }

    public function testTryToGetBackendPage()
    {
        $response = $this->get(
            ['entity' => 'systempages', 'id' => 'oro_product_index'],
            [],
            [],
            false
        );
        self::assertResponseStatusCodeEquals($response, Response::HTTP_NOT_FOUND);
    }

    public function testTryToGetDefaultBackendPage()
    {
        $response = $this->get(
            ['entity' => 'systempages', 'id' => 'oro_default'],
            [],
            [],
            false
        );
        self::assertResponseStatusCodeEquals($response, Response::HTTP_NOT_FOUND);
    }

    public function testOptionsForItemRoute()
    {
        $response = $this->options(
            $this->getItemRouteName(),
            ['entity' => 'systempages', 'id' => 'some_route']
        );
        self::assertAllowResponseHeader($response, 'OPTIONS, GET');
    }

    public function testOptionsForListRoute()
    {
        $response = $this->options(
            $this->getListRouteName(),
            ['entity' => 'systempages'],
            [],
            false
        );
        self::assertResponseStatusCodeEquals($response, Response::HTTP_NOT_FOUND);
    }

    public function testTryToGetList()
    {
        $response = $this->cget(
            ['entity' => 'systempages'],
            [],
            [],
            false
        );
        self::assertResponseStatusCodeEquals($response, Response::HTTP_NOT_FOUND);
    }

    public function testTryToUpdate()
    {
        $response = $this->patch(
            ['entity' => 'systempages', 'id' => 'oro_product_frontend_product_index'],
            [
                'data' => [
                    'type'       => 'systempages',
                    'id'         => 'oro_product_frontend_product_index',
                    'attributes' => [
                        'url' => 'Updated Url'
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
            ['entity' => 'systempages'],
            [
                'data' => [
                    'type'       => 'systempages',
                    'attributes' => [
                        'url' => 'New Url'
                    ]
                ]
            ],
            [],
            false
        );
        self::assertResponseStatusCodeEquals($response, Response::HTTP_NOT_FOUND);
    }

    public function testTryToDelete()
    {
        $response = $this->delete(
            ['entity' => 'systempages', 'id' => 'oro_product_frontend_product_index'],
            [],
            [],
            false
        );
        self::assertMethodNotAllowedResponse($response, 'OPTIONS, GET');
    }

    public function testTryToDeleteList()
    {
        $response = $this->cdelete(
            ['entity' => 'systempages'],
            ['filter' => ['id' => 'oro_product_frontend_product_index']],
            [],
            false
        );
        self::assertResponseStatusCodeEquals($response, Response::HTTP_NOT_FOUND);
    }
}
