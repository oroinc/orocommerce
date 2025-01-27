<?php

namespace Oro\Bundle\CheckoutBundle\Tests\Functional\ApiFrontendForUnauthenticated\RestJsonApi;

use Oro\Bundle\FrontendBundle\Tests\Functional\ApiFrontend\FrontendRestJsonApiTestCase;
use Symfony\Component\HttpFoundation\Response;

/**
 * @SuppressWarnings(PHPMD.TooManyPublicMethods)
 */
class CheckoutAddressForUnauthenticatedTest extends FrontendRestJsonApiTestCase
{
    public function testOptionsForList(): void
    {
        $response = $this->options(
            $this->getListRouteName(),
            ['entity' => 'checkoutaddresses']
        );
        self::assertAllowResponseHeader($response, 'OPTIONS, POST');
    }

    public function testOptionsForItem(): void
    {
        $response = $this->options(
            $this->getItemRouteName(),
            ['entity' => 'checkoutaddresses', 'id' => '1']
        );
        self::assertAllowResponseHeader($response, 'OPTIONS, GET, PATCH');
    }

    public function testOptionsForSubresource(): void
    {
        $response = $this->options(
            $this->getSubresourceRouteName(),
            ['entity' => 'checkoutaddresses', 'id' => '1', 'association' => 'country']
        );
        self::assertAllowResponseHeader($response, 'OPTIONS, GET');
    }

    public function testOptionsForRelationship(): void
    {
        $response = $this->options(
            $this->getRelationshipRouteName(),
            ['entity' => 'checkoutaddresses', 'id' => '1', 'association' => 'country']
        );
        self::assertAllowResponseHeader($response, 'OPTIONS, GET');
    }

    public function testGetList(): void
    {
        $response = $this->cget(
            ['entity' => 'checkoutaddresses'],
            [],
            [],
            false
        );
        self::assertResponseStatusCodeEquals($response, Response::HTTP_UNAUTHORIZED);
    }

    public function testGet(): void
    {
        $response = $this->get(
            ['entity' => 'checkoutaddresses', 'id' => '1'],
            [],
            [],
            false
        );
        self::assertResponseStatusCodeEquals($response, Response::HTTP_UNAUTHORIZED);
    }

    public function testCreate(): void
    {
        $response = $this->post(
            ['entity' => 'checkoutaddresses'],
            [],
            [],
            false
        );
        self::assertResponseStatusCodeEquals($response, Response::HTTP_UNAUTHORIZED);
    }

    public function testUpdate(): void
    {
        $response = $this->patch(
            ['entity' => 'checkoutaddresses', 'id' => '1'],
            [],
            [],
            false
        );
        self::assertResponseStatusCodeEquals($response, Response::HTTP_UNAUTHORIZED);
    }

    public function testDelete(): void
    {
        $response = $this->delete(
            ['entity' => 'checkoutaddresses', 'id' => '1'],
            [],
            [],
            false
        );
        self::assertResponseStatusCodeEquals($response, Response::HTTP_UNAUTHORIZED);
    }

    public function testTryToDeleteList(): void
    {
        $response = $this->cdelete(
            ['entity' => 'checkoutaddresses'],
            ['filter' => ['id' => '1']],
            [],
            false
        );
        self::assertResponseStatusCodeEquals($response, Response::HTTP_UNAUTHORIZED);
    }

    public function testTryToGetSubresource(): void
    {
        $response = $this->getSubresource(
            ['entity' => 'checkoutaddresses', 'id' => '1', 'association' => 'country'],
            [],
            [],
            false
        );
        self::assertResponseStatusCodeEquals($response, Response::HTTP_UNAUTHORIZED);
    }

    public function testTryToGetRelationship(): void
    {
        $response = $this->getRelationship(
            ['entity' => 'checkoutaddresses', 'id' => '1', 'association' => 'country'],
            [],
            [],
            false
        );
        self::assertResponseStatusCodeEquals($response, Response::HTTP_UNAUTHORIZED);
    }

    public function testTryToUpdateRelationship(): void
    {
        $response = $this->patchRelationship(
            ['entity' => 'checkoutaddresses', 'id' => '1', 'association' => 'country'],
            [],
            [],
            false
        );
        self::assertResponseStatusCodeEquals($response, Response::HTTP_UNAUTHORIZED);
    }

    public function testTryToAddRelationship(): void
    {
        $response = $this->postRelationship(
            ['entity' => 'checkoutaddresses', 'id' => '1', 'association' => 'country'],
            [],
            [],
            false
        );
        self::assertResponseStatusCodeEquals($response, Response::HTTP_UNAUTHORIZED);
    }

    public function testTryToDeleteRelationship(): void
    {
        $response = $this->deleteRelationship(
            ['entity' => 'checkoutaddresses', 'id' => '1', 'association' => 'country'],
            [],
            [],
            false
        );
        self::assertResponseStatusCodeEquals($response, Response::HTTP_UNAUTHORIZED);
    }
}
