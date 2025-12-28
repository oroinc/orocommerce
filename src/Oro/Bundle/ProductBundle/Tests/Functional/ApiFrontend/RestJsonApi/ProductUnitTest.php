<?php

namespace Oro\Bundle\ProductBundle\Tests\Functional\ApiFrontend\RestJsonApi;

use Oro\Bundle\CustomerBundle\Tests\Functional\ApiFrontend\DataFixtures\LoadAdminCustomerUserData;
use Oro\Bundle\FrontendBundle\Tests\Functional\ApiFrontend\FrontendRestJsonApiTestCase;
use Oro\Bundle\LocaleBundle\Tests\Functional\DataFixtures\LoadLocalizationData;
use Oro\Bundle\ProductBundle\Tests\Functional\Api\DataFixtures\LoadProductUnits;
use Oro\Bundle\TranslationBundle\Entity\Translation;
use Oro\Bundle\TranslationBundle\Manager\TranslationManager;

/**
 * @dbIsolationPerTest
 */
class ProductUnitTest extends FrontendRestJsonApiTestCase
{
    private ?array $initialEnabledLocalizations;

    #[\Override]
    protected function setUp(): void
    {
        parent::setUp();
        $this->loadFixtures([
            LoadAdminCustomerUserData::class,
            LoadProductUnits::class,
            LoadLocalizationData::class
        ]);
        $this->loadLocalizedProductUnits();

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

    private function loadLocalizedProductUnits(): void
    {
        $productUnitTranslations = [
            'item' => [
                'label.full' => 'Spanish Item',
                'label.full_plural' => 'Spanish Item',
                'label.short' => 'Spanish Item',
                'label.short_plural' => 'Spanish Item'

            ],
            'set' => [
                'label.full' => 'Spanish Set',
                'label.full_plural' => 'Spanish Set',
                'label.short' => 'Spanish Set',
                'label.short_plural' => 'Spanish Set'

            ],
            'peace' => [
                'label.full' => 'Spanish Peace',
                'label.full_plural' => 'Spanish Peace',
                'label.short' => 'Spanish Peace',
                'label.short_plural' => 'Spanish Peace'
            ]
        ];
        /** @var TranslationManager $translationManager */
        $translationManager = self::getContainer()->get('oro_translation.manager.translation');
        foreach ($productUnitTranslations as $productUnitName => $translations) {
            $keyPrefix = 'oro.product_unit.' . $productUnitName;
            foreach ($translations as $key => $translation) {
                $translationManager->saveTranslation(
                    sprintf('%s.%s', $keyPrefix, $key),
                    $translation,
                    'es',
                    'messages',
                    Translation::SCOPE_UI
                );
            }
        }
        $translationManager->flush();
    }

    public function testGetList(): void
    {
        $response = $this->cget(
            ['entity' => 'productunits'],
            ['filter' => ['id' => 'item']]
        );

        $this->assertResponseContains(
            [
                'data' => [
                    [
                        'type' => 'productunits',
                        'id' => 'item',
                        'attributes' => [
                            'defaultPrecision' => 0,
                            'label' => 'item',
                            'shortLabel' => 'item',
                            'pluralLabel' => 'items',
                            'shortPluralLabel' => 'items'
                        ]
                    ]
                ]
            ],
            $response
        );
    }

    public function testGetListFilterBySeveralIds(): void
    {
        $response = $this->cget(
            ['entity' => 'productunits'],
            ['filter' => ['id' => 'piece,set']]
        );

        $this->assertResponseContains(
            [
                'data' => [
                    [
                        'type' => 'productunits',
                        'id' => 'piece',
                        'attributes' => [
                            'defaultPrecision' => 0,
                            'label' => 'piece',
                            'shortLabel' => 'pc',
                            'pluralLabel' => 'pieces',
                            'shortPluralLabel' => 'pcs'
                        ]
                    ],
                    [
                        'type' => 'productunits',
                        'id' => 'set',
                        'attributes' => [
                            'defaultPrecision' => 0,
                            'label' => 'set',
                            'shortLabel' => 'set',
                            'pluralLabel' => 'sets',
                            'shortPluralLabel' => 'sets'
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
            ['entity' => 'productunits', 'id' => 'item']
        );

        $this->assertResponseContains(
            [
                'data' => [
                    'type' => 'productunits',
                    'id' => 'item',
                    'attributes' => [
                        'defaultPrecision' => 0,
                        'label' => 'item',
                        'shortLabel' => 'item',
                        'pluralLabel' => 'items',
                        'shortPluralLabel' => 'items'
                    ]
                ]
            ],
            $response
        );
    }

    public function testGetForAnotherLocalization(): void
    {
        $response = $this->get(
            ['entity' => 'productunits', 'id' => 'item'],
            [],
            ['HTTP_X-Localization-ID' => $this->getReference('es')->getId()]
        );

        $this->assertResponseContains(
            [
                'data' => [
                    'type' => 'productunits',
                    'id' => 'item',
                    'attributes' => [
                        'defaultPrecision' => 0,
                        'label' => 'Spanish Item',
                        'shortLabel' => 'Spanish Item',
                        'pluralLabel' => 'Spanish Item',
                        'shortPluralLabel' => 'Spanish Item'
                    ]
                ]
            ],
            $response
        );
    }

    public function testTryToGetForUnknownLocalization(): void
    {
        $response = $this->get(
            ['entity' => 'productunits', 'id' => 'item'],
            [],
            ['HTTP_X-Localization-ID' => '99999'],
            false
        );

        $this->assertResponseValidationError(
            [
                'title' => 'invalid header value exception',
                'detail' => \sprintf(
                    'The value "99999" is unknown localization ID. Available values: %s. Header: X-Localization-ID.',
                    implode(', ', LoadLocalizationData::getLocalizationIds(self::getContainer()))
                )
            ],
            $response
        );
    }

    public function testTryToUpdate(): void
    {
        $data = [
            'data' => [
                'type' => 'productunits',
                'id' => 'item',
                'attributes' => [
                    'defaultPrecision' => 1
                ]
            ]
        ];

        $response = $this->patch(
            ['entity' => 'productunits', 'id' => 'item'],
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
                'type' => 'productunits',
                'attributes' => [
                    'defaultPrecision' => 1
                ]
            ]
        ];

        $response = $this->post(
            ['entity' => 'productunits'],
            $data,
            [],
            false
        );

        self::assertMethodNotAllowedResponse($response, 'OPTIONS, GET');
    }

    public function testTryToDelete(): void
    {
        $response = $this->delete(
            ['entity' => 'productunits', 'id' => 'item'],
            [],
            [],
            false
        );

        self::assertMethodNotAllowedResponse($response, 'OPTIONS, GET');
    }

    public function testTryToDeleteList(): void
    {
        $response = $this->cdelete(
            ['entity' => 'productunits'],
            ['filter' => ['id' => 'item']],
            [],
            false
        );

        self::assertMethodNotAllowedResponse($response, 'OPTIONS, GET');
    }
}
