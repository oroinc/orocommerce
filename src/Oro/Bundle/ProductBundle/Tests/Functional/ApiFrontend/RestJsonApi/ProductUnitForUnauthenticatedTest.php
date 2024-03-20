<?php

namespace Oro\Bundle\ProductBundle\Tests\Functional\ApiFrontend\RestJsonApi;

use Oro\Bundle\FrontendBundle\Tests\Functional\ApiFrontend\FrontendRestJsonApiTestCase;
use Symfony\Component\HttpFoundation\Response;

class ProductUnitForUnauthenticatedTest extends FrontendRestJsonApiTestCase
{
    public function testOptionsForList(): void
    {
        $response = $this->options(
            $this->getListRouteName(),
            ['entity' => 'productunits']
        );
        self::assertAllowResponseHeader($response, 'OPTIONS, GET');
    }

    public function testOptionsForItem(): void
    {
        $response = $this->options(
            $this->getItemRouteName(),
            ['entity' => 'productunits', 'id' => '1']
        );
        self::assertAllowResponseHeader($response, 'OPTIONS, GET');
    }

    public function testTryToGetList(): void
    {
        $response = $this->cget(
            ['entity' => 'productunits'],
            [],
            [],
            false
        );
        self::assertResponseStatusCodeEquals($response, Response::HTTP_UNAUTHORIZED);
    }

    public function testTryToGet(): void
    {
        $response = $this->get(
            ['entity' => 'productunits', 'id' => '1'],
            [],
            [],
            false
        );
        self::assertResponseStatusCodeEquals($response, Response::HTTP_UNAUTHORIZED);
    }

    public function testTryToUpdate(): void
    {
        $response = $this->patch(
            ['entity' => 'productunits', 'id' => '1'],
            [],
            [],
            false
        );
        self::assertResponseStatusCodeEquals($response, Response::HTTP_UNAUTHORIZED);
    }

    public function testTryToCreate(): void
    {
        $response = $this->post(
            ['entity' => 'productunits'],
            [],
            [],
            false
        );
        self::assertResponseStatusCodeEquals($response, Response::HTTP_UNAUTHORIZED);
    }

    public function testTryToDelete(): void
    {
        $response = $this->delete(
            ['entity' => 'productunits', 'id' => '1'],
            [],
            [],
            false
        );
        self::assertResponseStatusCodeEquals($response, Response::HTTP_UNAUTHORIZED);
    }

    public function testTryToDeleteList(): void
    {
        $response = $this->cdelete(
            ['entity' => 'productunits'],
            ['filter' => ['id' => '1']],
            [],
            false
        );
        self::assertResponseStatusCodeEquals($response, Response::HTTP_UNAUTHORIZED);
    }
}
