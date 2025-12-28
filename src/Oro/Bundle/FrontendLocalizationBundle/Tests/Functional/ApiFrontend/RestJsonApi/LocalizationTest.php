<?php

namespace Oro\Bundle\FrontendLocalizationBundle\Tests\Functional\ApiFrontend\RestJsonApi;

use Oro\Bundle\CustomerBundle\Tests\Functional\ApiFrontend\DataFixtures\LoadAdminCustomerUserData;
use Oro\Bundle\FrontendBundle\Tests\Functional\ApiFrontend\FrontendRestJsonApiTestCase;
use Oro\Bundle\LocaleBundle\Entity\Localization;
use Oro\Bundle\LocaleBundle\Entity\LocalizedFallbackValue;
use Oro\Bundle\LocaleBundle\Tests\Functional\DataFixtures\LoadLocalizationData;
use Symfony\Component\HttpFoundation\Response;

class LocalizationTest extends FrontendRestJsonApiTestCase
{
    private ?array $initialEnabledLocalizations;
    private string $initialDefaultLocalization;

    #[\Override]
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

        $configManager = self::getConfigManager();
        $this->initialEnabledLocalizations = $configManager->get('oro_locale.enabled_localizations');
        $this->initialDefaultLocalization = $configManager->get('oro_locale.default_localization');
        $configManager->set('oro_locale.enabled_localizations', [$esLocalizationId, $enLocalizationId]);
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
                        'type' => 'localizations',
                        'id' => '<toString(@en_US->id)>',
                        'attributes' => [
                            'title' => 'English (United States)',
                            'formattingCode' => 'en_US',
                            'languageCode' => 'en',
                            'default' => true
                        ]
                    ],
                    [
                        'type' => 'localizations',
                        'id' => '<toString(@es->id)>',
                        'attributes' => [
                            'title' => 'Spanish',
                            'formattingCode' => 'es',
                            'languageCode' => 'es',
                            'default' => false
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
                    'type' => 'localizations',
                    'id' => '<toString(@en_US->id)>',
                    'attributes' => [
                        'title' => 'English (United States)',
                        'formattingCode' => 'en_US',
                        'languageCode' => 'en',
                        'default' => true
                    ]
                ]
            ],
            $response
        );
    }

    public function testGetForAnotherLocalization(): void
    {
        $response = $this->get(
            ['entity' => 'localizations', 'id' => '<toString(@en_US->id)>'],
            [],
            ['HTTP_X-Localization-ID' => $this->getReference('es')->getId()]
        );

        $this->assertResponseContains(
            [
                'data' => [
                    'type' => 'localizations',
                    'id' => '<toString(@en_US->id)>',
                    'attributes' => [
                        'title' => 'English (United States) in Spanish',
                        'formattingCode' => 'en_US',
                        'languageCode' => 'en',
                        'default' => true
                    ]
                ]
            ],
            $response
        );
    }

    public function testTryToGetForInvalidLocalizationIdInHeader(): void
    {
        $response = $this->get(
            ['entity' => 'localizations', 'id' => '<toString(@en_US->id)>'],
            [],
            ['HTTP_X-Localization-ID' => 'invalid'],
            false
        );

        $this->assertResponseValidationError(
            [
                'title' => 'invalid header value exception',
                'detail' => 'Expected integer value. Given "invalid". Header: X-Localization-ID.'
            ],
            $response
        );
    }

    public function testTryToGetForUnknownLocalizationIdInHeader(): void
    {
        $response = $this->get(
            ['entity' => 'localizations', 'id' => '<toString(@en_US->id)>'],
            [],
            ['HTTP_X-Localization-ID' => '99999'],
            false
        );

        $this->assertResponseValidationError(
            [
                'title' => 'invalid header value exception',
                'detail' => \sprintf(
                    'The value "99999" is unknown localization ID. Available values: %s. Header: X-Localization-ID.',
                    implode(', ', [$this->getReference('es')->getId(), $this->getReference('en_US')->getId()])
                )
            ],
            $response
        );
    }

    public function testTryToGetDisabled(): void
    {
        $response = $this->get(
            ['entity' => 'localizations', 'id' => '<toString(@en_CA->id)>'],
            [],
            [],
            false
        );

        self::assertResponseStatusCodeEquals($response, Response::HTTP_NOT_FOUND);
    }

    public function testTryToUpdate(): void
    {
        $data = [
            'data' => [
                'type' => 'localizations',
                'id' => '<toString(@en_US->id)>',
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
                'type' => 'localizations',
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
