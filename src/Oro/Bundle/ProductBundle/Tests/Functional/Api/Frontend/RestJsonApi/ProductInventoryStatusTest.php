<?php

namespace Oro\Bundle\ProductBundle\Tests\Functional\Api\Frontend\RestJsonApi;

use Oro\Bundle\CustomerBundle\Tests\Functional\Api\Frontend\DataFixtures\LoadAdminCustomerUserData;
use Oro\Bundle\EntityExtendBundle\Entity\EnumValueTranslation;
use Oro\Bundle\FrontendBundle\Tests\Functional\Api\FrontendRestJsonApiTestCase;
use Oro\Bundle\LocaleBundle\Entity\Localization;
use Oro\Bundle\LocaleBundle\Tests\Functional\DataFixtures\LoadLocalizationData;

/**
 * @dbIsolationPerTest
 */
class ProductInventoryStatusTest extends FrontendRestJsonApiTestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->loadFixtures([
            LoadAdminCustomerUserData::class,
            LoadLocalizationData::class
        ]);

        /** @var Localization $esLocalization */
        $esLocalization = $this->getReference('es');
        $esLanguageCode = $esLocalization->getLanguageCode();

        $em = $this->getEntityManager();
        /** @var EnumValueTranslation|null $translation */
        $translation = $em->getRepository(EnumValueTranslation::class)
            ->findOneBy([
                'objectClass' => 'Extend\Entity\EV_Prod_Inventory_Status',
                'field'       => 'name',
                'foreignKey'  => 'in_stock',
                'locale'      => $esLanguageCode
            ]);
        if (null === $translation) {
            $translation = new EnumValueTranslation();
            $translation->setObjectClass('Extend\Entity\EV_Prod_Inventory_Status');
            $translation->setField('name');
            $translation->setForeignKey('in_stock');
            $translation->setLocale($esLanguageCode);
            $translation->setContent('In Stock (Spanish)');
            $em->persist($translation);
            $em->flush();
        }
    }

    public function testGetList()
    {
        $response = $this->cget(
            ['entity' => 'productinventorystatuses']
        );

        $this->assertResponseContains(
            [
                'data' => [
                    [
                        'type'       => 'productinventorystatuses',
                        'id'         => 'in_stock',
                        'attributes' => [
                            'name' => 'In Stock'
                        ]
                    ],
                    [
                        'type'       => 'productinventorystatuses',
                        'id'         => 'out_of_stock',
                        'attributes' => [
                            'name' => 'Out of Stock'
                        ]
                    ],
                    [
                        'type'       => 'productinventorystatuses',
                        'id'         => 'discontinued',
                        'attributes' => [
                            'name' => 'Discontinued'
                        ]
                    ]
                ]
            ],
            $response,
            true
        );
    }

    public function testGetListFilterBySeveralIds()
    {
        $response = $this->cget(
            ['entity' => 'productinventorystatuses'],
            ['filter' => ['id' => 'in_stock,out_of_stock']]
        );

        $this->assertResponseContains(
            [
                'data' => [
                    ['type' => 'productinventorystatuses', 'id' => 'in_stock'],
                    ['type' => 'productinventorystatuses', 'id' => 'out_of_stock']
                ]
            ],
            $response,
            true
        );
    }

    public function testGetListForAnotherLocalization()
    {
        $response = $this->cget(
            ['entity' => 'productinventorystatuses'],
            [],
            ['HTTP_X-Localization-ID' => $this->getReference('es')->getId()]
        );

        $this->assertResponseContains(
            [
                'data' => [
                    [
                        'type'       => 'productinventorystatuses',
                        'id'         => 'in_stock',
                        'attributes' => [
                            'name' => 'In Stock (Spanish)'
                        ]
                    ],
                    [
                        'type'       => 'productinventorystatuses',
                        'id'         => 'out_of_stock',
                        'attributes' => [
                            'name' => 'Out of Stock'
                        ]
                    ],
                    [
                        'type'       => 'productinventorystatuses',
                        'id'         => 'discontinued',
                        'attributes' => [
                            'name' => 'Discontinued'
                        ]
                    ]
                ]
            ],
            $response,
            true
        );
    }

    public function testGet()
    {
        $response = $this->get(
            ['entity' => 'productinventorystatuses', 'id' => 'in_stock']
        );

        $this->assertResponseContains(
            [
                'data' => [
                    'type'       => 'productinventorystatuses',
                    'id'         => 'in_stock',
                    'attributes' => [
                        'name' => 'In Stock'
                    ]
                ]
            ],
            $response
        );
    }

    public function testGetForAnotherLocalization()
    {
        $response = $this->get(
            ['entity' => 'productinventorystatuses', 'id' => 'in_stock'],
            [],
            ['HTTP_X-Localization-ID' => $this->getReference('es')->getId()]
        );

        $this->assertResponseContains(
            [
                'data' => [
                    'type'       => 'productinventorystatuses',
                    'id'         => 'in_stock',
                    'attributes' => [
                        'name' => 'In Stock (Spanish)'
                    ]
                ]
            ],
            $response
        );
    }

    public function testGetForAnotherLocalizationWhenNoLocalizedNameForIt()
    {
        $response = $this->get(
            ['entity' => 'productinventorystatuses', 'id' => 'in_stock'],
            [],
            ['HTTP_X-Localization-ID' => $this->getReference('en_CA')->getId()]
        );

        $this->assertResponseContains(
            [
                'data' => [
                    'type'       => 'productinventorystatuses',
                    'id'         => 'in_stock',
                    'attributes' => [
                        'name' => 'In Stock'
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
                'type'       => 'productinventorystatuses',
                'id'         => 'in_stock',
                'attributes' => [
                    'name' => 'test'
                ]
            ]
        ];

        $response = $this->patch(
            ['entity' => 'productinventorystatuses', 'id' => 'in_stock'],
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
                'type'       => 'productinventorystatuses',
                'attributes' => [
                    'name' => 'test'
                ]
            ]
        ];

        $response = $this->post(
            ['entity' => 'productinventorystatuses'],
            $data,
            [],
            false
        );

        self::assertMethodNotAllowedResponse($response, 'OPTIONS, GET');
    }

    public function testTryToDelete()
    {
        $response = $this->delete(
            ['entity' => 'productinventorystatuses', 'id' => 'in_stock'],
            [],
            [],
            false
        );

        self::assertMethodNotAllowedResponse($response, 'OPTIONS, GET');
    }

    public function testTryToDeleteList()
    {
        $response = $this->cdelete(
            ['entity' => 'productinventorystatuses'],
            ['filter' => ['id' => 'in_stock']],
            [],
            false
        );

        self::assertMethodNotAllowedResponse($response, 'OPTIONS, GET');
    }
}
