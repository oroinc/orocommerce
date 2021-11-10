<?php

namespace Oro\Bundle\ProductBundle\Tests\Functional\Controller;

use Oro\Bundle\ConfigBundle\Tests\Functional\Traits\ConfigManagerAwareTestTrait;
use Oro\Bundle\FrontendTestFrameworkBundle\Migrations\Data\ORM\LoadCustomerUserData;
use Oro\Bundle\LocaleBundle\Entity\LocalizedFallbackValue;
use Oro\Bundle\LocaleBundle\Model\FallbackType;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\ProductBundle\Tests\Functional\DataFixtures\LoadProductData;
use Oro\Bundle\ProductBundle\Tests\Functional\Helper\ProductTestHelper;
use Oro\Bundle\RedirectBundle\DependencyInjection\Configuration;
use Symfony\Component\DomCrawler\Crawler;

/**
 * @SuppressWarnings(PHPMD.TooManyMethods)
 * @SuppressWarnings(PHPMD.TooManyPublicMethods)
 */
class ProductControllerTest extends ProductHelperTestCase
{
    use ConfigManagerAwareTestTrait;

    private static array $expectedProductImageMatrixHeaders = ['File', 'Main', 'Listing', 'Additional'];

    protected function setUp(): void
    {
        $this->initClient([], $this->generateBasicAuthHeader());
        $this->client->useHashNavigation(true);
    }

    public function testIndex()
    {
        $crawler = $this->client->request('GET', $this->getUrl('oro_product_index'));
        $result = $this->client->getResponse();
        $this->assertHtmlResponseStatusCodeEquals($result, 200);
        self::assertStringContainsString('products-grid', $crawler->html());
    }

    public function testCreate()
    {
        $crawler = $this->createProduct();

        $expectedProductImageMatrix = [
            self::$expectedProductImageMatrixHeaders,
            [ProductTestHelper::FIRST_IMAGE_FILENAME, 1, 1, 1],
        ];

        $parsedProductImageMatrix = $this->parseProductImages($crawler);

        sort($parsedProductImageMatrix);
        sort($expectedProductImageMatrix);

        $this->assertEquals($expectedProductImageMatrix, $parsedProductImageMatrix);
    }

    /**
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     *
     * @depends testCreate
     */
    public function testUpdate(): int
    {
        $product = $this->getProductDataBySku(ProductTestHelper::TEST_SKU);
        $id = $product->getId();

        $crawler = $this->client->request('GET', $this->getUrl('oro_product_update', ['id' => $id]));
        $this->assertEquals(
            1,
            $crawler->filterXPath("//nav/a[contains(text(),'".ProductTestHelper::CATEGORY_MENU_NAME."')]")->count()
        );
        $form = $crawler->selectButton('Save and Close')->form();

        $data = $form->getPhpValues()['oro_product'];
        $submittedData = $this->getSubmittedData($data, $product, $form);

        $filesData = [
            'oro_product' => [
                'images' => [
                    1 => [
                        'image' => [
                            'file' => $this->createUploadedFile(ProductTestHelper::SECOND_IMAGE_FILENAME)
                        ]
                    ],
                ]
            ]
        ];

        $this->client->followRedirects();
        $this->client->request($form->getMethod(), $form->getUri(), $submittedData, $filesData);
        $result = $this->client->getResponse();
        $this->assertHtmlResponseStatusCodeEquals($result, 200);

        // Check product unit precisions
        $crawler = $this->client->request('GET', $this->getUrl('oro_product_update', ['id' => $id]));

        $actualAdditionalUnitPrecisions = [
            $this->getActualAdditionalUnitPrecision($crawler, 0),
            $this->getActualAdditionalUnitPrecision($crawler, 1),
        ];
        $expectedAdditionalUnitPrecisions = [
            [
                'unit' => ProductTestHelper::SECOND_UNIT_FULL_NAME,
                'precision' => ProductTestHelper::SECOND_UNIT_PRECISION,
                'conversionRate' => 2, 'sell' => false
            ],
            [
                'unit' => ProductTestHelper::THIRD_UNIT_FULL_NAME,
                'precision' => ProductTestHelper::THIRD_UNIT_PRECISION,
                'conversionRate' => 3, 'sell' => true
            ],
        ];

        $this->assertEquals(
            $this->sortUnitPrecisions($expectedAdditionalUnitPrecisions),
            $this->sortUnitPrecisions($actualAdditionalUnitPrecisions)
        );

        return $id;
    }

    /**
     * @depends testUpdate
     */
    public function testView(int $id): int
    {
        $crawler = $this->client->request('GET', $this->getUrl('oro_product_view', ['id' => $id]));

        $result = $this->client->getResponse();
        $this->assertHtmlResponseStatusCodeEquals($result, 200);

        $html = $crawler->html();
        self::assertStringContainsString(
            ProductTestHelper::UPDATED_SKU . ' - ' . ProductTestHelper::DEFAULT_NAME_ALTERED . ' - Products - Products',
            $html
        );
        self::assertStringContainsString(ProductTestHelper::UPDATED_INVENTORY_STATUS, $html);
        self::assertStringContainsString(ProductTestHelper::UPDATED_STATUS, $html);
        self::assertStringContainsString(ProductTestHelper::TYPE, $html);
        $this->assertProductPrecision(
            $id,
            ProductTestHelper::SECOND_UNIT_CODE,
            ProductTestHelper::SECOND_UNIT_PRECISION
        );
        $this->assertProductPrecision($id, ProductTestHelper::THIRD_UNIT_CODE, ProductTestHelper::THIRD_UNIT_PRECISION);

        $expectedProductImageMatrix = [
            self::$expectedProductImageMatrixHeaders,
            [ProductTestHelper::FIRST_IMAGE_FILENAME, 1, 1, 0],
            [ProductTestHelper::SECOND_IMAGE_FILENAME, 0, 0, 1]
        ];

        $parsedProductImageMatrix = $this->parseProductImages($crawler);

        sort($parsedProductImageMatrix);
        sort($expectedProductImageMatrix);

        $this->assertEquals($expectedProductImageMatrix, $parsedProductImageMatrix);

        return $id;
    }

    /**
     * @depends testView
     */
    public function testDuplicate(): int
    {
        $this->client->followRedirects();

        $crawler = $this->client->getCrawler();
        $button = $crawler->selectLink('Duplicate');
        $this->assertCount(1, $button);
        $buttonOptions = json_decode((string) $button->attr('data-options'), true, 512, JSON_THROW_ON_ERROR);

        $this->client->request(
            'POST',
            $button->attr('data-operation-url'),
            $buttonOptions['executionTokenData'] ?? [],
            [],
            ['HTTP_X-Requested-With' => 'XMLHttpRequest']
        );
        $response = $this->client->getResponse();
        $this->assertJsonResponseStatusCodeEquals($response, 200);
        $data = json_decode($response->getContent(), true, 512, JSON_THROW_ON_ERROR);
        $this->assertArrayHasKey('redirectUrl', $data);

        $crawler = $this->client->request('GET', $data['redirectUrl']);
        $html = $crawler->html();
        self::assertStringContainsString('Product has been duplicated', $html);
        self::assertStringContainsString(
            ProductTestHelper::FIRST_DUPLICATED_SKU . ' - ' .
            ProductTestHelper::DEFAULT_NAME_ALTERED . ' - Products - Products',
            $html
        );
        self::assertStringContainsString(ProductTestHelper::UPDATED_INVENTORY_STATUS, $html);
        self::assertStringContainsString(ProductTestHelper::STATUS, $html);

        self::assertStringContainsString(
            $this->createPrimaryUnitPrecisionString(
                ProductTestHelper::FIRST_UNIT_FULL_NAME,
                ProductTestHelper::FIRST_UNIT_PRECISION
            ),
            $html
        );
        $this->assertContainsAdditionalUnitPrecision(
            ProductTestHelper::SECOND_UNIT_FULL_NAME,
            ProductTestHelper::SECOND_UNIT_PRECISION,
            $html
        );
        $this->assertContainsAdditionalUnitPrecision(
            ProductTestHelper::THIRD_UNIT_FULL_NAME,
            ProductTestHelper::THIRD_UNIT_PRECISION,
            $html
        );

        $expectedProductImageMatrix = [
            self::$expectedProductImageMatrixHeaders,
            [ProductTestHelper::FIRST_IMAGE_FILENAME, 1, 1, 0],
            [ProductTestHelper::SECOND_IMAGE_FILENAME, 0, 0, 1]
        ];

        $parsedProductImageMatrix = $this->parseProductImages($crawler);

        sort($parsedProductImageMatrix);
        sort($expectedProductImageMatrix);

        $this->assertEquals($expectedProductImageMatrix, $parsedProductImageMatrix);

        $product = $this->getProductDataBySku(ProductTestHelper::FIRST_DUPLICATED_SKU);

        return $product->getId();
    }

    /**
     * @depends testDuplicate
     */
    public function testSaveAndDuplicate(): int
    {
        $product = $this->getProductDataBySku(ProductTestHelper::FIRST_DUPLICATED_SKU);
        $id = $product->getId();
        $localization = $this->getLocalization();
        $localizedName = $this->getLocalizedName($product, $localization);

        $crawler = $this->client->request('GET', $this->getUrl('oro_product_update', ['id' => $id]));

        $form = $crawler->selectButton('Save and Close')->form();

        $data = $form->getPhpValues()['oro_product'];
        $submittedData = [
            'input_action' => 'save_and_duplicate',
            'oro_product' => array_merge($data, [
                '_token' => $form['oro_product[_token]']->getValue(),
                'sku' => ProductTestHelper::FIRST_DUPLICATED_SKU,
                'owner' => $this->getBusinessUnitId(),
                'inventory_status' => Product::INVENTORY_STATUS_OUT_OF_STOCK,
                'status' => Product::STATUS_ENABLED,
                'type' => Product::TYPE_SIMPLE,
                'primaryUnitPrecision' => $form->getPhpValues()['oro_product']['primaryUnitPrecision'],
                'additionalUnitPrecisions' => $form->getPhpValues()['oro_product']['additionalUnitPrecisions'],
                'names' => [
                    'values' => [
                        'default' => ProductTestHelper::DEFAULT_NAME_ALTERED,
                        'localizations' => [
                            $localization->getId() => [
                                'fallback' => FallbackType::SYSTEM
                            ]
                        ],
                    ],
                    'ids' => [
                        $localization->getId() => $localizedName->getId()
                    ],
                ],
                'descriptions' => [
                    'values' => [
                        'default' => ['wysiwyg' => ProductTestHelper::DEFAULT_DESCRIPTION],
                        'localizations' => [
                            $localization->getId() => [
                                'fallback' => FallbackType::SYSTEM
                            ]
                        ],
                    ],
                    'ids' => [
                        $localization->getId() => $localizedName->getId()
                    ],
                ],
                'shortDescriptions' => [
                    'values' => [
                        'default' => ProductTestHelper::DEFAULT_SHORT_DESCRIPTION,
                        'localizations' => [
                            $localization->getId() => [
                                'fallback' => FallbackType::SYSTEM
                            ]
                        ],
                    ],
                    'ids' => [
                        $localization->getId() => $localizedName->getId()
                    ],
                ],
                'images' => []//remove all images
            ]),
        ];

        $this->client->followRedirects();

        $crawler = $this->client->request($form->getMethod(), $form->getUri(), $submittedData);
        $result = $this->client->getResponse();
        $this->assertHtmlResponseStatusCodeEquals($result, 200);

        $this->checkDuplicateProduct($crawler);

        $product = $this->getProductDataBySku(ProductTestHelper::UPDATED_SKU);

        return $product->getId();
    }

    /**
     * @depends testUpdate
     */
    public function testPrimaryPrecisionAdditionalPrecisionSwap(): int
    {
        $product = $this->getProductDataBySku(ProductTestHelper::UPDATED_SKU);
        $id = $product->getId();
        $crawler = $this->client->request('GET', $this->getUrl('oro_product_update', ['id' => $id]));
        $form = $crawler->selectButton('Save and Close')->form();

        $formValues = $form->getPhpValues();

        $additionalUnit = array_pop($formValues['oro_product']['additionalUnitPrecisions']);
        $primaryUnit = $formValues['oro_product']['primaryUnitPrecision'];
        $formValues['oro_product']['additionalUnitPrecisions'][2] = $primaryUnit;

        $formValues['oro_product']['primaryUnitPrecision'] = [
            'unit' => $additionalUnit['unit'],
            'precision' => $additionalUnit['precision']
        ];

        $this->client->request($form->getMethod(), $form->getUri(), $formValues);

        $result = $this->client->getResponse();
        $this->assertHtmlResponseStatusCodeEquals($result, 200);
        // Check product unit precisions
        $crawler = $this->client->request('GET', $this->getUrl('oro_product_update', ['id' => $id]));
        $actualUnitPrecisions = [
            [
                'unit' => $crawler
                    ->filter('select[name="oro_product[primaryUnitPrecision][unit]"] :selected')
                    ->html(),
                'precision' => $crawler
                    ->filter('input[name="oro_product[primaryUnitPrecision][precision]"]')
                    ->extract(['value'])[0],
                'conversionRate' => $crawler
                    ->filter('input[name="oro_product[primaryUnitPrecision][conversionRate]"]')
                    ->extract(['value'])[0],
                'sell' => $crawler
                    ->filter('input[name="oro_product[primaryUnitPrecision][sell]"]')
                    ->extract(['value'])[0],
            ],
            $this->getActualAdditionalUnitPrecision($crawler, 0),
            $this->getActualAdditionalUnitPrecision($crawler, 1),
        ];
        $expectedUnitPrecisions = [
            [
                'unit' => ProductTestHelper::THIRD_UNIT_FULL_NAME,
                'precision' => ProductTestHelper::THIRD_UNIT_PRECISION,
                'conversionRate' => 1, 'sell' => true
            ],
            [
                'unit' => ProductTestHelper::FIRST_UNIT_FULL_NAME,
                'precision' => ProductTestHelper::FIRST_UNIT_PRECISION,
                'conversionRate' => 1, 'sell' => true
            ],
            [
                'unit' => ProductTestHelper::SECOND_UNIT_FULL_NAME,
                'precision' => ProductTestHelper::SECOND_UNIT_PRECISION,
                'conversionRate' => 2, 'sell' => false
            ],
        ];
        $this->assertEquals(
            $expectedUnitPrecisions,
            $actualUnitPrecisions
        );
        return $id;
    }

    /**
     * @depends testUpdate
     */
    public function testRemoveAddSameAdditionalPrecision(): int
    {
        $product = $this->getProductDataBySku(ProductTestHelper::UPDATED_SKU);
        $id = $product->getId();
        $crawler = $this->client->request('GET', $this->getUrl('oro_product_update', ['id' => $id]));
        $form = $crawler->selectButton('Save and Close')->form();

        $formValues = $form->getPhpValues();

        $additionalUnit = array_pop($formValues['oro_product']['additionalUnitPrecisions']);
        $formValues['oro_product']['additionalUnitPrecisions'][2] = $additionalUnit;

        $this->client->request($form->getMethod(), $form->getUri(), $formValues);

        $result = $this->client->getResponse();
        $this->assertHtmlResponseStatusCodeEquals($result, 200);
        // Check product unit precisions
        $crawler = $this->client->request('GET', $this->getUrl('oro_product_update', ['id' => $id]));
        $actualUnitPrecisions = [
            $this->getActualAdditionalUnitPrecision($crawler, 0),
            $this->getActualAdditionalUnitPrecision($crawler, 1),
        ];
        $expectedUnitPrecisions = [
            [
                'unit' => ProductTestHelper::FIRST_UNIT_FULL_NAME,
                'precision' => ProductTestHelper::FIRST_UNIT_PRECISION,
                'conversionRate' => 1, 'sell' => true],
            [
                'unit' => ProductTestHelper::SECOND_UNIT_FULL_NAME,
                'precision' => ProductTestHelper::SECOND_UNIT_PRECISION,
                'conversionRate' => 2, 'sell' => false
            ],
        ];
        $this->assertEquals($expectedUnitPrecisions, $actualUnitPrecisions);

        return $id;
    }

    /**
     * @depends testUpdate
     */
    public function testGetChangedUrlsWhenNoSlugChanged()
    {
        /** @var Product $product */
        $product = $this->getProductDataBySku(ProductTestHelper::UPDATED_SKU);

        $crawler = $this->client->request('GET', $this->getUrl('oro_product_update', ['id' => $product->getId()]));
        $form = $crawler->selectButton('Save')->form();
        $formValues = $form->getPhpValues();

        $this->client->request(
            'POST',
            $this->getUrl('oro_product_get_changed_slugs', ['id' => $product->getId()]),
            $formValues
        );

        $response = $this->client->getResponse();
        $this->assertEquals('[]', $response->getContent());
    }

    /**
     * @depends testUpdate
     */
    public function testGetChangedUrlsWhenSlugChanged()
    {
        $englishLocalization = $this->getContainer()->get('oro_locale.manager.localization')
            ->getDefaultLocalization(false);

        /** @var Product $product */
        $product = $this->getProductDataBySku(ProductTestHelper::UPDATED_SKU);

        $product->getSlugPrototypes()->clear();

        $product->setDefaultSlugPrototype('old-default-slug');
        $slugPrototype = new LocalizedFallbackValue();
        $slugPrototype->setString('old-english-slug')->setLocalization($englishLocalization);

        $product->addSlugPrototype($slugPrototype);

        $entityManager = $this->getContainer()->get('doctrine')->getManagerForClass(Product::class);
        $entityManager->persist($product);
        $entityManager->flush();

        $crawler = $this->client->request('GET', $this->getUrl('oro_product_update', ['id' => $product->getId()]));
        $form = $crawler->selectButton('Save')->form();
        $formValues = $form->getPhpValues();
        $formValues['oro_product']['slugPrototypesWithRedirect'] = [
            'slugPrototypes' => [
                'values' => [
                    'default' => 'default-slug',
                    'localizations' => [
                        $englishLocalization->getId() => [
                            'value' => 'english-slug'
                        ],
                    ]
                ]
            ]
        ];

        $this->client->request(
            'POST',
            $this->getUrl('oro_product_get_changed_slugs', ['id' => $product->getId()]),
            $formValues
        );

        $expectedData = [
            'Default Value' => ['before' => '/old-default-slug', 'after' => '/default-slug'],
            'English (United States)' => ['before' => '/old-english-slug','after' => '/english-slug']
        ];

        $response = $this->client->getResponse();
        $this->assertJsonStringEqualsJsonString(
            json_encode($expectedData, JSON_THROW_ON_ERROR),
            $response->getContent()
        );
    }

    public function getChangedDefaultSlugDataProvider(): array
    {
        return [
            'Product name empty, redirect strategy empty' => [
                'requestParams' => [],
                'redirectStrategy' => Configuration::STRATEGY_ASK,
                'expected' => '{"showRedirectConfirmation":true,"slugsData":[]}',
            ],
            'Product name set, redirect strategy ask' => [
                'requestParams' => [
                    'productName' => 'New product name',
                ],
                'redirectStrategy' => Configuration::STRATEGY_ASK,
                'expected' => '{"showRedirectConfirmation":true,'
                    . '"slugsData":{"Default Value":{"before":"\/old-default-slug","after":"\/new-product-name"}}}',
            ],
            'Product name set, redirect strategy never' => [
                'requestParams' => [
                    'productName' => 'New product name',
                ],
                'redirectStrategy' => Configuration::STRATEGY_NEVER,
                'expected' => '{"showRedirectConfirmation":false,'
                    . '"slugsData":{"Default Value":{"before":"\/old-default-slug","after":"\/new-product-name"}}}',
            ],
        ];
    }

    /**
     * @depends testGetChangedUrlsWhenSlugChanged
     * @dataProvider getChangedDefaultSlugDataProvider
     */
    public function testGetChangedDefaultSlug(array $requestParams, string $redirectStrategy, string $expected)
    {
        /** @var Product $product */
        $product = $this->getProductDataBySku(ProductTestHelper::UPDATED_SKU);

        $configManager = self::getConfigManager();
        $configManager->set('oro_redirect.redirect_generation_strategy', $redirectStrategy);
        $configManager->flush();
        $configManager->reload();

        $this->client->request(
            'POST',
            $this->getUrl('oro_product_get_changed_default_slug', ['id' => $product->getId()]),
            $requestParams
        );

        $response = $this->client->getResponse();
        $this->assertEquals($expected, $response->getContent());
    }

    /**
     * @depends testSaveAndDuplicate
     */
    public function testDelete(int $id)
    {
        $crawler = $this->client->request('GET', $this->getUrl('oro_product_view', ['id' => $id]));
        $button = $crawler->selectLink('Delete');
        $this->assertNotEmpty($button);
        $buttonOptions = json_decode((string) $button->attr('data-options'), true, 512, JSON_THROW_ON_ERROR);

        $this->client->request(
            'POST',
            $button->attr('data-operation-url'),
            $buttonOptions['executionTokenData'] ?? [],
            [],
            ['HTTP_X_REQUESTED_WITH' => 'XMLHttpRequest']
        );
        $this->assertJsonResponseStatusCodeEquals($this->client->getResponse(), 200);
        $this->assertEquals(
            [
                'success'     => true,
                'message'     => '',
                'messages'    => [],
                'redirectUrl' => $this->getUrl('oro_product_index'),
                'pageReload'  => true
            ],
            json_decode($this->client->getResponse()->getContent(), true, 512, JSON_THROW_ON_ERROR)
        );

        $this->client->request('GET', $this->getUrl('oro_product_view', ['id' => $id]));

        $result = $this->client->getResponse();
        $this->assertHtmlResponseStatusCodeEquals($result, 404);
    }

    public function testFeaturedProductsOnFrontendRootAfterUpdatingProduct()
    {
        $this->loadFixtures([LoadProductData::class]);
        $this->getContainer()->get('oro_website_search.indexer')
            ->reindex(Product::class);

        $product = $this->getProductDataBySku('продукт-7');
        $crawler = $this->client->request(
            'GET',
            $this->getUrl('oro_product_update', ['id' => $product->getId()])
        );
        $form = $crawler->selectButton('Save and Close')->form();
        $this->client->followRedirects();
        $this->client->submit($form);

        $this->initClient(
            [],
            $this->generateBasicAuthHeader(LoadCustomerUserData::AUTH_USER, LoadCustomerUserData::AUTH_PW)
        );
        $crawler = $this->client->request('GET', $this->getUrl('oro_frontend_root'));

        $this->assertEquals(3, $crawler->filter('.featured-product')->count());
    }

    public function testValidationForLocalizedFallbackValues()
    {
        $crawler = $this->client->request('GET', $this->getUrl('oro_product_create'));
        $form = $crawler->selectButton('Continue')->form();
        $formValues = $form->getPhpValues();
        $formValues['input_action'] = 'oro_product_create';
        $formValues['oro_product_step_one']['category'] = ProductTestHelper::CATEGORY_ID;
        $formValues['oro_product_step_one']['type'] = Product::TYPE_SIMPLE;
        $formValues['oro_product_step_one']['attributeFamily'] = ProductTestHelper::ATTRIBUTE_FAMILY_ID;

        $this->client->followRedirects();
        $crawler = $this->client->request('POST', $this->getUrl('oro_product_create'), $formValues);

        $form = $crawler->selectButton('Save and Close')->form();

        $bigStringValue = str_repeat('a', 256);
        $formValues = $form->getPhpValues();
        $formValues['oro_product']['sku'] = ProductTestHelper::TEST_SKU;
        $formValues['oro_product']['owner'] = $this->getBusinessUnitId();
        $formValues['oro_product']['names']['values']['default'] = $bigStringValue;
        $formValues['oro_product']['slugPrototypesWithRedirect']['slugPrototypes'] = [
            'values' => ['default' => $bigStringValue]
        ];
        $formValues['oro_product']['type'] = Product::TYPE_SIMPLE;

        $this->client->followRedirects();
        $crawler = $this->client->request($form->getMethod(), $form->getUri(), $formValues);

        $result = $this->client->getResponse();
        $this->assertHtmlResponseStatusCodeEquals($result, 200);

        $this->assertEquals(
            2,
            $crawler->filterXPath(
                "//li[contains(text(),'This value is too long. It should have 255 characters or less.')]"
            )->count()
        );
    }

    private function checkDuplicateProduct(Crawler $crawler): void
    {
        $html = $crawler->html();

        self::assertStringContainsString('Product has been saved and duplicated', $html);
        self::assertStringContainsString(
            ProductTestHelper::SECOND_DUPLICATED_SKU . ' - ' .
            ProductTestHelper::DEFAULT_NAME_ALTERED . ' - Products - Products',
            $html
        );
        self::assertStringContainsString(ProductTestHelper::UPDATED_INVENTORY_STATUS, $html);
        self::assertStringContainsString(ProductTestHelper::STATUS, $html);

        self::assertStringContainsString(
            $this->createPrimaryUnitPrecisionString(
                ProductTestHelper::FIRST_UNIT_FULL_NAME,
                ProductTestHelper::FIRST_UNIT_PRECISION
            ),
            $html
        );
        $this->assertContainsAdditionalUnitPrecision(
            ProductTestHelper::SECOND_UNIT_FULL_NAME,
            ProductTestHelper::SECOND_UNIT_PRECISION,
            $html
        );
        $this->assertContainsAdditionalUnitPrecision(
            ProductTestHelper::THIRD_UNIT_FULL_NAME,
            ProductTestHelper::THIRD_UNIT_PRECISION,
            $html
        );

        $this->assertEmpty($this->parseProductImages($crawler));
    }
}
