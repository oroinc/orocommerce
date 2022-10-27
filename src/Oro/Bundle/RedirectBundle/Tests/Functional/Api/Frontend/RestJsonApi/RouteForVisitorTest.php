<?php

namespace Oro\Bundle\RedirectBundle\Tests\Functional\Api\Frontend\RestJsonApi;

use Oro\Bundle\FrontendBundle\Tests\Functional\Api\FrontendRestJsonApiTestCase;

class RouteForVisitorTest extends FrontendRestJsonApiTestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->enableVisitor();
        $this->loadVisitor();
    }

    private function getRouteId(string $pathIfo): string
    {
        return str_replace('/', ':', $pathIfo);
    }

    public function testGetForRootPageUrl()
    {
        $response = $this->get(
            ['entity' => 'routes', 'id' => $this->getRouteId('/')]
        );
        $this->assertResponseContains(
            [
                'data' => [
                    'type'       => 'routes',
                    'id'         => ':',
                    'attributes' => [
                        'url'                => '/',
                        'isSlug'             => false,
                        'redirectUrl'        => null,
                        'redirectStatusCode' => null,
                        'resourceType'       => 'system_page',
                        'apiUrl'             => '/api/systempages/oro_frontend_root'
                    ]
                ]
            ],
            $response
        );
    }

    public function testGetForSystemPageUrl()
    {
        $response = $this->get(
            ['entity' => 'routes', 'id' => $this->getRouteId('/customer/user')]
        );
        $this->assertResponseContains(
            [
                'data' => [
                    'type'       => 'routes',
                    'id'         => ':customer:user',
                    'attributes' => [
                        'url'                => '/customer/user/',
                        'isSlug'             => false,
                        'redirectUrl'        => null,
                        'redirectStatusCode' => null,
                        'resourceType'       => 'system_page',
                        'apiUrl'             => '/api/systempages/oro_customer_frontend_customer_user_index'
                    ]
                ]
            ],
            $response
        );
    }
}
