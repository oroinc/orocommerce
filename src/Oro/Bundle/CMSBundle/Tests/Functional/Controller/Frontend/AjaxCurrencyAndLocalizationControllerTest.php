<?php

namespace Oro\Bundle\CMSBundle\Tests\Functional\Controller\Frontend;

use Oro\Bundle\CMSBundle\Entity\Page;
use Oro\Bundle\CMSBundle\Tests\Functional\DataFixtures\LoadPageData;
use Oro\Bundle\CustomerBundle\Entity\CustomerUser;
use Oro\Bundle\FrontendTestFrameworkBundle\Migrations\Data\ORM\LoadCustomerUserData;
use Oro\Bundle\LocaleBundle\Entity\Localization;
use Oro\Bundle\LocaleBundle\Tests\Functional\DataFixtures\LoadDisabledLocalizationData;
use Oro\Bundle\RedirectBundle\Tests\Functional\DataFixtures\LoadSlugsData;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;
use Oro\Bundle\TranslationBundle\Entity\Language;
use Symfony\Component\HttpFoundation\Request;

class AjaxCurrencyAndLocalizationControllerTest extends WebTestCase
{
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
    }

    /**
     * @dataProvider setCurrentCurrencyAndLocalizationActionDataProvider
     */
    public function testSetCurrentCurrencyAndLocalizationAction(
        string $code,
        ?string $redirectRoute,
        ?string $routeParameters,
        ?array $queryParameters,
        array $serverParameters,
        string $current,
        ?string $currency,
        array $expectedResult
    ): void {
        $localization = $this->getLocalizationByCode($code);
        if ($routeParameters) {
            /** @var Page $page */
            $page = $this->getReference($routeParameters);
            $routeParameters = json_encode(['id' => $page->getId()], JSON_THROW_ON_ERROR);
        }

        $this->ajaxRequest(
            Request::METHOD_POST,
            $this->getUrl('oro_frontend_set_current_currency_and_localization'),
            [
                'currency' => $currency,
                'localization' => $localization?->getId(),
                'redirectRoute' => $redirectRoute,
                'redirectRouteParameters' => $routeParameters,
                'redirectQueryParameters' => json_encode($queryParameters, JSON_THROW_ON_ERROR)
            ],
            [],
            $serverParameters
        );

        $result = $this->client->getResponse();

        self::assertJsonResponseStatusCodeEquals($result, 200);

        $data = json_decode($result->getContent(), true, 512, JSON_THROW_ON_ERROR);
        $this->assertSame($expectedResult, $data);

        // Do not access localization from UserLocalizationManager service directly,
        // it has local cache and will reflect after client request.
        $website = $this->client->getContainer()->get('oro_website.manager')->getDefaultWebsite();
        $tokenStorage = self::getContainer()->get('oro_security.token_accessor');
        /** @var CustomerUser $user */
        $user = $tokenStorage->getUser();
        $currentLocalization = $user->getWebsiteSettings($website)?->getLocalization();

        $this->assertNotEmpty($currentLocalization);
        $this->assertEquals($current, $currentLocalization->getFormattingCode());
        $this->assertSame($expectedResult, $data);
    }

    /**
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     */
    public function setCurrentCurrencyAndLocalizationActionDataProvider(): array
    {
        return [
            'set to en and redirect to root(without route)' => [
                'code' => 'en',
                'redirectRoute' => null,
                'routeParameters' => null,
                'queryParameters' => null,
                'serverParameters' => [],
                'current' => 'en_US',
                'currency' => 'USD',
                'expectedResult' => [
                    'currencySuccessful' => true,
                    'localizationSuccessful' => true,
                    'redirectTo' => '/'
                ],
            ],
            'set to en and redirect to root' => [
                'code' => 'en',
                'redirectRoute' => 'oro_frontend_root',
                'routeParameters' => null,
                'queryParameters' => null,
                'serverParameters' => [],
                'current' => 'en_US',
                'currency' => 'USD',
                'expectedResult' => [
                    'currencySuccessful' => true,
                    'localizationSuccessful' => true,
                    'redirectTo' => '/'
                ],
            ],
            'Set to en_CA and redirect to localized page' => [
                'code' => 'en_CA',
                'redirectRoute' => 'oro_cms_frontend_page_view',
                'routeParameters' => LoadPageData::PAGE_3,
                'queryParameters' => ['random' => '1234567890'],
                'serverParameters' => [],
                'current' => 'en_CA',
                'currency' => 'USD',
                'expectedResult' => [
                    'currencySuccessful' => true,
                    'localizationSuccessful' => true,
                    'redirectTo' => 'http://localhost/localized-slug/en_ca/page3?random=1234567890'
                ],
            ],
            'Set to a disabled localization' => [
                'code' => 'es_MX',
                'redirectRoute' => 'oro_frontend_root',
                'routeParameters' => null,
                'queryParameters' => null,
                'serverParameters' => [],
                'current' => 'en_CA',
                'currency' => 'USD',
                'expectedResult' => [
                    'currencySuccessful' => true,
                    'localizationSuccessful' => false,
                ],
            ],
            'Set to en and redirect to root when site installed in sub-directory' => [
                'code' => 'en',
                'redirectRoute' => 'oro_frontend_root',
                'routeParameters' => null,
                'queryParameters' => null,
                'serverParameters' => ['WEBSITE_PATH' => '/test'],
                'current' => 'en_US',
                'currency' => 'USD',
                'expectedResult' => [
                    'currencySuccessful' => true,
                    'localizationSuccessful' => true,
                    'redirectTo' => 'http://localhost/test/'
                ],
            ],
            'Set to en_CA and redirect to localized page when site installed in sub-directory' => [
                'code' => 'en_CA',
                'redirectRoute' => 'oro_cms_frontend_page_view',
                'routeParameters' => LoadPageData::PAGE_3,
                'queryParameters' => ['random' => '1234567890'],
                'serverParameters' => ['WEBSITE_PATH' => '/test'],
                'current' => 'en_CA',
                'currency' => 'USD',
                'expectedResult' => [
                    'currencySuccessful' => true,
                    'localizationSuccessful' => true,
                    'redirectTo' => 'http://localhost/test/localized-slug/en_ca/page3?random=1234567890'
                ],
            ],
            'Set to en_CA and redirect to localized page having website path similar to beginning of product slug' => [
                'code' => 'en_CA',
                'redirectRoute' => 'oro_cms_frontend_page_view',
                'routeParameters' => LoadPageData::PAGE_3,
                'queryParameters' => ['random' => '1234567890'],
                'serverParameters' => ['WEBSITE_PATH' => '/loc'],
                'current' => 'en_CA',
                'currency' => 'USD',
                'expectedResult' => [
                    'currencySuccessful' => true,
                    'localizationSuccessful' => true,
                    'redirectTo' => 'http://localhost/loc/localized-slug/en_ca/page3?random=1234567890'
                ],
            ],
            'set without localization' => [
                'code' => 'en1',
                'redirectRoute' => 'oro_frontend_root',
                'routeParameters' => null,
                'queryParameters' => null,
                'serverParameters' => [],
                'current' => 'en_CA',
                'currency' => 'USD',
                'expectedResult' => [
                    'currencySuccessful' => true,
                    'localizationSuccessful' => false
                ],
            ],
            'set wrong currency' => [
                'code' => 'en',
                'redirectRoute' => 'oro_frontend_root',
                'routeParameters' => null,
                'queryParameters' => null,
                'serverParameters' => [],
                'current' => 'en_US',
                'currency' => 'USD2',
                'expectedResult' => [
                    'currencySuccessful' => false,
                    'localizationSuccessful' => true,
                    'redirectTo' => '/'
                ],
            ],
            'set without currency' => [
                'code' => 'en',
                'redirectRoute' => 'oro_frontend_root',
                'routeParameters' => null,
                'queryParameters' => null,
                'serverParameters' => [],
                'current' => 'en_US',
                'currency' => null,
                'expectedResult' => [
                    'currencySuccessful' => false,
                    'localizationSuccessful' => true,
                    'redirectTo' => '/'
                ],
            ],
        ];
    }

    protected function getLocalizationByCode(string $code): ?Localization
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
