<?php

namespace Oro\Bundle\ProductBundle\Tests\Functional\Controller;

use Oro\Bundle\FrontendTestFrameworkBundle\Migrations\Data\ORM\LoadCustomerUserData;
use Oro\Bundle\LocaleBundle\Entity\Localization;
use Oro\Bundle\LocaleBundle\Entity\LocalizedFallbackValue;
use Oro\Bundle\LocaleBundle\Model\FallbackType;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\ProductBundle\Tests\Functional\DataFixtures\LoadFeaturedProductData;
use Oro\Bundle\ProductBundle\Tests\Functional\Helper\ProductTestHelper;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;

use Symfony\Component\DomCrawler\Crawler;
use Symfony\Component\DomCrawler\Form;
use Symfony\Component\HttpFoundation\File\UploadedFile;

/**
 * @SuppressWarnings(PHPMD.TooManyMethods)
 * @SuppressWarnings(PHPMD.TooManyPublicMethods)
 */
class ProductControllerTest extends WebTestCase
{
    /**
     * @var array
     */
    private static $expectedProductImageMatrixHeaders = ['File', 'Main', 'Listing', 'Additional'];

    /**
     * {@inheritdoc}
     */
    protected function setUp()
    {
        $this->initClient([], $this->generateBasicAuthHeader());
        $this->client->useHashNavigation(true);
    }



    public function testIndex()
    {
        $crawler = $this->client->request('GET', $this->getUrl('oro_product_index'));
        $result = $this->client->getResponse();
        $this->assertHtmlResponseStatusCodeEquals($result, 200);
        $this->assertContains('products-grid', $crawler->html());
    }

    public function testCreate()
    {
        $crawler = $this->client->request('GET', $this->getUrl('oro_product_create'));
        $this->assertEquals(
            1,
            $crawler->filterXPath("//li/a[contains(text(),'".ProductTestHelper::CATEGORY_MENU_NAME."')]")->count()
        );

        $this->assertEquals(
            1,
            $crawler->filterXPath("//select/option[contains(text(),'Simple')]")->count()
        );

        $this->assertEquals(
            1,
            $crawler->filterXPath("//select/option[contains(text(),'Configurable')]")->count()
        );

        $form = $crawler->selectButton('Continue')->form();
        $formValues = $form->getPhpValues();
        $formValues['input_action'] = 'oro_product_create';
        $formValues['oro_product_step_one']['category'] = ProductTestHelper::CATEGORY_ID;
        $formValues['oro_product_step_one']['type'] = Product::TYPE_SIMPLE;
        $formValues['oro_product_step_one']['attributeFamily'] = ProductTestHelper::ATTRIBUTE_FAMILY_ID;

        $this->client->followRedirects(true);
        $crawler = $this->client->request(
            'POST',
            $this->getUrl('oro_product_create'),
            $formValues
        );

        $result = $this->client->getResponse();
        $this->assertHtmlResponseStatusCodeEquals($result, 200);
        $this->assertEquals(
            0,
            $crawler->filterXPath("//li/a[contains(text(),'".ProductTestHelper::CATEGORY_MENU_NAME."')]")->count()
        );
        $this->assertContains("Category: ".ProductTestHelper::CATEGORY_NAME, $crawler->html());

        $form = $crawler->selectButton('Save and Close')->form();
        $this->assertDefaultProductUnit($form);

        $formValues = $form->getPhpValues();
        $formValues['oro_product']['sku'] = ProductTestHelper::TEST_SKU;
        $formValues['oro_product']['owner'] = $this->getBusinessUnitId();
        $formValues['oro_product']['inventory_status'] = Product::INVENTORY_STATUS_IN_STOCK;
        $formValues['oro_product']['status'] = Product::STATUS_DISABLED;
        $formValues['oro_product']['names']['values']['default'] = ProductTestHelper::DEFAULT_NAME;
        $formValues['oro_product']['descriptions']['values']['default'] = ProductTestHelper::DEFAULT_DESCRIPTION;
        $formValues['oro_product']['shortDescriptions']['values']['default'] =
            ProductTestHelper::DEFAULT_SHORT_DESCRIPTION;
        $formValues['oro_product']['type'] = Product::TYPE_SIMPLE;
        $formValues['oro_product']['additionalUnitPrecisions'][] = [
            'unit' => ProductTestHelper::FIRST_UNIT_CODE,
            'precision' => ProductTestHelper::FIRST_UNIT_PRECISION,
            'conversionRate' => 10,
            'sell' => true,
        ];

        $formValues['oro_product']['images'][] = [
            'main' => 1,
            'listing' => 1,
            'additional' => 1
        ];

        $filesData['oro_product']['images'][] = [
            'image' => [
                'file' => $this->createUploadedFile(ProductTestHelper::FIRST_IMAGE_FILENAME)
            ]
        ];

        $this->client->followRedirects(true);
        $crawler = $this->client->request($form->getMethod(), $form->getUri(), $formValues, $filesData);

        $result = $this->client->getResponse();
        $this->assertHtmlResponseStatusCodeEquals($result, 200);

        $html = $crawler->html();
        $this->assertContains('Product has been saved', $html);
        $this->assertContains(ProductTestHelper::TEST_SKU, $html);
        $this->assertContains(ProductTestHelper::INVENTORY_STATUS, $html);
        $this->assertContains(ProductTestHelper::STATUS, $html);
        $this->assertContains(ProductTestHelper::FIRST_UNIT_CODE, $html);

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
     * @return int
     */
    public function testUpdate()
    {
        $product = $this->getProductDataBySku(ProductTestHelper::TEST_SKU);
        $id = $product->getId();
        $localization = $this->getLocalization();
        $localizedName = $this->getLocalizedName($product, $localization);

        $crawler = $this->client->request('GET', $this->getUrl('oro_product_update', ['id' => $id]));
        $this->assertEquals(
            1,
            $crawler->filterXPath("//li/a[contains(text(),'".ProductTestHelper::CATEGORY_MENU_NAME."')]")->count()
        );
        /** @var Form $form */
        $form = $crawler->selectButton('Save and Close')->form();

        $data = $form->getPhpValues()['oro_product'];
        $submittedData = [
            'input_action' => 'save_and_stay',
            'oro_product' => array_merge($data, [
                '_token' => $form['oro_product[_token]']->getValue(),
                'sku' => ProductTestHelper::UPDATED_SKU,
                'owner' => $this->getBusinessUnitId(),
                'inventory_status' => Product::INVENTORY_STATUS_OUT_OF_STOCK,
                'status' => Product::STATUS_ENABLED,
                'type' => Product::TYPE_SIMPLE,
                'primaryUnitPrecision' => [
                    'unit' => ProductTestHelper::FIRST_UNIT_CODE,
                    'precision' => ProductTestHelper::FIRST_UNIT_PRECISION,
                ],
                'additionalUnitPrecisions' => [
                    [
                        'unit' => ProductTestHelper::SECOND_UNIT_CODE,
                        'precision' => ProductTestHelper::SECOND_UNIT_PRECISION,
                        'conversionRate' => 2, 'sell' => false
                    ],
                    [
                        'unit' => ProductTestHelper::THIRD_UNIT_CODE,
                        'precision' => ProductTestHelper::THIRD_UNIT_PRECISION,
                        'conversionRate' => 3, 'sell' => true
                    ]
                ],
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
                        'default' => ProductTestHelper::DEFAULT_DESCRIPTION,
                        'localizations' => [
                            $localization->getId() => ['fallback' => FallbackType::SYSTEM
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
                'images' => [
                    0 => [
                        'main' => 1,
                        'listing' => 1
                    ],
                    1 => [
                        'additional' => 1
                    ]
                ]
            ]),
        ];

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

        $this->client->followRedirects(true);
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
     * @param int $id
     */
    public function testView($id)
    {
        $crawler = $this->client->request('GET', $this->getUrl('oro_product_view', ['id' => $id]));

        $result = $this->client->getResponse();
        $this->assertHtmlResponseStatusCodeEquals($result, 200);

        $html = $crawler->html();
        $this->assertContains(
            ProductTestHelper::UPDATED_SKU . ' - ' . ProductTestHelper::DEFAULT_NAME_ALTERED . ' - Products - Products',
            $html
        );
        $this->assertContains(ProductTestHelper::UPDATED_INVENTORY_STATUS, $html);
        $this->assertContains(ProductTestHelper::UPDATED_STATUS, $html);
        $this->assertContains(ProductTestHelper::TYPE, $html);
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
    }

    /**
     * @depends testView
     * @return int
     */
    public function testDuplicate()
    {
        $this->client->followRedirects(true);

        $crawler = $this->client->getCrawler();
        $button = $crawler->selectLink('Duplicate');
        $this->assertCount(1, $button);

        $headers = ['HTTP_X-Requested-With' => 'XMLHttpRequest'];
        $this->client->request('GET', $button->attr('data-operation-url'), [], [], $headers);
        $response = $this->client->getResponse();
        $this->assertJsonResponseStatusCodeEquals($response, 200);
        $data = json_decode($response->getContent(), true);
        $this->assertArrayHasKey('redirectUrl', $data);

        $crawler = $this->client->request('GET', $data['redirectUrl']);
        $html = $crawler->html();
        $this->assertContains('Product has been duplicated', $html);
        $this->assertContains(
            ProductTestHelper::FIRST_DUPLICATED_SKU . ' - ' .
            ProductTestHelper::DEFAULT_NAME_ALTERED . ' - Products - Products',
            $html
        );
        $this->assertContains(ProductTestHelper::UPDATED_INVENTORY_STATUS, $html);
        $this->assertContains(ProductTestHelper::STATUS, $html);

        $this->assertContains(
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
     *
     * @return int
     */
    public function testSaveAndDuplicate()
    {
        $product = $this->getProductDataBySku(ProductTestHelper::FIRST_DUPLICATED_SKU);
        $id = $product->getId();
        $localization = $this->getLocalization();
        $localizedName = $this->getLocalizedName($product, $localization);

        $crawler = $this->client->request('GET', $this->getUrl('oro_product_update', ['id' => $id]));

        /** @var Form $form */
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
                        'default' => ProductTestHelper::DEFAULT_DESCRIPTION,
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

        $this->client->followRedirects(true);

        $crawler = $this->client->request($form->getMethod(), $form->getUri(), $submittedData);
        $result = $this->client->getResponse();
        $this->assertHtmlResponseStatusCodeEquals($result, 200);

        $html = $crawler->html();
        $this->assertContains('Product has been saved and duplicated', $html);
        $this->assertContains(
            ProductTestHelper::SECOND_DUPLICATED_SKU . ' - ' .
            ProductTestHelper::DEFAULT_NAME_ALTERED . ' - Products - Products',
            $html
        );
        $this->assertContains(ProductTestHelper::UPDATED_INVENTORY_STATUS, $html);
        $this->assertContains(ProductTestHelper::STATUS, $html);

        $this->assertContains(
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

        $product = $this->getProductDataBySku(ProductTestHelper::UPDATED_SKU);

        return $product->getId();
    }

    /**
     * @depends testUpdate
     * @return int
     */
    public function testPrimaryPrecisionAdditionalPrecisionSwap()
    {
        $product = $this->getProductDataBySku(ProductTestHelper::UPDATED_SKU);
        $id = $product->getId();
        $crawler = $this->client->request('GET', $this->getUrl('oro_product_update', ['id' => $id]));
        /** @var Form $form */
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
                    ->extract('value')[0],
                'conversionRate' => $crawler
                    ->filter('input[name="oro_product[primaryUnitPrecision][conversionRate]"]')
                    ->extract('value')[0],
                'sell' => $crawler
                    ->filter('input[name="oro_product[primaryUnitPrecision][sell]"]')
                    ->extract('value')[0],
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
     * @return int
     */
    public function testRemoveAddSameAdditionalPrecision()
    {
        $product = $this->getProductDataBySku(ProductTestHelper::UPDATED_SKU);
        $id = $product->getId();
        $crawler = $this->client->request('GET', $this->getUrl('oro_product_update', ['id' => $id]));
        /** @var Form $form */
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
            'English' => ['before' => '/old-english-slug','after' => '/english-slug']
        ];

        $response = $this->client->getResponse();
        $this->assertJsonStringEqualsJsonString(json_encode($expectedData), $response->getContent());
    }

    /**
     * @depends testSaveAndDuplicate
     * @param int $id
     */
    public function testDelete($id)
    {
        $this->client->request(
            'GET',
            $this->getUrl(
                'oro_action_operation_execute',
                [
                    'operationName' => 'DELETE',
                    'entityId'      => $id,
                    'entityClass'   => $this->getContainer()->getParameter('oro_product.entity.product.class'),
                ]
            ),
            [],
            [],
            ['HTTP_X_REQUESTED_WITH' => 'XMLHttpRequest']
        );
        $this->assertJsonResponseStatusCodeEquals($this->client->getResponse(), 200);
        $this->assertEquals(
            [
                'success'     => true,
                'message'     => '',
                'messages'    => [],
                'redirectUrl' => $this->getUrl('oro_product_index')
            ],
            json_decode($this->client->getResponse()->getContent(), true)
        );

        $this->client->request('GET', $this->getUrl('oro_product_view', ['id' => $id]));

        $result = $this->client->getResponse();
        $this->assertHtmlResponseStatusCodeEquals($result, 404);
    }

    public function testFeaturedProductsOnFrontendRootAfterUpdatingProduct()
    {
        $this->loadFixtures([LoadFeaturedProductData::class]);
        $product = $this->getProductDataBySku('product-7');
        $crawler = $this->client->request(
            'GET',
            $this->getUrl('oro_product_update', ['id' => $product->getId()])
        );
        $form    = $crawler->selectButton('Save and Close')->form();
        $this->client->followRedirects(true);
        $this->client->submit($form);

        $this->initClient(
            [],
            $this->generateBasicAuthHeader(LoadCustomerUserData::AUTH_USER, LoadCustomerUserData::AUTH_PW)
        );
        $crawler = $this->client->request('GET', $this->getUrl('oro_frontend_root'));

        $this->assertEquals(12, $crawler->filter('.featured-product')->count());
    }

    /**
     * @return int
     */
    protected function getBusinessUnitId()
    {
        return $this->getContainer()->get('oro_security.security_facade')->getLoggedUser()->getOwner()->getId();
    }

    /**
     * @param array $unitPrecisions
     * @return array
     */
    protected function sortUnitPrecisions(array $unitPrecisions)
    {
        // prices must be sort by unit and currency
        usort(
            $unitPrecisions,
            function (array $a, array $b) {
                $unitCompare = strcmp($a['unit'], $b['unit']);
                if ($unitCompare !== 0) {
                    return $unitCompare;
                }

                return strcmp($a['precision'], $b['precision']);
            }
        );

        return $unitPrecisions;
    }

    /**
     * @param string $sku
     * @return Product
     */
    private function getProductDataBySku($sku)
    {
        /** @var Product $product */
        $product = $this->getContainer()->get('doctrine')
            ->getManagerForClass('OroProductBundle:Product')
            ->getRepository('OroProductBundle:Product')
            ->findOneBy(['sku' => $sku]);
        $this->assertNotEmpty($product);

        return $product;
    }

    /**
     * @param string $name
     * @param int $precision
     * @return string
     */
    private function createPrimaryUnitPrecisionString($name, $precision)
    {
        if ($precision == 0) {
            return sprintf('%s (whole numbers)', $name);
        } elseif ($precision == 1) {
            return sprintf('%s (fractional, %d decimal digit)', $name, $precision);
        } else {
            return sprintf('%s (fractional, %d decimal digits)', $name, $precision);
        }
    }

    /**
     * @param string $code
     * @param int $precision
     * @param string $html
     */
    private function assertContainsAdditionalUnitPrecision($code, $precision, $html)
    {
        $this->assertContains(sprintf("<td>%s</td>", $code), $html);
        $this->assertContains(sprintf("<td>%d</td>", $precision), $html);
    }

    /**
     * @return Localization
     */
    protected function getLocalization()
    {
        $localization = $this->getContainer()->get('doctrine')->getManagerForClass('OroLocaleBundle:Localization')
            ->getRepository('OroLocaleBundle:Localization')
            ->findOneBy([]);

        if (!$localization) {
            throw new \LogicException('At least one localization must be defined');
        }

        return $localization;
    }

    /**
     * @param Product $product
     * @param Localization $localization
     * @return LocalizedFallbackValue
     */
    protected function getLocalizedName(Product $product, Localization $localization)
    {
        $localizedName = null;
        foreach ($product->getNames() as $name) {
            $nameLocalization = $name->getLocalization();
            if ($nameLocalization && $nameLocalization->getId() === $localization->getId()) {
                $localizedName = $name;
                break;
            }
        }

        if (!$localizedName) {
            throw new \LogicException('At least one localized name must be defined');
        }

        return $localizedName;
    }

    /**
     * @param int $productId
     * @param string $unit
     * @param string $expectedPrecision
     */
    protected function assertProductPrecision($productId, $unit, $expectedPrecision)
    {
        $productUnitPrecision = $this->getContainer()
            ->get('doctrine')
            ->getRepository('OroProductBundle:ProductUnitPrecision')
            ->findOneBy(['product' => $productId, 'unit' => $unit]);

        $this->assertEquals($expectedPrecision, $productUnitPrecision->getPrecision());
    }

    /**
     * checking if default product unit field is added and filled
     *
     * @param Form $form
     */
    protected function assertDefaultProductUnit($form)
    {
        $configManager = $this->client->getContainer()->get('oro_config.manager');
        $expectedDefaultProductUnit = $configManager->get('oro_product.default_unit');
        $expectedDefaultProductUnitPrecision = $configManager->get('oro_product.default_unit_precision');

        $formValues = $form->getValues();

        $this->assertEquals(
            $expectedDefaultProductUnit,
            $formValues['oro_product[primaryUnitPrecision][unit]']
        );
        $this->assertEquals(
            $expectedDefaultProductUnitPrecision,
            $formValues['oro_product[primaryUnitPrecision][precision]']
        );
    }

    /**
     * @param string $fileName
     * @return UploadedFile
     */
    private function createUploadedFile($fileName)
    {
        return new UploadedFile(__DIR__ . '/../DataFixtures/files/example.gif', $fileName);
    }

    /**
     * @param Crawler $crawler
     * @return array
     */
    private function parseProductImages(Crawler $crawler)
    {
        $result = [];

        $children = $crawler->filter(ProductTestHelper::IMAGES_VIEW_HEAD_SELECTOR);
        /** @var \DOMElement $child */
        foreach ($children as $child) {
            $result[0][] = $child->textContent;
        }

        $crawler->filter(ProductTestHelper::IMAGES_VIEW_BODY_SELECTOR)->each(
            function (Crawler $node) use (&$result) {
                $data = [];
                $data[] = $node->filter('a')->first()->attr(ProductTestHelper::IMAGE_FILENAME_ATTR);

                /** @var \DOMElement $child */
                foreach ($node->children()->nextAll() as $child) {
                    $icon = $child->getElementsByTagName(ProductTestHelper::IMAGE_TYPE_CHECKED_TAG)->item(0);
                    $checked = false;
                    if ($icon) {
                        $iconClass = $icon->attributes->getNamedItem('class')->nodeValue;
                        $checked = $iconClass == ProductTestHelper::IMAGE_TYPE_CHECKED_CLASS;
                    }
                    $data[] = (int) $checked;
                }
                $result[] = $data;
            }
        );

        sort($result);

        return $result;
    }

    /**
     * @param Crawler $crawler
     * @param int $position
     * @return array
     */
    protected function getActualAdditionalUnitPrecision(Crawler $crawler, $position)
    {
        return [
            'unit' => $crawler
                ->filter('select[name="oro_product[additionalUnitPrecisions][' . $position . '][unit]"] :selected')
                ->html(),
            'precision' => $crawler
                ->filter('input[name="oro_product[additionalUnitPrecisions][' . $position . '][precision]"]')
                ->extract('value')[0],
            'conversionRate' => $crawler
                ->filter('input[name="oro_product[additionalUnitPrecisions][' . $position . '][conversionRate]"]')
                ->extract('value')[0],
            'sell' => (bool)$crawler
                ->filter('input[name="oro_product[additionalUnitPrecisions][' . $position . '][sell]"]')
                ->extract('checked')[0],
        ];
    }
}
