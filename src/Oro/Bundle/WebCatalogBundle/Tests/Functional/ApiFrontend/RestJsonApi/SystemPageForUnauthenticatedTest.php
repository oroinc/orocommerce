<?php

namespace Oro\Bundle\WebCatalogBundle\Tests\Functional\ApiFrontend\RestJsonApi;

use Oro\Bundle\FrontendBundle\Tests\Functional\ApiFrontend\FrontendRestJsonApiTestCase;
use Symfony\Component\HttpFoundation\Response;

class SystemPageForUnauthenticatedTest extends FrontendRestJsonApiTestCase
{
    public function testTryToGetOptionsForList(): void
    {
        $response = $this->options(
            $this->getListRouteName(),
            ['entity' => 'systempages'],
            [],
            false
        );
        self::assertResponseStatusCodeEquals($response, Response::HTTP_NOT_FOUND);
    }

    public function testOptionsForItem(): void
    {
        $response = $this->options(
            $this->getItemRouteName(),
            ['entity' => 'systempages', 'id' => '1']
        );
        self::assertAllowResponseHeader($response, 'OPTIONS, GET');
    }

    public function testTryToGetList(): void
    {
        $response = $this->cget(
            ['entity' => 'systempages'],
            [],
            [],
            false
        );
        self::assertResponseStatusCodeEquals($response, Response::HTTP_UNAUTHORIZED);
    }

    public function testTryToGet(): void
    {
        $response = $this->get(
            ['entity' => 'systempages', 'id' => '1'],
            [],
            [],
            false
        );
        self::assertResponseStatusCodeEquals($response, Response::HTTP_UNAUTHORIZED);
    }

    public function testTryToUpdate(): void
    {
        $response = $this->patch(
            ['entity' => 'systempages', 'id' => '1'],
            [],
            [],
            false
        );
        self::assertResponseStatusCodeEquals($response, Response::HTTP_UNAUTHORIZED);
    }

    public function testTryToCreate(): void
    {
        $response = $this->post(
            ['entity' => 'systempages'],
            [],
            [],
            false
        );
        self::assertResponseStatusCodeEquals($response, Response::HTTP_UNAUTHORIZED);
    }

    public function testTryToDelete(): void
    {
        $response = $this->delete(
            ['entity' => 'systempages', 'id' => '1'],
            [],
            [],
            false
        );
        self::assertResponseStatusCodeEquals($response, Response::HTTP_UNAUTHORIZED);
    }

    public function testTryToDeleteList(): void
    {
        $response = $this->cdelete(
            ['entity' => 'systempages'],
            ['filter' => ['id' => '1']],
            [],
            false
        );
        self::assertResponseStatusCodeEquals($response, Response::HTTP_UNAUTHORIZED);
    }
}
