<?php

namespace Oro\Bundle\FrontendLocalizationBundle\Tests\Functional\Api\Frontend\RestJsonApi;

use Oro\Bundle\FrontendBundle\Tests\Functional\Api\FrontendRestJsonApiTestCase;
use Oro\Bundle\LocaleBundle\DependencyInjection\Configuration;
use Oro\Bundle\LocaleBundle\Tests\Functional\DataFixtures\LoadLocalizationData;

class LocalizationForVisitorTest extends FrontendRestJsonApiTestCase
{
    /** @var string[] */
    private $originalEnabledLocalizations;

    /** @var string */
    private $originalDefaultLocalization;

    protected function setUp(): void
    {
        parent::setUp();
        $this->enableVisitor();
        $this->loadFixtures([
            LoadLocalizationData::class
        ]);

        $configManager = $this->getConfigManager();
        $enabledLocalizationsConfigKey = Configuration::getConfigKeyByName(Configuration::ENABLED_LOCALIZATIONS);
        $defaultLocalizationConfigKey = Configuration::getConfigKeyByName(Configuration::DEFAULT_LOCALIZATION);
        $this->originalEnabledLocalizations = $configManager->get($enabledLocalizationsConfigKey);
        $this->originalDefaultLocalization = $configManager->get($defaultLocalizationConfigKey);
        $configManager->set(
            $enabledLocalizationsConfigKey,
            [$this->getReference('en_US')->getId(), $this->getReference('es')->getId()]
        );
        $configManager->set(
            $defaultLocalizationConfigKey,
            $this->getReference('en_US')->getId()
        );
        $configManager->flush();
    }

    protected function tearDown(): void
    {
        $configManager = $this->getConfigManager();
        $configManager->set(
            Configuration::getConfigKeyByName(Configuration::ENABLED_LOCALIZATIONS),
            $this->originalEnabledLocalizations
        );
        $configManager->set(
            Configuration::getConfigKeyByName(Configuration::DEFAULT_LOCALIZATION),
            $this->originalDefaultLocalization
        );
        $configManager->flush();

        parent::tearDown();
    }

    public function testGetList()
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

    public function testGet()
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

    public function testTryToUpdate()
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

    public function testTryToCreate()
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

    public function testTryToDelete()
    {
        $response = $this->delete(
            ['entity' => 'localizations', 'id' => '<toString(@en_US->id)>'],
            [],
            [],
            false
        );

        self::assertMethodNotAllowedResponse($response, 'OPTIONS, GET');
    }

    public function testTryToDeleteList()
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
