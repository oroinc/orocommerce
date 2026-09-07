<?php

namespace Oro\Bundle\StorefrontEmbeddedBundle\Tests\Functional\Controller\Frontend;

use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;
use Symfony\Component\HttpFoundation\Response;

class EmbeddedThemeAssetsControllerTest extends WebTestCase
{
    private const string THEME_ASSETS_PATH = '/embedded/theme-assets';
    private const string BUILD_ROOT = '/build/';

    #[\Override]
    protected function setUp(): void
    {
        $this->initClient();
    }

    public function testNamesTheBuiltThemeFilesToAVisitorWithNoEmbeddedHeader(): void
    {
        $this->client->request('GET', self::THEME_ASSETS_PATH);
        $content = self::getJsonResponseContent($this->client->getResponse(), Response::HTTP_OK);

        self::assertIsArray($content['files']);

        foreach ($content['files'] as $file) {
            self::assertStringStartsWith(self::BUILD_ROOT, $file);
            self::assertStringNotContainsString('..', $file);
        }
    }

    public function testRefusesEveryMethodButGet(): void
    {
        $this->client->request('POST', self::THEME_ASSETS_PATH);

        self::assertResponseStatusCodeSame(Response::HTTP_METHOD_NOT_ALLOWED);
    }
}
