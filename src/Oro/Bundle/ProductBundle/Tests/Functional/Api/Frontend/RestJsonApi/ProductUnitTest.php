<?php

namespace Oro\Bundle\ProductBundle\Tests\Functional\Api\Frontend\RestJsonApi;

use Oro\Bundle\CustomerBundle\Tests\Functional\Api\Frontend\DataFixtures\LoadAdminCustomerUserData;
use Oro\Bundle\FrontendBundle\Tests\Functional\Api\FrontendRestJsonApiTestCase;
use Oro\Bundle\LocaleBundle\Tests\Functional\DataFixtures\LoadLocalizationData;
use Oro\Bundle\ProductBundle\Tests\Functional\Api\DataFixtures\LoadProductUnits;
use Oro\Bundle\TranslationBundle\Entity\Translation;
use Oro\Bundle\TranslationBundle\Manager\TranslationManager;

/**
 * @dbIsolationPerTest
 */
class ProductUnitTest extends FrontendRestJsonApiTestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->loadFixtures([
            LoadAdminCustomerUserData::class,
            LoadProductUnits::class,
            LoadLocalizationData::class
        ]);
        $this->loadLocalizedProductUnits();
    }

    private function loadLocalizedProductUnits()
    {
        $productUnitTranslations = [
            'item'  => [
                'label.full'         => 'Spanish Item',
                'label.full_plural'  => 'Spanish Item',
                'label.short'        => 'Spanish Item',
                'label.short_plural' => 'Spanish Item'

            ],
            'set'   => [
                'label.full'         => 'Spanish Set',
                'label.full_plural'  => 'Spanish Set',
                'label.short'        => 'Spanish Set',
                'label.short_plural' => 'Spanish Set'

            ],
            'peace' => [
                'label.full'         => 'Spanish Peace',
                'label.full_plural'  => 'Spanish Peace',
                'label.short'        => 'Spanish Peace',
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

    public function testGetList()
    {
        $response = $this->cget(
            ['entity' => 'productunits'],
            ['filter' => ['id' => 'item']]
        );

        $this->assertResponseContains(
            [
                'data' => [
                    [
                        'type'       => 'productunits',
                        'id'         => 'item',
                        'attributes' => [
                            'defaultPrecision' => 0,
                            'label'            => 'item',
                            'shortLabel'       => 'item',
                            'pluralLabel'      => 'items',
                            'shortPluralLabel' => 'items'
                        ]
                    ]
                ]
            ],
            $response
        );
    }

    public function testGetListFilterBySeveralIds()
    {
        $response = $this->cget(
            ['entity' => 'productunits'],
            ['filter' => ['id' => 'piece,set']]
        );

        $this->assertResponseContains(
            [
                'data' => [
                    [
                        'type'       => 'productunits',
                        'id'         => 'piece',
                        'attributes' => [
                            'defaultPrecision' => 0,
                            'label'            => 'piece',
                            'shortLabel'       => 'pc',
                            'pluralLabel'      => 'pieces',
                            'shortPluralLabel' => 'pcs'
                        ]
                    ],
                    [
                        'type'       => 'productunits',
                        'id'         => 'set',
                        'attributes' => [
                            'defaultPrecision' => 0,
                            'label'            => 'set',
                            'shortLabel'       => 'set',
                            'pluralLabel'      => 'sets',
                            'shortPluralLabel' => 'sets'
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
            ['entity' => 'productunits', 'id' => 'item']
        );

        $this->assertResponseContains(
            [
                'data' => [
                    'type'       => 'productunits',
                    'id'         => 'item',
                    'attributes' => [
                        'defaultPrecision' => 0,
                        'label'            => 'item',
                        'shortLabel'       => 'item',
                        'pluralLabel'      => 'items',
                        'shortPluralLabel' => 'items'
                    ]
                ]
            ],
            $response
        );
    }

    public function testGetForAnotherLocalization()
    {
        $response = $this->get(
            ['entity' => 'productunits', 'id' => 'item'],
            [],
            ['HTTP_X-Localization-ID' => $this->getReference('es')->getId()]
        );

        $this->assertResponseContains(
            [
                'data' => [
                    'type'       => 'productunits',
                    'id'         => 'item',
                    'attributes' => [
                        'defaultPrecision' => 0,
                        'label'            => 'Spanish Item',
                        'shortLabel'       => 'Spanish Item',
                        'pluralLabel'      => 'Spanish Item',
                        'shortPluralLabel' => 'Spanish Item'
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
                'type'       => 'productunits',
                'id'         => 'item',
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

    public function testTryToCreate()
    {
        $data = [
            'data' => [
                'type'       => 'productunits',
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

    public function testTryToDelete()
    {
        $response = $this->delete(
            ['entity' => 'productunits', 'id' => 'item'],
            [],
            [],
            false
        );

        self::assertMethodNotAllowedResponse($response, 'OPTIONS, GET');
    }

    public function testTryToDeleteList()
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
