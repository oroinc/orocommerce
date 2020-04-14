<?php

namespace Oro\Bundle\FrontendLocalizationBundle\Tests\Functional\Api\Frontend\RestJsonApi;

use Oro\Bundle\CustomerBundle\Tests\Functional\Api\Frontend\DataFixtures\LoadAdminCustomerUserData;
use Oro\Bundle\FrontendBundle\Tests\Functional\Api\FrontendRestJsonApiTestCase;
use Oro\Bundle\LocaleBundle\DependencyInjection\Configuration;
use Oro\Bundle\LocaleBundle\Entity\Localization;
use Oro\Bundle\LocaleBundle\Entity\LocalizedFallbackValue;
use Oro\Bundle\LocaleBundle\Tests\Functional\DataFixtures\LoadLocalizationData;
use Symfony\Component\HttpFoundation\Response;

class LocalizationTest extends FrontendRestJsonApiTestCase
{
    /** @var string[] */
    private $originalEnabledLocalizations;

    /** @var string */
    private $originalDefaultLocalization;

    protected function setUp(): void
    {
        parent::setUp();

        $this->loadFixtures([
            LoadAdminCustomerUserData::class,
            LoadLocalizationData::class
        ]);
        $enLocalizationId = $this->getReference('en_US')->getId();
        $esLocalizationId = $this->getReference('es')->getId();
        $em = $this->getEntityManager();
        $enLocalization = $em->find(Localization::class, $enLocalizationId);
        $esLocalization = $em->find(Localization::class, $esLocalizationId);
        $hasEsTitle = false;
        foreach ($enLocalization->getTitles() as $title) {
            if ($title->getLocalization() && $title->getLocalization()->getId() === $esLocalization->getId()) {
                $hasEsTitle = true;
            }
        }
        if (!$hasEsTitle) {
            $esTitleForEnLocalization = new LocalizedFallbackValue();
            $esTitleForEnLocalization->setString('English (United States) in Spanish');
            $esTitleForEnLocalization->setLocalization($esLocalization);
            $enLocalization->addTitle($esTitleForEnLocalization);
            $em->persist($esTitleForEnLocalization);
            $em->flush();
        }

        $configManager = $this->getConfigManager();
        $enabledLocalizationsConfigKey = Configuration::getConfigKeyByName(Configuration::ENABLED_LOCALIZATIONS);
        $defaultLocalizationConfigKey = Configuration::getConfigKeyByName(Configuration::DEFAULT_LOCALIZATION);
        $this->originalEnabledLocalizations = $configManager->get($enabledLocalizationsConfigKey);
        $this->originalDefaultLocalization = $configManager->get($defaultLocalizationConfigKey);
        $configManager->set(
            $enabledLocalizationsConfigKey,
            [$esLocalizationId, $enLocalizationId]
        );
        $configManager->set(
            $defaultLocalizationConfigKey,
            $enLocalizationId
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
                            'default'        => true,
                        ]
                    ],
                    [
                        'type'       => 'localizations',
                        'id'         => '<toString(@es->id)>',
                        'attributes' => [
                            'title' => 'Spanish',
                            'formattingCode' => 'es',
                            'languageCode'   => 'es',
                            'default' => false
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
                        'title' => 'English (United States)',
                        'formattingCode' => 'en_US',
                        'languageCode'   => 'en',
                        'default' => true
                    ]
                ]
            ],
            $response
        );
    }

    public function testGetForAnotherLocalization()
    {
        $response = $this->get(
            ['entity' => 'localizations', 'id' => '<toString(@en_US->id)>'],
            [],
            ['HTTP_X-Localization-ID' => $this->getReference('es')->getId()]
        );

        $this->assertResponseContains(
            [
                'data' => [
                    'type'       => 'localizations',
                    'id'         => '<toString(@en_US->id)>',
                    'attributes' => [
                        'title' => 'English (United States) in Spanish',
                        'formattingCode' => 'en_US',
                        'languageCode'   => 'en',
                        'default' => true
                    ]
                ]
            ],
            $response
        );
    }

    public function testTryToGetDisabled()
    {
        $response = $this->get(
            ['entity' => 'localizations', 'id' => '<toString(@en_CA->id)>'],
            [],
            [],
            false
        );

        self::assertResponseStatusCodeEquals($response, Response::HTTP_NOT_FOUND);
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
