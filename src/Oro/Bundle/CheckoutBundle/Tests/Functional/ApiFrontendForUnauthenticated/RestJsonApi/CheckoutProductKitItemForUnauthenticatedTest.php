<?php

namespace Oro\Bundle\CheckoutBundle\Tests\Functional\ApiFrontendForUnauthenticated\RestJsonApi;

use Oro\Bundle\FrontendBundle\Tests\Functional\ApiFrontend\FrontendRestJsonApiTestCase;
use Symfony\Component\HttpFoundation\Response;

/**
 * @SuppressWarnings(PHPMD.TooManyPublicMethods)
 */
class CheckoutProductKitItemForUnauthenticatedTest extends FrontendRestJsonApiTestCase
{
    public function testOptionsForList(): void
    {
        $response = $this->options(
            $this->getListRouteName(),
            ['entity' => 'checkoutproductkititemlineitems']
        );
        self::assertAllowResponseHeader($response, 'OPTIONS, GET, POST, DELETE');
    }

    public function testOptionsForItem(): void
    {
        $response = $this->options(
            $this->getItemRouteName(),
            ['entity' => 'checkoutproductkititemlineitems', 'id' => '1']
        );
        self::assertAllowResponseHeader($response, 'OPTIONS, GET, PATCH, DELETE');
    }

    public function testGetList(): void
    {
        $response = $this->cget(
            ['entity' => 'checkoutproductkititemlineitems'],
            [],
            [],
            false
        );
        self::assertResponseStatusCodeEquals($response, Response::HTTP_UNAUTHORIZED);
    }

    public function testGet(): void
    {
        $response = $this->get(
            ['entity' => 'checkoutproductkititemlineitems', 'id' => '1'],
            [],
            [],
            false
        );
        self::assertResponseStatusCodeEquals($response, Response::HTTP_UNAUTHORIZED);
    }

    public function testCreate(): void
    {
        $response = $this->post(
            ['entity' => 'checkoutproductkititemlineitems'],
            [],
            [],
            false
        );
        self::assertResponseStatusCodeEquals($response, Response::HTTP_UNAUTHORIZED);
    }

    public function testUpdate(): void
    {
        $response = $this->patch(
            ['entity' => 'checkoutproductkititemlineitems', 'id' => '1'],
            [],
            [],
            false
        );
        self::assertResponseStatusCodeEquals($response, Response::HTTP_UNAUTHORIZED);
    }

    public function testDelete(): void
    {
        $response = $this->delete(
            ['entity' => 'checkoutproductkititemlineitems', 'id' => '1'],
            [],
            [],
            false
        );
        self::assertResponseStatusCodeEquals($response, Response::HTTP_UNAUTHORIZED);
    }

    public function testTryToDeleteList(): void
    {
        $response = $this->cdelete(
            ['entity' => 'checkoutproductkititemlineitems'],
            ['filter' => ['id' => '1']],
            [],
            false
        );
        self::assertResponseStatusCodeEquals($response, Response::HTTP_UNAUTHORIZED);
    }

    public function testTryToGetSubresource(): void
    {
        $response = $this->getSubresource(
            ['entity' => 'checkoutproductkititemlineitems', 'id' => '1', 'association' => 'lineItem'],
            [],
            [],
            false
        );
        self::assertResponseStatusCodeEquals($response, Response::HTTP_UNAUTHORIZED);
    }

    public function testTryToGetRelationship(): void
    {
        $response = $this->getRelationship(
            ['entity' => 'checkoutproductkititemlineitems', 'id' => '1', 'association' => 'lineItem'],
            [],
            [],
            false
        );
        self::assertResponseStatusCodeEquals($response, Response::HTTP_UNAUTHORIZED);
    }

    public function testTryToUpdateRelationship(): void
    {
        $response = $this->patchRelationship(
            ['entity' => 'checkoutproductkititemlineitems', 'id' => '1', 'association' => 'lineItem'],
            [],
            [],
            false
        );
        self::assertResponseStatusCodeEquals($response, Response::HTTP_UNAUTHORIZED);
    }

    public function testTryToAddRelationship(): void
    {
        $response = $this->postRelationship(
            ['entity' => 'checkoutproductkititemlineitems', 'id' => '1', 'association' => 'lineItem'],
            [],
            [],
            false
        );
        self::assertResponseStatusCodeEquals($response, Response::HTTP_UNAUTHORIZED);
    }

    public function testTryToDeleteRelationship(): void
    {
        $response = $this->deleteRelationship(
            ['entity' => 'checkoutproductkititemlineitems', 'id' => '1', 'association' => 'lineItem'],
            [],
            [],
            false
        );
        self::assertResponseStatusCodeEquals($response, Response::HTTP_UNAUTHORIZED);
    }
}
