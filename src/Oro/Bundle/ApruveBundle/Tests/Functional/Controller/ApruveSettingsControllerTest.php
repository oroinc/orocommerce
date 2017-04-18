<?php

namespace Oro\Bundle\ApruveBundle\Tests\Functional\Controller;

use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;

class ApruveSettingsControllerTest extends WebTestCase
{
    /**
     * {@inheritDoc}
     */
    protected function setUp()
    {
        $this->initClient([], static::generateBasicAuthHeader());
    }

    public function testGenerateTokenAction()
    {
        $this->client->request(
            'POST',
            $this->getUrl('oro_apruve_generate_token')
        );
        $response = $this->client->getResponse();
        static::assertJson($response->getContent());

        $responseArray = static::getJsonResponseContent($response, 200);
        static::assertArrayHasKey('success', $responseArray);
        static::assertArrayHasKey('token', $responseArray);
        static::assertTrue($responseArray['success']);
        static::assertInternalType('string', $responseArray['token']);
    }
}
