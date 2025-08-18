<?php

namespace Oro\Bundle\CMSBundle\Tests\Functional\ApiFrontend\RestJsonApi;

use Oro\Bundle\CustomerBundle\Tests\Functional\ApiFrontend\DataFixtures\LoadAdminCustomerUserData;
use Oro\Bundle\FrontendBundle\Tests\Functional\ApiFrontend\FrontendRestJsonApiTestCase;
use Oro\Bundle\FrontendLocalizationBundle\Manager\UserLocalizationManager;
use Oro\Bundle\LocaleBundle\Entity\Localization;
use Oro\Bundle\LocaleBundle\Tests\Functional\DataFixtures\LoadLocalizationData;

class LandingPageTest extends FrontendRestJsonApiTestCase
{
    private ?array $initialEnabledLocalizations;

    #[\Override]
    protected function setUp(): void
    {
        parent::setUp();
        $this->loadFixtures([
            LoadAdminCustomerUserData::class,
            '@OroCMSBundle/Tests/Functional/ApiFrontend/DataFixtures/landing_page.yml'
        ]);

        $configManager = self::getConfigManager();
        $this->initialEnabledLocalizations = $configManager->get('oro_locale.enabled_localizations');
        $configManager->set(
            'oro_locale.enabled_localizations',
            LoadLocalizationData::getLocalizationIds(self::getContainer())
        );
        $configManager->flush();
    }

    #[\Override]
    protected function tearDown(): void
    {
        $configManager = self::getConfigManager();
        $configManager->set('oro_locale.enabled_localizations', $this->initialEnabledLocalizations);
        $configManager->flush();

        parent::tearDown();
    }

    /**
     * @return Localization
     */
    private function getCurrentLocalization()
    {
        /** @var UserLocalizationManager $localizationManager */
        $localizationManager = self::getContainer()->get('oro_frontend_localization.manager.user_localization');

        return $localizationManager->getCurrentLocalization();
    }

    public function testGetList(): void
    {
        $response = $this->cget(
            ['entity' => 'landingpages'],
            ['filter' => ['id' => ['gte' => '<toString(@page1->id)>']]]
        );

        $this->assertResponseContains('cget_landing_page.yml', $response);
    }

    public function testGetListFilterById(): void
    {
        $response = $this->cget(
            ['entity' => 'landingpages'],
            ['filter' => ['id' => '<toString(@page3->id)>']]
        );

        $this->assertResponseContains('cget_landing_page_filter_by_id.yml', $response);
    }

    public function testGet(): void
    {
        $response = $this->get(
            ['entity' => 'landingpages', 'id' => '<toString(@page1->id)>']
        );

        $this->assertResponseContains('get_landing_page.yml', $response);
    }

    public function testGetForAnotherLocalization(): void
    {
        $this->getReferenceRepository()->setReference('current_localization', $this->getCurrentLocalization());
        $response = $this->get(
            ['entity' => 'landingpages', 'id' => '<toString(@page1->id)>'],
            [],
            ['HTTP_X-Localization-ID' => $this->getReference('es')->getId()]
        );

        $this->assertResponseContains('get_landing_page_es.yml', $response);
    }

    public function testGetForAnotherLocalizationForLandingPageOnlyWithDefaultUrl(): void
    {
        $this->getReferenceRepository()->setReference('current_localization', $this->getCurrentLocalization());
        $response = $this->get(
            ['entity' => 'landingpages', 'id' => '<toString(@page2->id)>'],
            [],
            ['HTTP_X-Localization-ID' => $this->getReference('es')->getId()]
        );

        $this->assertResponseContains(
            [
                'data' => [
                    'attributes' => [
                        'url' => '/page2_slug_default',
                        'urls' => [
                            [
                                'url' => '/page2_slug_default',
                                'localizationId' => '<toString(@current_localization->id)>'
                            ],
                            ['url' => '/page2_slug_default', 'localizationId' => '<toString(@en_CA->id)>']
                        ]
                    ]
                ]
            ],
            $response
        );
    }

    public function testTryToUpdate(): void
    {
        $data = [
            'data' => [
                'type' => 'landingpages',
                'id' => '<toString(@page1->id)>',
                'attributes' => [
                    'title' => 'Updated Landing Page Title'
                ]
            ]
        ];

        $response = $this->patch(
            ['entity' => 'landingpages', 'id' => '<toString(@page1->id)>'],
            $data,
            [],
            false
        );

        self::assertMethodNotAllowedResponse($response, 'OPTIONS, GET');
    }

    public function testTryToCreate(): void
    {
        $data = [
            'data' => [
                'type' => 'landingpages',
                'attributes' => [
                    'title' => 'New Landing Page'
                ]
            ]
        ];

        $response = $this->post(
            ['entity' => 'landingpages'],
            $data,
            [],
            false
        );

        self::assertMethodNotAllowedResponse($response, 'OPTIONS, GET');
    }

    public function testTryToDelete(): void
    {
        $response = $this->delete(
            ['entity' => 'landingpages', 'id' => '<toString(@page1->id)>'],
            [],
            [],
            false
        );

        self::assertMethodNotAllowedResponse($response, 'OPTIONS, GET');
    }

    public function testTryToDeleteList(): void
    {
        $response = $this->cdelete(
            ['entity' => 'landingpages'],
            ['filter' => ['id' => '<toString(@page1->id)>']],
            [],
            false
        );

        self::assertMethodNotAllowedResponse($response, 'OPTIONS, GET');
    }
}
