<?php

declare(strict_types=1);

namespace Oro\Bundle\StorefrontEmbeddedBundle\Tests\Functional\Controller\Frontend;

use Oro\Bundle\ConfigBundle\Tests\Functional\Traits\ConfigManagerAwareTestTrait;
use Oro\Bundle\CustomerBundle\Entity\CustomerUser;
use Oro\Bundle\CustomerBundle\Entity\CustomerUserSettings;
use Oro\Bundle\FrontendLocalizationBundle\Manager\UserLocalizationManager;
use Oro\Bundle\FrontendTestFrameworkBundle\Migrations\Data\ORM\LoadCustomerUserData;
use Oro\Bundle\LocaleBundle\Entity\Localization;
use Oro\Bundle\LocaleBundle\Tests\Functional\DataFixtures\LoadLocalizationData;
use Oro\Bundle\PricingBundle\Manager\UserCurrencyManager;
use Oro\Bundle\SecurityBundle\Csrf\CsrfRequestManager;
use Oro\Bundle\StorefrontEmbeddedBundle\Tests\Functional\DataFixtures\LoadStorefrontAuthorizationCodeClient;
use Oro\Bundle\StorefrontEmbeddedBundle\Tests\Functional\EmbeddedSessionTransferTestTrait;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;
use Oro\Bundle\WebsiteBundle\Entity\Website;
use Symfony\Component\BrowserKit\Cookie;
use Symfony\Component\DomCrawler\Crawler;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Session\SessionInterface;

/**
 * @dbIsolationPerTest
 */
class EmbeddedPreferencesControllerTest extends WebTestCase
{
    use ConfigManagerAwareTestTrait;
    use EmbeddedSessionTransferTestTrait;

    #[\Override]
    protected function setUp(): void
    {
        $this->initClient();
        $this->loadFixtures([LoadLocalizationData::class]);

        $this->setConfigValue(
            'oro_locale.enabled_localizations',
            [
                $this->getLocalization(LoadLocalizationData::DEFAULT_LOCALIZATION_CODE)->getId(),
                $this->getLocalization(LoadLocalizationData::EN_CA_LOCALIZATION_CODE)->getId()
            ]
        );
    }

    #[\Override]
    protected function tearDown(): void
    {
        $this->restoreConfigValues();
    }

    public function testStoresLocalizationOfCustomerUserAndRendersNextFragmentWithIt(): void
    {
        $this->openCustomerUserSession(LoadCustomerUserData::AUTH_USER, LoadCustomerUserData::AUTH_PW);
        $localization = $this->getLocalization(LoadLocalizationData::EN_CA_LOCALIZATION_CODE);

        $response = $this->postPreferences(
            ['localization' => $localization->getId()]
        );

        self::assertNoContentThatMustNotBeStored($response);
        $settings = $this->getCustomerUserSettings();
        self::assertNotNull($settings, 'The customer user website settings must be created.');
        self::assertSame($localization->getId(), $settings->getLocalization()?->getId());
        self::assertNull($settings->getCurrency());

        $this->client->request('GET', '/embedded/fragment/embedded_profile');
        $content = self::getJsonResponseContent($this->client->getResponse(), Response::HTTP_OK);
        $moduleConfigs = (new Crawler($content['layout_js_modules_config']))
            ->filter('script[data-role="config"]')
            ->each(static fn (Crawler $script): array => (array) json_decode($script->text(), true));
        self::assertSame(
            $localization->getFormattingCode(),
            array_column($moduleConfigs, 'orolocale/js/locale-settings')[0]['settings']['locale'] ?? null
        );
    }

    public function testStoresCurrencyOfCustomerUser(): void
    {
        $this->openCustomerUserSession(LoadCustomerUserData::AUTH_USER, LoadCustomerUserData::AUTH_PW);
        $currency = $this->getAvailableCurrency();

        $response = $this->postPreferences(['currency' => $currency]);

        self::assertNoContentThatMustNotBeStored($response);
        $settings = $this->getCustomerUserSettings();
        self::assertNotNull($settings, 'The customer user website settings must be created.');
        self::assertSame($currency, $settings->getCurrency());
        self::assertNull($settings->getLocalization());
    }

    public function testStoresLocalizationAndCurrencyOfCustomerUser(): void
    {
        $this->openCustomerUserSession(LoadCustomerUserData::AUTH_USER, LoadCustomerUserData::AUTH_PW);
        $localization = $this->getLocalization(LoadLocalizationData::EN_CA_LOCALIZATION_CODE);
        $currency = $this->getAvailableCurrency();

        $response = $this->postPreferences([
            'localization' => $localization->getId(),
            'currency' => $currency
        ]);

        self::assertNoContentThatMustNotBeStored($response);
        $settings = $this->getCustomerUserSettings();
        self::assertNotNull($settings, 'The customer user website settings must be created.');
        self::assertSame($localization->getId(), $settings->getLocalization()?->getId());
        self::assertSame($currency, $settings->getCurrency());
    }

    public function testRejectsInvalidPreferencesOfCustomerUserAndStoresNothing(): void
    {
        $this->openCustomerUserSession(LoadCustomerUserData::AUTH_USER, LoadCustomerUserData::AUTH_PW);

        $response = $this->postPreferences([
            'localization' => $this->getLocalization(LoadLocalizationData::EN_CA_LOCALIZATION_CODE)->getId(),
            'currency' => 'XYZ'
        ]);

        self::assertBadRequestThatMustNotBeStored($response);
        self::assertNull($this->getCustomerUserSettings());
    }

    public function testRejectsRequestWithoutCsrfHeader(): void
    {
        $this->openCustomerUserSession(LoadCustomerUserData::AUTH_USER, LoadCustomerUserData::AUTH_PW);
        $this->client->getCookieJar()->set(
            new Cookie(CsrfRequestManager::CSRF_TOKEN_ID, 'csrf-cookie-value', null, '/', 'localhost')
        );

        $this->client->request(
            'POST',
            '/embedded/preferences',
            ['currency' => $this->getAvailableCurrency()],
            [],
            ['HTTP_X-Requested-With' => 'XMLHttpRequest']
        );

        self::assertResponseStatusCodeEquals($this->client->getResponse(), Response::HTTP_FORBIDDEN);
        self::assertNull($this->getCustomerUserSettings());
    }

    public function testKeepsStoredLocalizationOfCustomerUserWhenOnlyCurrencyIsGiven(): void
    {
        $this->openCustomerUserSession(LoadCustomerUserData::AUTH_USER, LoadCustomerUserData::AUTH_PW);
        $localization = $this->getLocalization(LoadLocalizationData::EN_CA_LOCALIZATION_CODE);
        $currency = $this->getAvailableCurrency();
        self::assertNoContentThatMustNotBeStored(
            $this->postPreferences(['localization' => $localization->getId()])
        );

        $response = $this->postPreferences(['currency' => $currency]);

        self::assertNoContentThatMustNotBeStored($response);
        $settings = $this->getCustomerUserSettings();
        self::assertNotNull($settings, 'The customer user website settings must be kept.');
        self::assertSame($localization->getId(), $settings->getLocalization()?->getId());
        self::assertSame($currency, $settings->getCurrency());
    }

    public function testStoresCurrencyInVisitorSessionOpenedThroughSessionTransfer(): void
    {
        $this->openVisitorSession();
        $currency = $this->getAvailableCurrency();

        $response = $this->postPreferences(['currency' => $currency]);

        self::assertNoContentThatMustNotBeStored($response);
        $session = $this->getClientSession();
        self::assertSame(
            [$this->getDefaultWebsite()->getId() => $currency],
            $session->get(UserCurrencyManager::SESSION_CURRENCIES)
        );
        self::assertNull($session->get(UserLocalizationManager::SESSION_LOCALIZATIONS));
    }

    public function testStoresLocalizationInVisitorSessionOpenedThroughSessionTransfer(): void
    {
        $this->openVisitorSession();
        $localization = $this->getLocalization(LoadLocalizationData::EN_CA_LOCALIZATION_CODE);

        $response = $this->postPreferences(
            ['localization' => $localization->getId()]
        );

        self::assertNoContentThatMustNotBeStored($response);
        $session = $this->getClientSession();
        self::assertSame(
            [$this->getDefaultWebsite()->getId() => $localization->getId()],
            $session->get(UserLocalizationManager::SESSION_LOCALIZATIONS)
        );
        self::assertNull($session->get(UserCurrencyManager::SESSION_CURRENCIES));
    }

    public function testStoresLocalizationAndCurrencyInVisitorSessionOpenedThroughSessionTransfer(): void
    {
        $this->openVisitorSession();
        $localization = $this->getLocalization(LoadLocalizationData::EN_CA_LOCALIZATION_CODE);
        $currency = $this->getAvailableCurrency();

        $response = $this->postPreferences([
            'localization' => $localization->getId(),
            'currency' => $currency
        ]);

        self::assertNoContentThatMustNotBeStored($response);
        $session = $this->getClientSession();
        $websiteId = $this->getDefaultWebsite()->getId();
        self::assertSame(
            [$websiteId => $localization->getId()],
            $session->get(UserLocalizationManager::SESSION_LOCALIZATIONS)
        );
        self::assertSame([$websiteId => $currency], $session->get(UserCurrencyManager::SESSION_CURRENCIES));
    }

    public function testRejectsRequestOfVisitorWithoutSessionAndStoresNothing(): void
    {
        $response = $this->postPreferences(['currency' => $this->getAvailableCurrency()]);

        self::assertBadRequestThatMustNotBeStored($response);
        self::assertNull(
            $this->client->getCookieJar()->get(
                self::getContainer()->get('session.factory')->createSession()->getName()
            ),
            'No session must be opened for the refused request.'
        );
    }

    public function testRedirectsVisitorToLoginAndStoresNothingWhenGuestAccessIsDisabled(): void
    {
        $this->openVisitorSession();
        $this->setConfigValue('oro_frontend.guest_access_enabled', false);

        $response = $this->postPreferences(
            ['currency' => $this->getAvailableCurrency()]
        );

        self::assertResponseStatusCodeEquals($response, Response::HTTP_FOUND);
        self::assertSame(
            $this->getUrl('oro_customer_customer_user_security_login'),
            $response->headers->get('location')
        );
        self::assertNull($this->getClientSession()->get(UserCurrencyManager::SESSION_CURRENCIES));
    }

    private static function assertNoContentThatMustNotBeStored(Response $response): void
    {
        self::assertEmptyResponseStatusCodeEquals($response, Response::HTTP_NO_CONTENT);
        self::assertTrue($response->headers->hasCacheControlDirective('no-store'));
        self::assertTrue($response->headers->hasCacheControlDirective('private'));
    }

    private static function assertBadRequestThatMustNotBeStored(Response $response): void
    {
        self::assertResponseStatusCodeEquals($response, Response::HTTP_BAD_REQUEST);
        self::assertTrue($response->headers->hasCacheControlDirective('no-store'));
        self::assertTrue($response->headers->hasCacheControlDirective('private'));
    }

    private function postPreferences(array $parameters): Response
    {
        $this->ajaxRequest('POST', '/embedded/preferences', $parameters);

        return $this->client->getResponse();
    }

    private function openVisitorSession(): void
    {
        $this->loadFixtures([LoadStorefrontAuthorizationCodeClient::class]);
        $sessionTransferToken = $this->getSessionTransferToken(
            LoadStorefrontAuthorizationCodeClient::OAUTH_CLIENT_ID,
            $this->getVisitorAccessToken()
        );
        $this->client->getCookieJar()->clear();

        self::assertResponseStatusCodeEquals(
            $this->consumeSessionTransferToken($sessionTransferToken),
            Response::HTTP_SEE_OTHER
        );
    }

    /**
     * Opens the session of the client cookie, as the next request of the host does.
     */
    private function getClientSession(): SessionInterface
    {
        $session = self::getContainer()->get('session.factory')->createSession();
        $sessionCookie = $this->client->getCookieJar()->get($session->getName());
        self::assertNotNull($sessionCookie, 'The visitor session cookie must be kept by the client.');
        $session->setId($sessionCookie->getValue());
        $session->start();

        return $session;
    }

    private function getCustomerUserSettings(): ?CustomerUserSettings
    {
        $entityManager = self::getContainer()->get('doctrine')->getManagerForClass(CustomerUser::class);
        $entityManager->clear();
        $customerUser = $entityManager->getRepository(CustomerUser::class)
            ->findOneBy(['email' => LoadCustomerUserData::AUTH_USER]);

        return $customerUser->getWebsiteSettings($this->getDefaultWebsite());
    }

    private function getAvailableCurrency(): string
    {
        return self::getContainer()->get('oro_pricing.user_currency_manager')->getDefaultCurrency();
    }

    /**
     * Finds the localization by its formatting code, because a fixture load in a test method
     * replaces the fixture references of setUp().
     */
    private function getLocalization(string $formattingCode): Localization
    {
        return self::getContainer()->get('doctrine')->getRepository(Localization::class)
            ->findOneBy(['formattingCode' => $formattingCode]);
    }

    private function getDefaultWebsite(): Website
    {
        return self::getContainer()->get('oro_website.manager')->getDefaultWebsite();
    }
}
