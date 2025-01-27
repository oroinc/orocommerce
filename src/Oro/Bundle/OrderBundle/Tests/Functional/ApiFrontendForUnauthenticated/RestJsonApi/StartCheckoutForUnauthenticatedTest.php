<?php

namespace Oro\Bundle\OrderBundle\Tests\Functional\ApiFrontendForUnauthenticated\RestJsonApi;

use Oro\Bundle\FrontendBundle\Tests\Functional\ApiFrontend\FrontendRestJsonApiTestCase;
use Symfony\Component\HttpFoundation\Response;

class StartCheckoutForUnauthenticatedTest extends FrontendRestJsonApiTestCase
{
    public function testTryToStartCheckout(): void
    {
        $response = $this->postSubresource(
            ['entity' => 'orders', 'id' => '1', 'association' => 'checkout'],
            [],
            [],
            false
        );
        self::assertResponseStatusCodeEquals($response, Response::HTTP_UNAUTHORIZED);
    }

    public function testOptionsForStartCheckout(): void
    {
        $response = $this->options(
            $this->getSubresourceRouteName(),
            ['entity' => 'orders', 'id' => '1', 'association' => 'checkout']
        );
        self::assertAllowResponseHeader($response, 'OPTIONS, POST');
    }
}
