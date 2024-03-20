<?php

namespace Oro\Bundle\ProductBundle\Tests\Functional\ApiFrontend\RestJsonApi;

use Oro\Bundle\FrontendBundle\Tests\Functional\ApiFrontend\FrontendRestJsonApiTestCase;
use Symfony\Component\HttpFoundation\Response;

class ProductKitItemProductForUnauthenticatedTest extends FrontendRestJsonApiTestCase
{
    public function testOptionsForList(): void
    {
        $response = $this->options(
            $this->getListRouteName(),
            ['entity' => 'productkititemproducts']
        );
        self::assertAllowResponseHeader($response, 'OPTIONS, GET');
    }

    public function testOptionsForItem(): void
    {
        $response = $this->options(
            $this->getItemRouteName(),
            ['entity' => 'productkititemproducts', 'id' => '1']
        );
        self::assertAllowResponseHeader($response, 'OPTIONS, GET');
    }

    public function testTryToGetList(): void
    {
        $response = $this->cget(
            ['entity' => 'productkititemproducts'],
            [],
            [],
            false
        );
        self::assertResponseStatusCodeEquals($response, Response::HTTP_UNAUTHORIZED);
    }

    public function testGet(): void
    {
        $response = $this->get(
            ['entity' => 'productkititemproducts', 'id' => '1'],
            [],
            [],
            false
        );
        self::assertResponseStatusCodeEquals($response, Response::HTTP_UNAUTHORIZED);
    }
    public function testTryToUpdate(): void
    {
        $response = $this->patch(
            ['entity' => 'productkititemproducts', 'id' => '1'],
            [],
            [],
            false
        );
        self::assertResponseStatusCodeEquals($response, Response::HTTP_UNAUTHORIZED);
    }

    public function testTryToCreate(): void
    {
        $response = $this->post(
            ['entity' => 'productkititemproducts'],
            [],
            [],
            false
        );
        self::assertResponseStatusCodeEquals($response, Response::HTTP_UNAUTHORIZED);
    }

    public function testTryToDelete(): void
    {
        $response = $this->delete(
            ['entity' => 'productkititemproducts', 'id' => '1'],
            [],
            [],
            false
        );
        self::assertResponseStatusCodeEquals($response, Response::HTTP_UNAUTHORIZED);
    }

    public function testTryToDeleteList(): void
    {
        $response = $this->cdelete(
            ['entity' => 'productkititemproducts'],
            ['filter' => ['id' => '1']],
            [],
            false
        );
        self::assertResponseStatusCodeEquals($response, Response::HTTP_UNAUTHORIZED);
    }
}
