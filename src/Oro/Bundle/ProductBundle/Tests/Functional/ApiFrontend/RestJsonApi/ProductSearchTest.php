<?php

namespace Oro\Bundle\ProductBundle\Tests\Functional\ApiFrontend\RestJsonApi;

use Doctrine\DBAL\Platforms\MySqlPlatform;
use Oro\Bundle\CustomerBundle\Tests\Functional\ApiFrontend\DataFixtures\LoadAdminCustomerUserData;
use Oro\Bundle\FrontendBundle\Tests\Functional\ApiFrontend\FrontendRestJsonApiTestCase;
use Oro\Bundle\LocaleBundle\Tests\Functional\DataFixtures\LoadLocalizationData;
use Oro\Bundle\SearchBundle\Engine\Orm;
use Oro\Bundle\WebsiteSearchBundle\Tests\Functional\WebsiteSearchExtensionTrait;
use Symfony\Component\HttpFoundation\Response;

/**
 * @SuppressWarnings(PHPMD.TooManyMethods)
 * @SuppressWarnings(PHPMD.TooManyPublicMethods)
 * @SuppressWarnings(PHPMD.ExcessivePublicCount)
 * @SuppressWarnings(PHPMD.ExcessiveClassLength)
 * @SuppressWarnings(PHPMD.ExcessiveClassComplexity)
 */
class ProductSearchTest extends FrontendRestJsonApiTestCase
{
    use WebsiteSearchExtensionTrait;

    private ?array $initialEnabledLocalizations;

    #[\Override]
    protected function setUp(): void
    {
        parent::setUp();
        $this->loadFixtures([
            LoadAdminCustomerUserData::class,
            '@OroProductBundle/Tests/Functional/ApiFrontend/DataFixtures/product.yml',
            '@OroProductBundle/Tests/Functional/ApiFrontend/DataFixtures/product_prices.yml'
        ]);

        $configManager = self::getConfigManager();
        $this->initialEnabledLocalizations = $configManager->get('oro_locale.enabled_localizations');
        $configManager->set(
            'oro_locale.enabled_localizations',
            LoadLocalizationData::getLocalizationIds(self::getContainer())
        );
        $configManager->set('oro_order.enable_purchase_history', true);
        $configManager->flush();

        self::reindexProductData();
    }

    #[\Override]
    protected function tearDown(): void
    {
        $configManager = self::getConfigManager();
        $configManager->set('oro_locale.enabled_localizations', $this->initialEnabledLocalizations);
        $configManager->set('oro_order.enable_purchase_history', false);
        $configManager->flush();

        parent::tearDown();
    }

    private function isMySqlOrmSearchEngine(): bool
    {
        if (Orm::ENGINE_NAME !== self::getContainer()->get('oro_website_search.engine.parameters')->getEngineName()) {
            return false;
        }

        return $this->getEntityManager()->getConnection()->getDatabasePlatform() instanceof MySqlPlatform;
    }

    public function testNoSearchQueryFilter(): void
    {
        $response = $this->cget(
            ['entity' => 'productsearch']
        );

        $this->assertResponseContains('cget_product_search.yml', $response, true);
    }

    public function testIncludeInventoryStatuses(): void
    {
        $response = $this->cget(
            ['entity' => 'productsearch'],
            ['include' => 'inventoryStatus', 'page[size]' => 1, 'sort' => 'id']
        );

        $this->assertResponseContains(
            [
                'data' => [
                    [
                        'type' => 'productsearch',
                        'id' => '<toString(@product1->id)>',
                        'relationships' => [
                            'inventoryStatus' => [
                                'data' => [
                                    'type' => 'productinventorystatuses',
                                    'id' => '<toString(@product1->inventoryStatus->internalId)>'
                                ]
                            ]
                        ]
                    ]
                ],
                'included' => [
                    [
                        'type' => 'productinventorystatuses',
                        'id' => '<toString(@product1->inventoryStatus->internalId)>',
                        'attributes' => [
                            'name' => '<toString(@product1->inventoryStatus->name)>'
                        ]
                    ]
                ]
            ],
            $response
        );
    }

    public function testIncludeProductFamily(): void
    {
        $response = $this->cget(
            ['entity' => 'productsearch'],
            ['include' => 'productFamily', 'page[size]' => 1, 'sort' => 'id']
        );

        $this->assertResponseContains(
            [
                'data' => [
                    [
                        'type' => 'productsearch',
                        'id' => '<toString(@product1->id)>',
                        'relationships' => [
                            'productFamily' => [
                                'data' => [
                                    'type' => 'productfamilies',
                                    'id' => '<toString(@default_product_family->id)>'
                                ]
                            ]
                        ]
                    ]
                ],
                'included' => [
                    [
                        'type' => 'productfamilies',
                        'id' => '<toString(@default_product_family->id)>'
                    ]
                ]
            ],
            $response
        );
    }

    public function testIncludeProduct(): void
    {
        $response = $this->cget(
            ['entity' => 'productsearch'],
            ['include' => 'product', 'page[size]' => 1, 'sort' => 'id']
        );

        $this->assertResponseContains(
            [
                'data' => [
                    [
                        'type' => 'productsearch',
                        'id' => '<toString(@product1->id)>',
                        'relationships' => [
                            'product' => [
                                'data' => [
                                    'type' => 'products',
                                    'id' => '<toString(@product1->id)>'
                                ]
                            ]
                        ]
                    ]
                ],
                'included' => [
                    [
                        'type' => 'products',
                        'id' => '<toString(@product1->id)>'
                    ]
                ]
            ],
            $response
        );
    }

    public function testIncludeProductWithOnlyProductAttributes(): void
    {
        $response = $this->cget(
            ['entity' => 'productsearch'],
            ['page[size]' => 1, 'sort' => 'id', 'include' => 'product', 'fields[products]' => 'productAttributes']
        );

        $this->assertResponseContains(
            [
                'data' => [
                    [
                        'type' => 'productsearch',
                        'id' => '<toString(@product1->id)>'
                    ]
                ],
                'included' => [
                    [
                        'type' => 'products',
                        'id' => '<toString(@product1->id)>',
                        'attributes' => [
                            'productAttributes' => [
                                'testAttrString' => 'string attribute',
                                'testAttrBoolean' => true,
                                'testAttrFloat' => 1.23,
                                'testAttrMoney' => '1.2300',
                                'testAttrDateTime' => '2010-06-15T20:20:30Z',
                                'testAttrMultiEnum' => [
                                    [
                                        'id' => '@productAttrMultiEnum_option1->internalId',
                                        'targetValue' => '@productAttrMultiEnum_option1->name'
                                    ],
                                    [
                                        'id' => '@productAttrMultiEnum_option2->internalId',
                                        'targetValue' => '@productAttrMultiEnum_option2->name'
                                    ]
                                ],
                                'testAttrManyToOne' => [
                                    'id' => '<toString(@customer1->id)>',
                                    'targetValue' => 'Company 1'
                                ],
                                'testToOneId' => ['id' => 'US', 'targetValue' => 'US'],
                                'testAttrManyToMany' => [
                                    ['id' => '<toString(@customer_user1->id)>', 'targetValue' => 'John Edgar Doo'],
                                    ['id' => '<toString(@customer_user2->id)>', 'targetValue' => 'Amanda Cole']
                                ],
                                'testToManyId' => [
                                    [
                                        'id' => '<toString(@country.mexico->iso2Code)>',
                                        'targetValue' => '<toString(@country.mexico->iso2Code)>'
                                    ],
                                    [
                                        'id' => '<toString(@country.germany->iso2Code)>',
                                        'targetValue' => '<toString(@country.germany->iso2Code)>'
                                    ]
                                ],
                                'wysiwyg' => '<style type="text/css">.test {color: red}</style>'
                                    . 'Product 1 WYSIWYG Text. Twig Expr: "test".',
                                'wysiwygAttr' => '<style type="text/css">.test {color: red}</style>'
                                    . 'Product 1 WYSIWYG Attr Text. Twig Expr: "test".'
                            ]
                        ]
                    ]
                ]
            ],
            $response
        );
        $responseData = self::jsonToArray($response->getContent());
        self::assertCount(12, $responseData['included'][0]['attributes']['productAttributes']);
    }

    public function testOnlyMinimalPricesAttribute(): void
    {
        $response = $this->cget(
            ['entity' => 'productsearch'],
            ['fields[productsearch]' => 'minimalPrices', 'page[size]' => 1, 'sort' => 'id']
        );

        $this->assertResponseContains(
            [
                'data' => [
                    [
                        'type' => 'productsearch',
                        'id' => '<toString(@product1->id)>',
                        'attributes' => [
                            'minimalPrices' => [
                                [
                                    'price' => '11.0000',
                                    'currencyId' => 'USD',
                                    'unit' => 'item'
                                ]
                            ]
                        ]
                    ]
                ]
            ],
            $response
        );
    }

    public function testOnlyUnitPrecisionsAttribute(): void
    {
        $response = $this->cget(
            ['entity' => 'productsearch'],
            ['fields[productsearch]' => 'unitPrecisions', 'page[size]' => 1, 'sort' => 'id']
        );

        $this->assertResponseContains(
            [
                'data' => [
                    [
                        'type' => 'productsearch',
                        'id' => '<toString(@product1->id)>',
                        'attributes' => [
                            'unitPrecisions' => [
                                ['unit' => 'item', 'precision' => 0, 'default' => true],
                                ['unit' => 'set', 'precision' => 1, 'default' => false]
                            ]
                        ]
                    ]
                ]
            ],
            $response
        );
        // test that unit precision with "sell" === false is not returned
        $responseData = self::jsonToArray($response->getContent());
        self::assertCount(2, $responseData['data'][0]['attributes']['unitPrecisions']);
    }

    public function testFilterByNotLocalizableValueForAnotherLocalization(): void
    {
        $response = $this->cget(
            ['entity' => 'productsearch'],
            ['filter' => ['searchQuery' => 'sku = PSKU1']],
            ['HTTP_X-Localization-ID' => $this->getReference('es')->getId()]
        );

        $this->assertResponseContains(
            [
                'data' => [
                    [
                        'type' => 'productsearch',
                        'id' => '<toString(@product1->id)>',
                        'attributes' => [
                            'name' => 'Product 1 Spanish Name',
                            'shortDescription' => 'Product 1 Spanish Short Description'
                        ]
                    ]
                ]
            ],
            $response
        );
    }

    public function testFilterByLocalizableValueForAnotherLocalization(): void
    {
        $response = $this->cget(
            ['entity' => 'productsearch'],
            ['filter' => ['searchQuery' => 'name = "Product 1 Spanish Name"']],
            ['HTTP_X-Localization-ID' => $this->getReference('es')->getId()]
        );

        $this->assertResponseContains(
            [
                'data' => [
                    [
                        'type' => 'productsearch',
                        'id' => '<toString(@product1->id)>',
                        'attributes' => [
                            'name' => 'Product 1 Spanish Name',
                            'shortDescription' => 'Product 1 Spanish Short Description'
                        ]
                    ]
                ]
            ],
            $response
        );
    }

    public function testFilterByLocalizableFieldAndValueFromAnotherLocalization(): void
    {
        $response = $this->cget(
            ['entity' => 'productsearch'],
            ['filter' => ['searchQuery' => 'name = "Product 1 Spanish Name"']]
        );

        $this->assertResponseContains(['data' => []], $response);
    }

    public function testFilterByDecimalField(): void
    {
        $response = $this->cget(
            ['entity' => 'productsearch'],
            ['filter' => ['searchQuery' => 'minimalPrice = 11']]
        );

        $this->assertResponseContains(
            ['data' => [['type' => 'productsearch', 'id' => '<toString(@product1->id)>']]],
            $response
        );
    }

    public function testExplicitlyDefinedFieldTypeInSearchQuery(): void
    {
        $response = $this->cget(
            ['entity' => 'productsearch'],
            ['filter' => ['searchQuery' => 'decimal minimalPrice = 11']]
        );

        $this->assertResponseContains(
            ['data' => [['type' => 'productsearch', 'id' => '<toString(@product1->id)>']]],
            $response
        );
    }

    public function testFilterBySkuWithEqualsOperator(): void
    {
        $response = $this->cget(
            ['entity' => 'productsearch'],
            ['filter' => ['searchQuery' => 'sku = PSKU1']]
        );

        $this->assertResponseContains(
            ['data' => [['type' => 'productsearch', 'id' => '<toString(@product1->id)>']]],
            $response
        );
    }

    public function testFilterBySeveralSkusWithInOperator(): void
    {
        $response = $this->cget(
            ['entity' => 'productsearch'],
            ['filter' => ['searchQuery' => 'sku in (PSKU1, PSKU3)']]
        );

        $this->assertResponseContains(
            [
                'data' => [
                    [
                        'type' => 'productsearch',
                        'id' => '<toString(@product1->id)>',
                        'attributes' => [
                            'sku' => 'PSKU1'
                        ]
                    ],
                    [
                        'type' => 'productsearch',
                        'id' => '<toString(@product3->id)>',
                        'attributes' => [
                            'sku' => 'PSKU3'
                        ]
                    ]
                ]
            ],
            $response,
            true
        );
    }

    public function testFilterByComplexFilter(): void
    {
        $response = $this->cget(
            ['entity' => 'productsearch'],
            ['filter' => ['searchQuery' => 'sku = PSKU1 or (name = "Product 3" and isVariant = 0)']]
        );

        $this->assertResponseContains(
            [
                'data' => [
                    ['type' => 'productsearch', 'id' => '<toString(@product1->id)>'],
                    ['type' => 'productsearch', 'id' => '<toString(@product3->id)>']
                ]
            ],
            $response,
            true
        );
    }

    public function testFilterByComplexFilterWithEnumAttribute(): void
    {
        $response = $this->cget(
            ['entity' => 'productsearch'],
            ['filter' => ['searchQuery' => 'sku = PSKU3 or (name = "Product 1" and testAttrEnum = option1)']]
        );

        $this->assertResponseContains(
            [
                'data' => [
                    ['type' => 'productsearch', 'id' => '<toString(@product1->id)>'],
                    ['type' => 'productsearch', 'id' => '<toString(@product3->id)>']
                ]
            ],
            $response,
            true
        );
    }

    public function testFilterByMinimalPriceWithUnit(): void
    {
        $response = $this->cget(
            ['entity' => 'productsearch'],
            ['filter' => ['searchQuery' => 'minimalPrice_item = 11']]
        );

        $this->assertResponseContains(
            ['data' => [['type' => 'productsearch', 'id' => '<toString(@product1->id)>']]],
            $response
        );
    }

    public function testFilterByEnumAttributeWithEqualsOperator(): void
    {
        $response = $this->cget(
            ['entity' => 'productsearch'],
            ['filter' => ['searchQuery' => 'testAttrEnum = option1']]
        );

        $this->assertResponseContains(
            [
                'data' => [
                    [
                        'type' => 'productsearch',
                        'id' => '<toString(@product1->id)>'
                    ],
                    [
                        'type' => 'productsearch',
                        'id' => '<toString(@configurable_product1->id)>'
                    ],
                    [
                        'type' => 'productsearch',
                        'id' => '<toString(@configurable_product3->id)>'
                    ]
                ]
            ],
            $response,
            true
        );
    }

    public function testFilterByEnumAttributeWithNotEqualsOperator(): void
    {
        $response = $this->cget(
            ['entity' => 'productsearch'],
            ['filter' => ['searchQuery' => 'testAttrEnum != option1']]
        );

        $this->assertResponseContains(
            [
                'data' => [
                    [
                        'type' => 'productsearch',
                        'id' => '<toString(@product3->id)>'
                    ],
                    [
                        'type' => 'productsearch',
                        'id' => '<toString(@configurable_product2->id)>'
                    ],
                    [
                        'type' => 'productsearch',
                        'id' => '<toString(@product_kit1->id)>'
                    ],
                ]
            ],
            $response,
            true
        );
    }

    public function testFilterByEnumAttributeWithInOperator(): void
    {
        $response = $this->cget(
            ['entity' => 'productsearch'],
            ['filter' => ['searchQuery' => 'testAttrEnum in (option1, option2)']]
        );

        $this->assertResponseContains(
            [
                'data' => [
                    [
                        'type' => 'productsearch',
                        'id' => '<toString(@product1->id)>'
                    ],
                    [
                        'type' => 'productsearch',
                        'id' => '<toString(@product3->id)>'
                    ],
                    [
                        'type' => 'productsearch',
                        'id' => '<toString(@configurable_product1->id)>'
                    ],
                    [
                        'type' => 'productsearch',
                        'id' => '<toString(@configurable_product3->id)>'
                    ]
                ]
            ],
            $response,
            true
        );
    }

    public function testFilterByEnumAttributeWithNotInOperator(): void
    {
        $response = $this->cget(
            ['entity' => 'productsearch'],
            ['filter' => ['searchQuery' => 'testAttrEnum !in (option1, option2)']]
        );

        $this->assertResponseContains(
            [
                'data' => [
                    [
                        'type' => 'productsearch',
                        'id' => '<toString(@configurable_product2->id)>'
                    ],
                    [
                        'type' => 'productsearch',
                        'id' => '<toString(@product_kit1->id)>'
                    ],
                ]
            ],
            $response,
            true
        );
    }

    public function testFilterByStringAttributeWithExistsOperator(): void
    {
        $response = $this->cget(
            ['entity' => 'productsearch'],
            ['filter' => ['searchQuery' => 'testAttrString exists']]
        );

        $this->assertResponseContains(
            [
                'data' => [
                    ['type' => 'productsearch', 'id' => '<toString(@product1->id)>']
                ]
            ],
            $response,
            true
        );
    }

    public function testFilterByStringAttributeWithNotExistsOperator(): void
    {
        $response = $this->cget(
            ['entity' => 'productsearch'],
            ['filter' => ['searchQuery' => 'testAttrString notexists']]
        );

        $this->assertResponseContains(
            [
                'data' => [
                    ['type' => 'productsearch', 'id' => '<toString(@product3->id)>'],
                    ['type' => 'productsearch', 'id' => '<toString(@configurable_product1->id)>'],
                    ['type' => 'productsearch', 'id' => '<toString(@configurable_product2->id)>'],
                    ['type' => 'productsearch', 'id' => '<toString(@configurable_product3->id)>'],
                    ['type' => 'productsearch', 'id' => '<toString(@product_kit1->id)>'],
                ]
            ],
            $response,
            true
        );
    }

    public function testFilterByBooleanAttributeWithExistsOperator(): void
    {
        $response = $this->cget(
            ['entity' => 'productsearch'],
            ['filter' => ['searchQuery' => 'testAttrBoolean exists']]
        );

        $this->assertResponseContains(
            [
                'data' => [
                    ['type' => 'productsearch', 'id' => '<toString(@product1->id)>'],
                    ['type' => 'productsearch', 'id' => '<toString(@product3->id)>'],
                    ['type' => 'productsearch', 'id' => '<toString(@configurable_product1->id)>'],
                    ['type' => 'productsearch', 'id' => '<toString(@configurable_product2->id)>'],
                    ['type' => 'productsearch', 'id' => '<toString(@configurable_product3->id)>'],
                    ['type' => 'productsearch', 'id' => '<toString(@product_kit1->id)>'],
                ]
            ],
            $response,
            true
        );
    }

    public function testFilterByBooleanAttributeWithNotExistsOperator(): void
    {
        $response = $this->cget(
            ['entity' => 'productsearch'],
            ['filter' => ['searchQuery' => 'testAttrBoolean notexists']]
        );

        $this->assertResponseContains(['data' => []], $response);
    }

    public function testFilterByIntegerAttributeWithExistsOperator(): void
    {
        $response = $this->cget(
            ['entity' => 'productsearch'],
            ['filter' => ['searchQuery' => 'testAttrInteger exists']]
        );

        $this->assertResponseContains(
            [
                'data' => [
                    ['type' => 'productsearch', 'id' => '<toString(@product1->id)>'],
                    ['type' => 'productsearch', 'id' => '<toString(@product3->id)>'],
                    ['type' => 'productsearch', 'id' => '<toString(@configurable_product1->id)>'],
                    ['type' => 'productsearch', 'id' => '<toString(@configurable_product2->id)>'],
                    ['type' => 'productsearch', 'id' => '<toString(@product_kit1->id)>'],
                ]
            ],
            $response,
            true
        );
    }

    public function testFilterByIntegerAttributeWithNotExistsOperator(): void
    {
        $response = $this->cget(
            ['entity' => 'productsearch'],
            ['filter' => ['searchQuery' => 'testAttrInteger notexists']]
        );

        $this->assertResponseContains(
            [
                'data' => [
                    ['type' => 'productsearch', 'id' => '<toString(@configurable_product3->id)>'],
                ]
            ],
            $response,
            true
        );
    }

    public function testFilterByMoneyAttributeWithExistsOperator(): void
    {
        $response = $this->cget(
            ['entity' => 'productsearch'],
            ['filter' => ['searchQuery' => 'testAttrMoney exists']]
        );

        $this->assertResponseContains(
            [
                'data' => [
                    ['type' => 'productsearch', 'id' => '<toString(@product1->id)>']
                ]
            ],
            $response,
            true
        );
    }

    public function testFilterByMoneyAttributeWithNotExistsOperator(): void
    {
        $response = $this->cget(
            ['entity' => 'productsearch'],
            ['filter' => ['searchQuery' => 'testAttrMoney notexists']]
        );

        $this->assertResponseContains(
            [
                'data' => [
                    ['type' => 'productsearch', 'id' => '<toString(@product3->id)>'],
                    ['type' => 'productsearch', 'id' => '<toString(@configurable_product1->id)>'],
                    ['type' => 'productsearch', 'id' => '<toString(@configurable_product2->id)>'],
                    ['type' => 'productsearch', 'id' => '<toString(@configurable_product3->id)>'],
                    ['type' => 'productsearch', 'id' => '<toString(@product_kit1->id)>'],
                ]
            ],
            $response,
            true
        );
    }

    public function testTryToFilterByEnumAttributeWithNotSupportedOperator(): void
    {
        $response = $this->cget(
            ['entity' => 'productsearch'],
            ['filter' => ['searchQuery' => 'testAttrEnum > option1']],
            [],
            false
        );

        $this->assertResponseValidationError(
            [
                'title' => 'filter constraint',
                'detail' => 'The operator ">" is not supported for the field "testAttrEnum".'
                    . ' Supported operators: =, !=, in, !in.',
                'source' => ['parameter' => 'filter[searchQuery]']
            ],
            $response
        );
    }

    public function testTryToFilterByEnumAttributeWithNotAllowedOperator(): void
    {
        $response = $this->cget(
            ['entity' => 'productsearch'],
            ['filter' => ['searchQuery' => 'testAttrEnum ~ option1']],
            [],
            false
        );

        $this->assertResponseValidationError(
            [
                'title' => 'filter constraint',
                'detail' => 'Not allowed operator. Unexpected token "operator" of value "~" '
                    . '("operator" expected with value ">, >=, <, <=, =, !=, in, !in, exists, notexists") '
                    . 'around position 14.',
                'source' => ['parameter' => 'filter[searchQuery]']
            ],
            $response
        );
    }

    public function testTryToFilterByTextFieldWithNotSupportedOperator(): void
    {
        $response = $this->cget(
            ['entity' => 'productsearch'],
            ['filter' => ['searchQuery' => 'name > test']],
            [],
            false
        );

        $this->assertResponseValidationError(
            [
                'title' => 'filter constraint',
                'detail' => 'Not allowed operator. Unexpected token "operator" of value ">" '
                    . '("operator" expected with value "~, !~, =, !=, in, !in, starts_with, exists, notexists, like, '
                    . 'notlike") around position 6.',
                'source' => ['parameter' => 'filter[searchQuery]']
            ],
            $response
        );
    }

    public function testTryToFilterByDecimalFieldWithNotSupportedOperator(): void
    {
        $response = $this->cget(
            ['entity' => 'productsearch'],
            ['filter' => ['searchQuery' => 'minimalPrice ~ 11']],
            [],
            false
        );

        $this->assertResponseValidationError(
            [
                'title' => 'filter constraint',
                'detail' => 'Not allowed operator. Unexpected token "operator" of value "~" '
                    . '("operator" expected with value ">, >=, <, <=, =, !=, in, !in, exists, notexists") '
                    . 'around position 14.',
                'source' => ['parameter' => 'filter[searchQuery]']
            ],
            $response
        );
    }

    public function testTryToFilterByTextFieldWithInvalidStringValueFormat(): void
    {
        $response = $this->cget(
            ['entity' => 'productsearch'],
            ['filter' => ['searchQuery' => 'name = text test']],
            [],
            false
        );

        $this->assertResponseValidationError(
            [
                'title' => 'filter constraint',
                'detail' => 'Unexpected string "test" in where statement around position 13.',
                'source' => ['parameter' => 'filter[searchQuery]']
            ],
            $response
        );
    }

    public function testTryToFilterByNotSupportedField(): void
    {
        $response = $this->cget(
            ['entity' => 'productsearch'],
            ['filter' => ['searchQuery' => 'name123 = 10']],
            [],
            false
        );

        $this->assertResponseValidationError(
            [
                'title' => 'filter constraint',
                'detail' => 'The field "name123" is not supported.',
                'source' => ['parameter' => 'filter[searchQuery]']
            ],
            $response
        );
    }

    public function testSortBySku(): void
    {
        $response = $this->cget(
            ['entity' => 'productsearch'],
            ['fields[productsearch]' => 'sku', 'sort' => 'sku']
        );

        $this->assertResponseContains(
            [
                'data' => [
                    [
                        'type' => 'productsearch',
                        'id' => '<toString(@configurable_product1->id)>',
                        'attributes' => [
                            'sku' => 'CPSKU1'
                        ]
                    ],
                    [
                        'type' => 'productsearch',
                        'id' => '<toString(@configurable_product2->id)>',
                        'attributes' => [
                            'sku' => 'CPSKU2'
                        ]
                    ],
                    [
                        'type' => 'productsearch',
                        'id' => '<toString(@configurable_product3->id)>',
                        'attributes' => [
                            'sku' => 'CPSKU3'
                        ]
                    ],
                    [
                        'type' => 'productsearch',
                        'id' => '<toString(@product_kit1->id)>',
                        'attributes' => [
                            'sku' => 'PKSKU1',
                        ],
                    ],
                    [
                        'type' => 'productsearch',
                        'id' => '<toString(@product1->id)>',
                        'attributes' => [
                            'sku' => 'PSKU1'
                        ]
                    ],
                    [
                        'type' => 'productsearch',
                        'id' => '<toString(@product3->id)>',
                        'attributes' => [
                            'sku' => 'PSKU3'
                        ]
                    ]
                ]
            ],
            $response
        );
    }

    public function testSortByLocalizedField(): void
    {
        $response = $this->cget(
            ['entity' => 'productsearch'],
            ['fields[productsearch]' => 'sku,name', 'sort' => '-name']
        );

        $this->assertResponseContains(
            [
                'data' => [
                    [
                        'type' => 'productsearch',
                        'id' => '<toString(@product_kit1->id)>',
                        'attributes' => [
                            'sku' => 'PKSKU1',
                            'name' => 'Product Kit 1'
                        ]
                    ],
                    [
                        'type' => 'productsearch',
                        'id' => '<toString(@product3->id)>',
                        'attributes' => [
                            'sku' => 'PSKU3',
                            'name' => 'Product 3'
                        ]
                    ],
                    [
                        'type' => 'productsearch',
                        'id' => '<toString(@product1->id)>',
                        'attributes' => [
                            'sku' => 'PSKU1',
                            'name' => 'Product 1'
                        ]
                    ],
                    [
                        'type' => 'productsearch',
                        'id' => '<toString(@configurable_product3->id)>',
                        'attributes' => [
                            'sku' => 'CPSKU3',
                            'name' => 'Configurable Product 3'
                        ]
                    ],
                    [
                        'type' => 'productsearch',
                        'id' => '<toString(@configurable_product2->id)>',
                        'attributes' => [
                            'sku' => 'CPSKU2',
                            'name' => 'Configurable Product 2'
                        ]
                    ],
                    [
                        'type' => 'productsearch',
                        'id' => '<toString(@configurable_product1->id)>',
                        'attributes' => [
                            'sku' => 'CPSKU1',
                            'name' => 'Configurable Product 1'
                        ]
                    ]
                ]
            ],
            $response
        );
    }

    public function testSortByIntegerProductAttribute(): void
    {
        $data = [
            [
                'type' => 'productsearch',
                'id' => '<toString(@configurable_product1->id)>'
            ],
            [
                'type' => 'productsearch',
                'id' => '<toString(@product3->id)>'
            ],
            [
                'type' => 'productsearch',
                'id' => '<toString(@configurable_product2->id)>'
            ],
            [
                'type' => 'productsearch',
                'id' => '<toString(@product1->id)>'
            ],
            [
                'type' => 'productsearch',
                'id' => '<toString(@product_kit1->id)>'
            ],
            [
                'type' => 'productsearch',
                'id' => '<toString(@configurable_product3->id)>'
            ],
        ];

        if ($this->isMySqlOrmSearchEngine()) {
            // MySql returns NULL values at the top
            array_unshift($data, array_pop($data));
        }

        $response = $this->cget(
            ['entity' => 'productsearch'],
            ['sort' => 'testAttrInteger']
        );

        $this->assertResponseContains(['data' => $data], $response);
    }

    /**
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     */
    public function testSortByFloatProductAttribute(): void
    {
        $data = [
            [
                'type' => 'productsearch',
                'id' => '<toString(@configurable_product2->id)>'
            ],
            [
                'type' => 'productsearch',
                'id' => '<toString(@product3->id)>'
            ],
            [
                'type' => 'productsearch',
                'id' => '<toString(@product1->id)>'
            ],
            [
                'type' => 'productsearch',
                'id' => '<toString(@configurable_product3->id)>'
            ],
            [
                'type' => 'productsearch',
                'id' => '<toString(@product_kit1->id)>'
            ],
            [
                'type' => 'productsearch',
                'id' => '<toString(@configurable_product1->id)>'
            ],
        ];

        if ($this->isMySqlOrmSearchEngine()) {
            // MySql returns NULL values at the top
            array_unshift($data, array_pop($data));
        }

        $response = $this->cget(
            ['entity' => 'productsearch'],
            ['sort' => 'testAttrFloat', 'include' => 'product', 'fields[products]' => 'productAttributes']
        );

        $this->assertResponseContains(
            [
                'data' => $data,
                'included' => [
                    [
                        'type' => 'products',
                        'id' => '<toString(@configurable_product1->id)>',
                        'attributes' => [
                            'productAttributes' => [
                                'testAttrFloat' => null
                            ]
                        ]
                    ],
                    [
                        'type' => 'products',
                        'id' => '<toString(@configurable_product2->id)>',
                        'attributes' => [
                            'productAttributes' => [
                                'testAttrFloat' => 1.1
                            ]
                        ]
                    ],
                    [
                        'type' => 'products',
                        'id' => '<toString(@product3->id)>',
                        'attributes' => [
                            'productAttributes' => [
                                'testAttrFloat' => 1.2
                            ]
                        ]
                    ],
                    [
                        'type' => 'products',
                        'id' => '<toString(@product1->id)>',
                        'attributes' => [
                            'productAttributes' => [
                                'testAttrFloat' => 1.23
                            ]
                        ]
                    ],
                    [
                        'type' => 'products',
                        'id' => '<toString(@product_kit1->id)>',
                        'attributes' => [
                            'productAttributes' => [
                                'testAttrFloat' => 1.6
                            ]
                        ]
                    ],
                    [
                        'type' => 'products',
                        'id' => '<toString(@configurable_product3->id)>',
                        'attributes' => [
                            'productAttributes' => [
                                'testAttrFloat' => 1.5
                            ]
                        ]
                    ],
                ]
            ],
            $response
        );
    }

    public function testSortByEnumProductAttribute(): void
    {
        $response = $this->cget(
            ['entity' => 'productsearch'],
            ['sort' => 'testAttrEnum']
        );

        $this->assertResponseContains(
            [
                'data' => [
                    [
                        'type' => 'productsearch',
                        'id' => '<toString(@product1->id)>'
                    ],
                    [
                        'type' => 'productsearch',
                        'id' => '<toString(@product3->id)>'
                    ],
                    [
                        'type' => 'productsearch',
                        'id' => '<toString(@configurable_product2->id)>'
                    ],
                    [
                        'type' => 'productsearch',
                        'id' => '<toString(@configurable_product1->id)>'
                    ],
                    [
                        'type' => 'productsearch',
                        'id' => '<toString(@configurable_product3->id)>'
                    ],
                    [
                        'type' => 'productsearch',
                        'id' => '<toString(@product_kit1->id)>'
                    ],
                ]
            ],
            $response
        );
    }

    /**
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     */
    public function testSortByManyToOneProductAttribute(): void
    {
        $response = $this->cget(
            ['entity' => 'productsearch'],
            ['sort' => 'testAttrManyToOne', 'include' => 'product', 'fields[products]' => 'productAttributes']
        );

        $this->assertResponseContains(
            [
                'data' => [
                    [
                        'type' => 'productsearch',
                        'id' => '<toString(@product1->id)>'
                    ],
                    [
                        'type' => 'productsearch',
                        'id' => '<toString(@product3->id)>'
                    ],
                    [
                        'type' => 'productsearch',
                        'id' => '<toString(@configurable_product2->id)>'
                    ],
                    [
                        'type' => 'productsearch',
                        'id' => '<toString(@configurable_product3->id)>'
                    ],
                    [
                        'type' => 'productsearch',
                        'id' => '<toString(@configurable_product1->id)>'
                    ],
                    [
                        'type' => 'productsearch',
                        'id' => '<toString(@product_kit1->id)>'
                    ],
                ],
                'included' => [
                    [
                        'type' => 'products',
                        'id' => '<toString(@product1->id)>',
                        'attributes' => [
                            'productAttributes' => [
                                'testAttrManyToOne' => [
                                    'id' => '<toString(@customer1->id)>',
                                    'targetValue' => 'Company 1'
                                ]
                            ]
                        ]
                    ],
                    [
                        'type' => 'products',
                        'id' => '<toString(@product3->id)>',
                        'attributes' => [
                            'productAttributes' => [
                                'testAttrManyToOne' => [
                                    'id' => '<toString(@customer2->id)>',
                                    'targetValue' => 'Company 2'
                                ]
                            ]
                        ]
                    ],
                    [
                        'type' => 'products',
                        'id' => '<toString(@configurable_product2->id)>',
                        'attributes' => [
                            'productAttributes' => [
                                'testAttrManyToOne' => [
                                    'id' => '<toString(@customer3->id)>',
                                    'targetValue' => 'Company 3'
                                ]
                            ]
                        ]
                    ],
                    [
                        'type' => 'products',
                        'id' => '<toString(@configurable_product3->id)>',
                        'attributes' => [
                            'productAttributes' => [
                                'testAttrManyToOne' => [
                                    'id' => '<toString(@customer4->id)>',
                                    'targetValue' => 'Company 4'
                                ]
                            ]
                        ]
                    ],
                    [
                        'type' => 'products',
                        'id' => '<toString(@configurable_product1->id)>',
                        'attributes' => [
                            'productAttributes' => [
                                'testAttrManyToOne' => [
                                    'id' => '<toString(@customer5->id)>',
                                    'targetValue' => 'Company 5'
                                ]
                            ]
                        ]
                    ],
                    [
                        'type' => 'products',
                        'id' => '<toString(@product_kit1->id)>',
                        'attributes' => [
                            'productAttributes' => [
                                'testAttrManyToOne' => [
                                    'id' => '<toString(@customer6->id)>',
                                    'targetValue' => 'Company 6',
                                ],
                            ]
                        ]
                    ],
                ],
            ],
            $response
        );
    }

    public function testTryToSortByNotSupportedField(): void
    {
        $response = $this->cget(
            ['entity' => 'productsearch'],
            ['sort' => 'name123'],
            [],
            false
        );

        $this->assertResponseValidationError(
            [
                'title' => 'sort constraint',
                'detail' => 'Sorting by "name123" field is not supported.',
                'source' => ['parameter' => 'sort']
            ],
            $response
        );
    }

    public function testTryToGet(): void
    {
        $response = $this->get(
            [
                'entity' => 'productsearch',
                'id' => '<toString(@product1->id)>'
            ],
            [],
            [],
            false
        );

        self::assertResponseStatusCodeEquals($response, Response::HTTP_NOT_FOUND);
    }

    public function testTryToUpdate(): void
    {
        $response = $this->patch(
            [
                'entity' => 'productsearch',
                'id' => '<toString(@product1->id)>',
                'attributes' => [
                    'name' => 'Updated Product Name'
                ]
            ],
            [],
            [],
            false
        );

        self::assertResponseStatusCodeEquals($response, Response::HTTP_NOT_FOUND);
    }

    public function testTryToCreate(): void
    {
        $data = [
            'data' => [
                'type' => 'productsearch',
                'attributes' => [
                    'name' => 'New Product'
                ]
            ]
        ];

        $response = $this->post(
            ['entity' => 'productsearch'],
            $data,
            [],
            false
        );

        self::assertMethodNotAllowedResponse($response, 'OPTIONS, GET');
    }

    public function testTryToDelete(): void
    {
        $response = $this->delete(
            ['entity' => 'productsearch', 'id' => '<toString(@product1->id)>'],
            [],
            [],
            false
        );

        self::assertResponseStatusCodeEquals($response, Response::HTTP_NOT_FOUND);
    }

    public function testTryToDeleteList(): void
    {
        $response = $this->cdelete(
            ['entity' => 'productsearch'],
            ['filter' => ['id' => '<toString(@product1->id)>']],
            [],
            false
        );

        self::assertMethodNotAllowedResponse($response, 'OPTIONS, GET');
    }

    public function testPaginationLinksFirstPage(): void
    {
        $response = $this->cget(
            ['entity' => 'productsearch'],
            ['filter' => ['searchQuery' => 'isVariant = 0'], 'page' => ['size' => 2]],
            ['HTTP_HATEOAS' => true]
        );

        $url = '{baseUrl}/productsearch';
        $urlWithFilter = $url . '?filter%5BsearchQuery%5D=isVariant%20%3D%200';
        $this->assertResponseContains(
            [
                'links' => [
                    'self' => $url,
                    'next' => $urlWithFilter . '&page%5Bsize%5D=2&page%5Bnumber%5D=2'
                ]
            ],
            $response
        );
    }

    public function testPaginationLinksSecondPage(): void
    {
        $response = $this->cget(
            ['entity' => 'productsearch'],
            ['filter' => ['searchQuery' => 'isVariant = 0'], 'page' => ['size' => 2, 'number' => 2]],
            ['HTTP_HATEOAS' => true]
        );

        $url = '{baseUrl}/productsearch';
        $urlWithFilter = $url . '?filter%5BsearchQuery%5D=isVariant%20%3D%200';
        $this->assertResponseContains(
            [
                'links' => [
                    'self' => $url,
                    'first' => $urlWithFilter . '&page%5Bsize%5D=2',
                    'prev' => $urlWithFilter . '&page%5Bsize%5D=2',
                    'next' => $urlWithFilter . '&page%5Bnumber%5D=3&page%5Bsize%5D=2'
                ]
            ],
            $response
        );
    }

    public function testPaginationLinksLastPage(): void
    {
        $response = $this->cget(
            ['entity' => 'productsearch'],
            ['filter' => ['searchQuery' => 'isVariant = 0'], 'page' => ['size' => 2, 'number' => 3]],
            ['HTTP_HATEOAS' => true]
        );

        $url = '{baseUrl}/productsearch';
        $urlWithFilter = $url . '?filter%5BsearchQuery%5D=isVariant%20%3D%200';
        $this->assertResponseContains(
            [
                'links' => [
                    'self' => $url,
                    'first' => $urlWithFilter . '&page%5Bsize%5D=2',
                    'prev' => $urlWithFilter . '&page%5Bnumber%5D=2&page%5Bsize%5D=2'
                ]
            ],
            $response
        );
    }
}
