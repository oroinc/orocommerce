<?php

namespace Oro\Bundle\FrontendLocalizationBundle\Tests\Functional\Controller\Frontend;

use Oro\Bundle\CMSBundle\Entity\Page;
use Oro\Bundle\CMSBundle\Tests\Functional\DataFixtures\LoadPageData;
use Oro\Bundle\ConfigBundle\Tests\Functional\Traits\ConfigManagerAwareTestTrait;
use Oro\Bundle\CustomerBundle\Entity\CustomerUser;
use Oro\Bundle\FrontendTestFrameworkBundle\Migrations\Data\ORM\LoadCustomerUserData;
use Oro\Bundle\LocaleBundle\Entity\Localization;
use Oro\Bundle\LocaleBundle\Tests\Functional\DataFixtures\LoadDisabledLocalizationData;
use Oro\Bundle\LocaleBundle\Tests\Functional\DataFixtures\LoadLocalizationData;
use Oro\Bundle\RedirectBundle\Tests\Functional\DataFixtures\LoadSlugsData;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;
use Oro\Bundle\TranslationBundle\Entity\Language;
use Symfony\Component\HttpFoundation\Request;

/**
 * @dbIsolationPerTest
 */
class AjaxLocalizationControllerTest extends WebTestCase
{
    use ConfigManagerAwareTestTrait;

    private ?array $initialEnabledLocalizations;

    #[\Override]
    protected function setUp(): void
    {
        $this->initClient(
            [],
            self::generateBasicAuthHeader(LoadCustomerUserData::AUTH_USER, LoadCustomerUserData::AUTH_PW)
        );
        $this->loadFixtures([
            LoadSlugsData::class,
            LoadDisabledLocalizationData::class
        ]);

        $configManager = self::getConfigManager();
        $this->initialEnabledLocalizations = $configManager->get('oro_locale.enabled_localizations');
        $configManager->set(
            'oro_locale.enabled_localizations',
            array_diff(
                LoadLocalizationData::getLocalizationIds(self::getContainer()),
                LoadDisabledLocalizationData::getLocalizationIds(self::getContainer())
            )
        );
        $configManager->flush();
    }

    #[\Override]
    protected function tearDown(): void
    {
        $configManager = self::getConfigManager();
        $configManager->set('oro_locale.enabled_localizations', $this->initialEnabledLocalizations);
        $configManager->flush();
    }

    /**
     * @dataProvider setCurrentLocalizationProvider
     */
    public function testSetCurrentLocalizationAction(
        string $code,
        ?string $redirectRoute,
        ?string $routeParameters,
        ?array $queryParameters,
        array $serverParameters,
        array $expectedResult,
        ?string $current
    ): void {
        $localization = $this->getLocalizationByCode($code);
        if ($routeParameters) {
            /** @var Page $page */
            $page = $this->getReference($routeParameters);
            $routeParameters = json_encode(['id' => $page->getId()], JSON_THROW_ON_ERROR);
        }

        $this->ajaxRequest(
            Request::METHOD_POST,
            $this->getUrl('oro_frontend_localization_frontend_set_current_localization'),
            [
                'localization' => $localization->getId(),
                'redirectRoute' => $redirectRoute,
                'redirectRouteParameters' => $routeParameters,
                'redirectQueryParameters' => json_encode($queryParameters, JSON_THROW_ON_ERROR)
            ],
            [],
            $serverParameters
        );

        $result = $this->client->getResponse();

        self::assertJsonResponseStatusCodeEquals($result, 200);
        self::assertSame($expectedResult, self::jsonToArray($result->getContent()));

        // Do not access localization from UserLocalizationManager service directly,
        // it has local cache and will reflect after client request.
        $website = self::getContainer()->get('oro_website.manager')->getDefaultWebsite();
        $tokenStorage = self::getContainer()->get('oro_security.token_accessor');
        /** @var CustomerUser $user */
        $user = $tokenStorage->getUser();
        $currentLocalization = $user->getWebsiteSettings($website)?->getLocalization();
        if (null === $current) {
            self::assertNull($currentLocalization);
        } else {
            self::assertNotEmpty($currentLocalization);
            self::assertEquals($current, $currentLocalization->getFormattingCode());
        }
    }

    public function setCurrentLocalizationProvider(): array
    {
        return [
            'set to en and redirect to root(without route)' => [
                'code' => 'en',
                'redirectRoute' => null,
                'routeParameters' => null,
                'queryParameters' => null,
                'serverParameters' => [],
                'expectedResult' => ['success' => true, 'redirectTo' => '/'],
                'current' => 'en_US'
            ],
            'set to en and redirect to root' => [
                'code' => 'en',
                'redirectRoute' => 'oro_frontend_root',
                'routeParameters' => null,
                'queryParameters' => null,
                'serverParameters' => [],
                'expectedResult' => ['success' => true, 'redirectTo' => '/'],
                'current' => 'en_US'
            ],
            'Set to en_CA and redirect to localized page' => [
                'code' => 'en_CA',
                'redirectRoute' => 'oro_cms_frontend_page_view',
                'routeParameters' => LoadPageData::PAGE_3,
                'queryParameters' => ['random' => '1234567890'],
                'serverParameters' => [],
                'expectedResult' => [
                    'success' => true,
                    'redirectTo' => 'http://localhost/localized-slug/en_ca/page3?random=1234567890'
                ],
                'current' => 'en_CA'
            ],
            'Set to a disabled localization' => [
                'code' => 'es_MX',
                'redirectRoute' => 'oro_frontend_root',
                'routeParameters' => null,
                'queryParameters' => null,
                'serverParameters' => [],
                'expectedResult' => ['success' => false],
                'current' => null
            ],
            'Set to en and redirect to root when site installed in sub-directory' => [
                'code' => 'en',
                'redirectRoute' => 'oro_frontend_root',
                'routeParameters' => null,
                'queryParameters' => null,
                'serverParameters' => ['WEBSITE_PATH' => '/test'],
                'expectedResult' => ['success' => true, 'redirectTo' => 'http://localhost/test/'],
                'current' => 'en_US'
            ],
            'Set to en_CA and redirect to localized page when site installed in sub-directory' => [
                'code' => 'en_CA',
                'redirectRoute' => 'oro_cms_frontend_page_view',
                'routeParameters' => LoadPageData::PAGE_3,
                'queryParameters' => ['random' => '1234567890'],
                'serverParameters' => ['WEBSITE_PATH' => '/test'],
                'expectedResult' => [
                    'success' => true,
                    'redirectTo' => 'http://localhost/test/localized-slug/en_ca/page3?random=1234567890'
                ],
                'current' => 'en_CA'
            ],
            'Set to en_CA and redirect to localized page having website path similar to beginning of product slug' => [
                'code' => 'en_CA',
                'redirectRoute' => 'oro_cms_frontend_page_view',
                'routeParameters' => LoadPageData::PAGE_3,
                'queryParameters' => ['random' => '1234567890'],
                'serverParameters' => ['WEBSITE_PATH' => '/loc'],
                'expectedResult' => [
                    'success' => true,
                    'redirectTo' => 'http://localhost/loc/localized-slug/en_ca/page3?random=1234567890'
                ],
                'current' => 'en_CA'
            ],
        ];
    }

    private function getLocalizationByCode(string $code): Localization
    {
        $registry = self::getContainer()->get('doctrine');

        $language = $registry->getManagerForClass(Language::class)
            ->getRepository(Language::class)
            ->findOneBy(['code' => $code]);

        return $registry->getManagerForClass(Localization::class)
            ->getRepository(Localization::class)
            ->findOneBy(['language' => $language]);
    }
}
