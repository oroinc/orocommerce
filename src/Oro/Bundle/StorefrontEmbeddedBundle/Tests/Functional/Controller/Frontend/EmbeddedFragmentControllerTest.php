<?php

declare(strict_types=1);

namespace Oro\Bundle\StorefrontEmbeddedBundle\Tests\Functional\Controller\Frontend;

use Oro\Bundle\CustomerBundle\Entity\CustomerUser;
use Oro\Bundle\FrontendTestFrameworkBundle\Migrations\Data\ORM\LoadCustomerUserData;
use Oro\Bundle\NavigationBundle\Event\ResponseHashnavListener;
use Oro\Bundle\StorefrontEmbeddedBundle\Request\EmbeddedRequestChecker;
use Oro\Bundle\StorefrontEmbeddedBundle\Tests\Functional\EmbeddedSessionTransferTestTrait;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;
use Symfony\Component\HttpFoundation\Response;

/**
 * @dbIsolationPerTest
 */
class EmbeddedFragmentControllerTest extends WebTestCase
{
    use EmbeddedSessionTransferTestTrait;

    #[\Override]
    protected function setUp(): void
    {
        $this->initClient();
        $this->openCustomerUserSession(LoadCustomerUserData::AUTH_USER, LoadCustomerUserData::AUTH_PW);
    }

    public function testReturnsFragmentBlocksWithMetadataForGetRequest(): void
    {
        $this->client->request('GET', '/embedded/fragment/embedded_profile');

        $response = $this->client->getResponse();
        $content = self::getJsonResponseContent($response, Response::HTTP_OK);
        self::assertArrayHasKey('layout_js_modules_config', $content);
        self::assertArrayHasKey('runtimeUrl', $content);
        self::assertArrayHasKey('runtimeIntegrity', $content);
        self::assertIsString($content['embedded_profile']);
        self::assertNotEmpty($content['embedded_profile']);
        self::assertIsString($content['title']);
        self::assertNotEmpty($content['title']);
        self::assertSame(strip_tags($content['title']), $content['title']);
        self::assertSame($this->getUrl('oro_customer_frontend_customer_user_profile'), $content['subtreeUrl']);
        self::assertIsArray($content['flashMessages']);
        self::assertIsArray($content['styles']);
        self::assertIsArray($content['printStyles']);
        self::assertContains(EmbeddedRequestChecker::EMBEDDED_HEADER, $response->getVary());
    }

    public function testReturnsNotFoundForUnknownFragment(): void
    {
        $this->client->request('GET', '/embedded/fragment/unknown_fragment');

        self::assertResponseStatusCodeEquals($this->client->getResponse(), Response::HTTP_NOT_FOUND);
    }

    public function testReturnsMethodNotAllowedForUnsupportedMethod(): void
    {
        $this->client->request('DELETE', '/embedded/fragment/embedded_profile');

        $response = $this->client->getResponse();
        self::assertResponseStatusCodeEquals($response, Response::HTTP_METHOD_NOT_ALLOWED);
        self::assertSame('HEAD, GET, POST, PUT', $response->headers->get('Allow'));
    }

    public function testReturnsBadRequestWhenRequiredRouteParameterIsMissing(): void
    {
        $this->client->request('GET', '/embedded/fragment/embedded_customer_user_view');

        self::assertResponseStatusCodeEquals($this->client->getResponse(), Response::HTTP_BAD_REQUEST);
    }

    public function testPassesQueryParametersToTargetRoute(): void
    {
        $customerUserId = self::getContainer()->get('doctrine')->getRepository(CustomerUser::class)
            ->findOneBy(['email' => LoadCustomerUserData::AUTH_USER])
            ->getId();

        $this->client->request('GET', '/embedded/fragment/embedded_customer_user_view?id=' . $customerUserId);

        $content = self::getJsonResponseContent($this->client->getResponse(), Response::HTTP_OK);
        self::assertArrayHasKey('embedded_customer_user_view', $content);
        self::assertSame(
            $this->getUrl('oro_customer_frontend_customer_user_view', ['id' => $customerUserId]),
            $content['subtreeUrl']
        );
    }

    public function testDropsQueryParametersThatStartWithUnderscore(): void
    {
        $customerUserId = self::getContainer()->get('doctrine')->getRepository(CustomerUser::class)
            ->findOneBy(['email' => LoadCustomerUserData::AUTH_USER])
            ->getId();

        $this->client->request('GET', '/embedded/fragment/embedded_customer_user_view?_id=' . $customerUserId);

        self::assertResponseStatusCodeEquals($this->client->getResponse(), Response::HTTP_BAD_REQUEST);
    }

    public function testReturnsFragmentWithoutJsAndStylesMetadataForReloadRequest(): void
    {
        $this->client->request(
            'GET',
            '/embedded/fragment/embedded_profile',
            [],
            [],
            ['HTTP_' . ResponseHashnavListener::HASH_NAVIGATION_HEADER => '1']
        );

        $content = self::getJsonResponseContent($this->client->getResponse(), Response::HTTP_OK);
        self::assertArrayHasKey('embedded_profile', $content);
        self::assertArrayHasKey('subtreeUrl', $content);
        self::assertArrayHasKey('title', $content);
        self::assertArrayNotHasKey('layout_js_modules_config', $content);
        self::assertArrayNotHasKey('runtimeUrl', $content);
        self::assertArrayNotHasKey('runtimeIntegrity', $content);
        self::assertArrayNotHasKey('styles', $content);
        self::assertArrayNotHasKey('printStyles', $content);
    }
}
