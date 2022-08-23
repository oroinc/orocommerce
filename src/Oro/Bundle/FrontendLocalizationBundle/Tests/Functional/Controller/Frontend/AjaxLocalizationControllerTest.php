<?php

namespace Oro\Bundle\FrontendLocalizationBundle\Tests\Functional\Controller\Frontend;

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

class AjaxLocalizationControllerTest extends WebTestCase
{
    /**
     * {@inheritdoc}
     */
    protected function setUp(): void
    {
        $this->initClient(
            [],
            $this->generateBasicAuthHeader(LoadCustomerUserData::AUTH_USER, LoadCustomerUserData::AUTH_PW)
        );

        $this->loadFixtures([
            LoadSlugsData::class,
            LoadDisabledLocalizationData::class
        ]);
    }

    /**
     * @dataProvider setCurrentLocalizationProvider
     */
    public function testSetCurrentLocalizationAction(
        string $code,
        string $redirectRoute,
        ?string $routeParameters,
        ?array $queryParameters,
        array $expectedResult,
        string $current
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
            ]
        );

        $result = $this->client->getResponse();

        $this->assertJsonResponseStatusCodeEquals($result, 200);

        $data = json_decode($result->getContent(), true);
        $this->assertSame($expectedResult, $data);

        // Do not access localization from UserLocalizationManager service directly,
        // it has local cache and will reflect after client request.
        $website = $this->client->getContainer()->get('oro_website.manager')->getDefaultWebsite();
        $tokenStorage = $this->getContainer()->get('oro_security.token_accessor');
        /** @var CustomerUser $user */
        $user = $tokenStorage->getUser();
        $currentLocalization = $user->getWebsiteSettings($website)?->getLocalization();

        $this->assertNotEmpty($localization);
        $this->assertNotEmpty($currentLocalization);
        $this->assertEquals($current, $currentLocalization->getFormattingCode());
    }

    public function setCurrentLocalizationProvider(): array
    {
        return [
            'set to en and redirect to root' => [
                'code' => 'en',
                'redirectRoute' => 'oro_frontend_root',
                'routeParameters' => null,
                'queryParameters' => null,
                'expectedResult' => ['success' => true, 'redirectTo' => '/'],
                'current' => 'en_US'
            ],
            'Set to en_CA and redirect to localized page' => [
                'code' => 'en_CA',
                'redirectRoute' => 'oro_cms_frontend_page_view',
                'routeParameters' => LoadPageData::PAGE_3,
                'queryParameters' => ['random' => '1234567890'],
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
                'expectedResult' => ['success' => false],
                'current' => 'en_CA'
            ],
        ];
    }

    /**
     * @param string $code
     * @return Localization
     */
    protected function getLocalizationByCode($code)
    {
        $registry = $this->getContainer()->get('doctrine');

        $language = $registry->getManagerForClass(Language::class)
            ->getRepository(Language::class)
            ->findOneBy(['code' => $code]);

        return $registry->getManagerForClass(Localization::class)
            ->getRepository(Localization::class)
            ->findOneBy(['language' => $language]);
    }
}
