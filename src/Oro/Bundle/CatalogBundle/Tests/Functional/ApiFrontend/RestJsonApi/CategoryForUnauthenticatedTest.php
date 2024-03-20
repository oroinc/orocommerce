<?php

namespace Oro\Bundle\CatalogBundle\Tests\Functional\ApiFrontend\RestJsonApi;

use Oro\Bundle\FrontendBundle\Tests\Functional\ApiFrontend\FrontendRestJsonApiTestCase;
use Symfony\Component\HttpFoundation\Response;

/**
 * @SuppressWarnings(PHPMD.TooManyPublicMethods)
 */
class CategoryForUnauthenticatedTest extends FrontendRestJsonApiTestCase
{
    public function testOptionsForList(): void
    {
        $response = $this->options(
            $this->getListRouteName(),
            ['entity' => 'mastercatalogcategories']
        );
        self::assertAllowResponseHeader($response, 'OPTIONS, GET');
    }

    public function testOptionsForItem(): void
    {
        $response = $this->options(
            $this->getItemRouteName(),
            ['entity' => 'mastercatalogcategories', 'id' => '1']
        );
        self::assertAllowResponseHeader($response, 'OPTIONS, GET');
    }

    public function testTryToGetList(): void
    {
        $response = $this->cget(
            ['entity' => 'mastercatalogcategories'],
            [],
            [],
            false
        );
        self::assertResponseStatusCodeEquals($response, Response::HTTP_UNAUTHORIZED);
    }

    public function testTryToGet(): void
    {
        $response = $this->get(
            ['entity' => 'mastercatalogcategories', 'id' => '1'],
            [],
            [],
            false
        );
        self::assertResponseStatusCodeEquals($response, Response::HTTP_UNAUTHORIZED);
    }

    public function testTryToUpdate(): void
    {
        $response = $this->patch(
            ['entity' => 'mastercatalogcategories', 'id' => '1'],
            [],
            [],
            false
        );
        self::assertResponseStatusCodeEquals($response, Response::HTTP_UNAUTHORIZED);
    }

    public function testTryToCreate(): void
    {
        $response = $this->post(
            ['entity' => 'mastercatalogcategories'],
            [],
            [],
            false
        );
        self::assertResponseStatusCodeEquals($response, Response::HTTP_UNAUTHORIZED);
    }

    public function testTryToDelete(): void
    {
        $response = $this->delete(
            ['entity' => 'mastercatalogcategories', 'id' => '1'],
            [],
            [],
            false
        );
        self::assertResponseStatusCodeEquals($response, Response::HTTP_UNAUTHORIZED);
    }

    public function testTryToDeleteList(): void
    {
        $response = $this->cdelete(
            ['entity' => 'mastercatalogcategories'],
            ['filter' => ['id' => '1']],
            [],
            false
        );
        self::assertResponseStatusCodeEquals($response, Response::HTTP_UNAUTHORIZED);
    }

    public function testTryToGetSubresourceForProducts()
    {
        $response = $this->getSubresource(
            ['entity' => 'mastercatalogcategories', 'id' => '1', 'association' => 'products'],
            [],
            [],
            false
        );
        self::assertResponseStatusCodeEquals($response, Response::HTTP_UNAUTHORIZED);
    }

    public function testTryToGetRelationshipForProducts()
    {
        $response = $this->getRelationship(
            ['entity' => 'mastercatalogcategories', 'id' => '1', 'association' => 'products'],
            [],
            [],
            false
        );
        self::assertResponseStatusCodeEquals($response, Response::HTTP_UNAUTHORIZED);
    }

    public function testTryToUpdateRelationshipForProducts()
    {
        $response = $this->patchRelationship(
            ['entity' => 'mastercatalogcategories', 'id' => '1', 'association' => 'products'],
            [],
            [],
            false
        );
        self::assertResponseStatusCodeEquals($response, Response::HTTP_UNAUTHORIZED);
    }

    public function testTryToAddRelationshipForProducts()
    {
        $response = $this->postRelationship(
            ['entity' => 'mastercatalogcategories', 'id' => '1', 'association' => 'products'],
            [],
            [],
            false
        );
        self::assertResponseStatusCodeEquals($response, Response::HTTP_UNAUTHORIZED);
    }

    public function testTryToDeleteRelationshipForProducts()
    {
        $response = $this->deleteRelationship(
            ['entity' => 'mastercatalogcategories', 'id' => '1', 'association' => 'products'],
            [],
            [],
            false
        );
        self::assertResponseStatusCodeEquals($response, Response::HTTP_UNAUTHORIZED);
    }
}
