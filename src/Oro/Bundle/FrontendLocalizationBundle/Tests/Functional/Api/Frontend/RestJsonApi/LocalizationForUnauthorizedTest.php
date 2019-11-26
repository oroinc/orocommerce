<?php

namespace Oro\Bundle\FrontendLocalizationBundle\Tests\Functional\Api\Frontend\RestJsonApi;

use Oro\Bundle\FrontendBundle\Tests\Functional\Api\FrontendRestJsonApiTestCase;
use Symfony\Component\HttpFoundation\Response;

class LocalizationForUnauthorizedTest extends FrontendRestJsonApiTestCase
{
    public function testTryToGetList()
    {
        $response = $this->cget(
            ['entity' => 'localizations'],
            [],
            [],
            false
        );
        self::assertResponseStatusCodeEquals($response, Response::HTTP_UNAUTHORIZED);
    }

    public function testTryToGet()
    {
        $response = $this->get(
            ['entity' => 'localizations', 'id' => '<toString(@en_US->id)>'],
            [],
            [],
            false
        );
        self::assertResponseStatusCodeEquals($response, Response::HTTP_UNAUTHORIZED);
    }

    public function testTryToUpdate()
    {
        $response = $this->patch(
            ['entity' => 'localizations', 'id' => '<toString(@en_US->id)>'],
            [],
            [],
            false
        );
        self::assertResponseStatusCodeEquals($response, Response::HTTP_UNAUTHORIZED);
    }

    public function testTryToCreate()
    {
        $response = $this->post(
            ['entity' => 'localizations'],
            [],
            [],
            false
        );
        self::assertResponseStatusCodeEquals($response, Response::HTTP_UNAUTHORIZED);
    }

    public function testTryToDelete()
    {
        $response = $this->delete(
            ['entity' => 'localizations', 'id' => '<toString(@en_US->id)>'],
            [],
            [],
            false
        );
        self::assertResponseStatusCodeEquals($response, Response::HTTP_UNAUTHORIZED);
    }

    public function testTryToDeleteList()
    {
        $response = $this->cdelete(
            ['entity' => 'localizations'],
            ['filter' => ['id' => '<toString(@en_US->id)>']],
            [],
            false
        );
        self::assertResponseStatusCodeEquals($response, Response::HTTP_UNAUTHORIZED);
    }
}
