<?php

namespace Oro\Bundle\FrontendLocalizationBundle\Tests\Functional\ApiFrontend\RestJsonApi;

use Oro\Bundle\FrontendBundle\Tests\Functional\ApiFrontend\FrontendRestJsonApiTestCase;
use Oro\Bundle\LocaleBundle\Tests\Functional\DataFixtures\LoadLocalizationData;

class LocalizationForVisitorTest extends FrontendRestJsonApiTestCase
{
    private ?array $initialEnabledLocalizations;
    private ?string $initialDefaultLocalization;

    #[\Override]
    protected function setUp(): void
    {
        parent::setUp();
        $this->initializeVisitor();
        $this->loadFixtures([LoadLocalizationData::class]);

        $enLocalizationId = $this->getReference('en_US')->getId();
        $esLocalizationId = $this->getReference('es')->getId();

        $configManager = self::getConfigManager();
        $this->initialEnabledLocalizations = $configManager->get('oro_locale.enabled_localizations');
        $this->initialDefaultLocalization = $configManager->get('oro_locale.default_localization');
        $configManager->set('oro_locale.enabled_localizations', [$enLocalizationId, $esLocalizationId]);
        $configManager->set('oro_locale.default_localization', $enLocalizationId);
        $configManager->flush();
    }

    #[\Override]
    protected function tearDown(): void
    {
        $configManager = self::getConfigManager();
        $configManager->set('oro_locale.enabled_localizations', $this->initialEnabledLocalizations);
        $configManager->set('oro_locale.default_localization', $this->initialDefaultLocalization);
        $configManager->flush();

        parent::tearDown();
    }

    public function testGetList(): void
    {
        $response = $this->cget(
            ['entity' => 'localizations']
        );

        $this->assertResponseContains(
            [
                'data' => [
                    [
                        'type'       => 'localizations',
                        'id'         => '<toString(@en_US->id)>',
                        'attributes' => [
                            'title'          => 'English (United States)',
                            'formattingCode' => 'en_US',
                            'languageCode'   => 'en',
                            'default'        => true
                        ]
                    ],
                    [
                        'type'       => 'localizations',
                        'id'         => '<toString(@es->id)>',
                        'attributes' => [
                            'title'          => 'Spanish',
                            'formattingCode' => 'es',
                            'languageCode'   => 'es',
                            'default'        => false,
                        ]
                    ]
                ]
            ],
            $response
        );
    }

    public function testGet(): void
    {
        $response = $this->get(
            ['entity' => 'localizations', 'id' => '<toString(@en_US->id)>']
        );

        $this->assertResponseContains(
            [
                'data' => [
                    'type'       => 'localizations',
                    'id'         => '<toString(@en_US->id)>',
                    'attributes' => [
                        'title'          => 'English (United States)',
                        'formattingCode' => 'en_US',
                        'languageCode'   => 'en',
                        'default'        => true
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
                'type'       => 'localizations',
                'id'         => '<toString(@en_US->id)>',
                'attributes' => [
                    'title' => 'test'
                ]
            ]
        ];

        $response = $this->patch(
            ['entity' => 'localizations', 'id' => '<toString(@en_US->id)>'],
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
                'type'       => 'localizations',
                'attributes' => [
                    'title' => 'test'
                ]
            ]
        ];

        $response = $this->post(
            ['entity' => 'localizations'],
            $data,
            [],
            false
        );

        self::assertMethodNotAllowedResponse($response, 'OPTIONS, GET');
    }

    public function testTryToDelete(): void
    {
        $response = $this->delete(
            ['entity' => 'localizations', 'id' => '<toString(@en_US->id)>'],
            [],
            [],
            false
        );

        self::assertMethodNotAllowedResponse($response, 'OPTIONS, GET');
    }

    public function testTryToDeleteList(): void
    {
        $response = $this->cdelete(
            ['entity' => 'localizations'],
            ['filter' => ['id' => '<toString(@en_US->id)>']],
            [],
            false
        );

        self::assertMethodNotAllowedResponse($response, 'OPTIONS, GET');
    }
}
