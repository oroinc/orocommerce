<?php

namespace OroB2B\Bundle\ProductBundle\Tests\Functional\Controller;

use Symfony\Component\DomCrawler\Crawler;
use Symfony\Component\DomCrawler\Form;
use Symfony\Component\HttpFoundation\File\UploadedFile;

use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;

use OroB2B\Bundle\ProductBundle\Entity\Product;
use OroB2B\Bundle\ProductBundle\Entity\ProductUnitPrecision;
use OroB2B\Bundle\FallbackBundle\Entity\LocalizedFallbackValue;
use OroB2B\Bundle\FallbackBundle\Model\FallbackType;
use OroB2B\Bundle\WebsiteBundle\Entity\Locale;

/**
 * @SuppressWarnings(PHPMD.TooManyMethods)
 *
 * @dbIsolation
 */
class ProductControllerTest extends WebTestCase
{
    const TEST_SKU = 'SKU-001';
    const UPDATED_SKU = 'SKU-001-updated';
    const FIRST_DUPLICATED_SKU = 'SKU-001-updated-1';
    const SECOND_DUPLICATED_SKU = 'SKU-001-updated-2';

    const STATUS = 'Disabled';
    const UPDATED_STATUS = 'Enabled';

    const INVENTORY_STATUS = 'In Stock';
    const UPDATED_INVENTORY_STATUS = 'Out of Stock';

    const FIRST_UNIT_CODE = 'each';
    const FIRST_UNIT_FULL_NAME = 'each';
    const FIRST_UNIT_PRECISION = '0';

    const SECOND_UNIT_CODE = 'kg';
    const SECOND_UNIT_FULL_NAME = 'kilogram';
    const SECOND_UNIT_PRECISION = '1';

    const THIRD_UNIT_CODE = 'piece';
    const THIRD_UNIT_FULL_NAME = 'piece';
    const THIRD_UNIT_PRECISION = '0';

    const DEFAULT_NAME = 'default name';
    const DEFAULT_NAME_ALTERED = 'altered default name';
    const DEFAULT_DESCRIPTION = 'default description';
    const DEFAULT_SHORT_DESCRIPTION = 'default short description';

    const FIRST_IMAGE_FILENAME = 'image1.gif';
    const SECOND_IMAGE_FILENAME = 'image2.gif';

    const IMAGES_VIEW_BODY_SELECTOR = 'div.image-collection table tbody tr';
    const IMAGES_VIEW_HEAD_SELECTOR = 'div.image-collection table thead tr th';
    const IMAGE_TYPE_CHECKED_TAG = 'i';
    const IMAGE_TYPE_CHECKED_CLASS = 'icon-check';
    const IMAGE_FILENAME_ATTR = 'title';

    /**
     * @var array
     */
    private static $expectedProductImageMatrixHeaders = ['File', 'Main', 'Additional', 'Listing'];

    /**
     * {@inheritdoc}
     */
    protected function setUp()
    {
        $this->initClient([], $this->generateBasicAuthHeader());
    }

    public function testIndex()
    {
        $crawler = $this->client->request('GET', $this->getUrl('orob2b_product_index'));
        $result = $this->client->getResponse();
        $this->assertHtmlResponseStatusCodeEquals($result, 200);
        $this->assertContains('products-grid', $crawler->html());
    }

    public function testCreate()
    {
        $crawler = $this->client->request('GET', $this->getUrl('orob2b_product_create'));

        /** @var Form $form */
        $form = $crawler->selectButton('Save and Close')->form();

        $this->assertDefaultProductUnit($form);

        $formValues = $form->getPhpValues();
        $formValues['orob2b_product']['sku'] = self::TEST_SKU;
        $formValues['orob2b_product']['owner'] = $this->getBusinessUnitId();
        $formValues['orob2b_product']['inventoryStatus'] = Product::INVENTORY_STATUS_IN_STOCK;
        $formValues['orob2b_product']['status'] = Product::STATUS_DISABLED;
        $formValues['orob2b_product']['names']['values']['default'] = self::DEFAULT_NAME;
        $formValues['orob2b_product']['descriptions']['values']['default'] = self::DEFAULT_DESCRIPTION;
        $formValues['orob2b_product']['shortDescriptions']['values']['default'] = self::DEFAULT_SHORT_DESCRIPTION;
        $formValues['orob2b_product']['unitPrecisions'][] = [
            'unit' => self::FIRST_UNIT_CODE,
            'precision' => self::FIRST_UNIT_PRECISION,
        ];

        $formValues['orob2b_product']['images'][] = [
            'main' => 1,
            'additional' => 1,
            'listing' => 1
        ];

        $filesData['orob2b_product']['images'][] = [
            'image' => [
                'file' => $this->createUploadedFile(self::FIRST_IMAGE_FILENAME)
            ]
        ];

        $this->client->followRedirects(true);
        $crawler = $this->client->request($form->getMethod(), $form->getUri(), $formValues, $filesData);

        $result = $this->client->getResponse();
        $this->assertHtmlResponseStatusCodeEquals($result, 200);

        $html = $crawler->html();
        $this->assertContains('Product has been saved', $html);
        $this->assertContains(self::TEST_SKU, $html);
        $this->assertContains(self::INVENTORY_STATUS, $html);
        $this->assertContains(self::STATUS, $html);
        $this->assertContains(self::FIRST_UNIT_CODE, $html);

        $expectedProductImageMatrix = [
            self::$expectedProductImageMatrixHeaders,
            [self::FIRST_IMAGE_FILENAME, 1, 1, 1],
        ];

        $this->assertEquals($expectedProductImageMatrix, $this->parseProductImages($crawler));
    }

    /**
     * @depends testCreate
     * @return int
     */
    public function testUpdate()
    {
        $product = $this->getProductDataBySku(self::TEST_SKU);
        $id = $product->getId();
        $locale = $this->getLocale();
        $localizedName = $this->getLocalizedName($product, $locale);

        $crawler = $this->client->request('GET', $this->getUrl('orob2b_product_update', ['id' => $id]));

        /** @var Form $form */
        $form = $crawler->selectButton('Save and Close')->form();

        $submittedData = [
            'input_action' => 'save_and_stay',
            'orob2b_product' => [
                '_token' => $form['orob2b_product[_token]']->getValue(),
                'sku' => self::UPDATED_SKU,
                'owner' => $this->getBusinessUnitId(),
                'inventoryStatus' => Product::INVENTORY_STATUS_OUT_OF_STOCK,
                'status' => Product::STATUS_ENABLED,
                'unitPrecisions' => [
                    ['unit' => self::FIRST_UNIT_CODE, 'precision' => self::FIRST_UNIT_PRECISION],
                    ['unit' => self::SECOND_UNIT_CODE, 'precision' => self::SECOND_UNIT_PRECISION],
                    ['unit' => self::THIRD_UNIT_CODE, 'precision' => self::THIRD_UNIT_PRECISION]
                ],
                'names' => [
                    'values' => [
                        'default' => self::DEFAULT_NAME_ALTERED,
                        'locales' => [$locale->getId() => ['fallback' => FallbackType::SYSTEM]],
                    ],
                    'ids' => [$locale->getId() => $localizedName->getId()],
                ],
                'descriptions' => [
                    'values' => [
                        'default' => self::DEFAULT_DESCRIPTION,
                        'locales' => [$locale->getId() => ['fallback' => FallbackType::SYSTEM]],
                    ],
                    'ids' => [$locale->getId() => $localizedName->getId()],
                ],
                'shortDescriptions' => [
                    'values' => [
                        'default' => self::DEFAULT_SHORT_DESCRIPTION,
                        'locales' => [$locale->getId() => ['fallback' => FallbackType::SYSTEM]],
                    ],
                    'ids' => [$locale->getId() => $localizedName->getId()],
                ],
                'images' => [
                    0 => [
                        'main' => 1,
                        'listing' => 1
                    ],
                    1 => [
                        'additional' => 1
                    ],
                ]
            ],
        ];

        $filesData = [
            'orob2b_product' => [
                'images' => [
                    1 => [
                        'image' => [
                            'file' => $this->createUploadedFile(self::SECOND_IMAGE_FILENAME)
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
        $crawler = $this->client->request('GET', $this->getUrl('orob2b_product_update', ['id' => $id]));

        $actualUnitPrecisions = [
            [
                'unit' => $crawler->filter('select[name="orob2b_product[unitPrecisions][0][unit]"] :selected')->html(),
                'precision' => $crawler->filter('input[name="orob2b_product[unitPrecisions][0][precision]"]')
                    ->extract('value')[0],
            ],
            [
                'unit' => $crawler->filter('select[name="orob2b_product[unitPrecisions][1][unit]"] :selected')->html(),
                'precision' => $crawler->filter('input[name="orob2b_product[unitPrecisions][1][precision]"]')
                    ->extract('value')[0],
            ],
            [
                'unit' => $crawler->filter('select[name="orob2b_product[unitPrecisions][2][unit]"] :selected')->html(),
                'precision' => $crawler->filter('input[name="orob2b_product[unitPrecisions][2][precision]"]')
                    ->extract('value')[0],
            ]
        ];
        $expectedUnitPrecisions = [
            ['unit' => self::FIRST_UNIT_FULL_NAME, 'precision' => self::FIRST_UNIT_PRECISION],
            ['unit' => self::SECOND_UNIT_FULL_NAME, 'precision' => self::SECOND_UNIT_PRECISION],
            ['unit' => self::THIRD_UNIT_FULL_NAME, 'precision' => self::THIRD_UNIT_PRECISION],
        ];

        $this->assertEquals(
            $this->sortUnitPrecisions($expectedUnitPrecisions),
            $this->sortUnitPrecisions($actualUnitPrecisions)
        );

        return $id;
    }

    /**
     * @depends testUpdate
     * @param int $id
     */
    public function testView($id)
    {
        $crawler = $this->client->request('GET', $this->getUrl('orob2b_product_view', ['id' => $id]));

        $result = $this->client->getResponse();
        $this->assertHtmlResponseStatusCodeEquals($result, 200);

        $html = $crawler->html();
        $this->assertContains(
            self::UPDATED_SKU . ' - ' . self::DEFAULT_NAME_ALTERED . ' - Products - Products',
            $html
        );
        $this->assertContains(self::UPDATED_INVENTORY_STATUS, $html);
        $this->assertContains(self::UPDATED_STATUS, $html);
        $this->assertProductPrecision($id, self::FIRST_UNIT_CODE, self::FIRST_UNIT_PRECISION);
        $this->assertProductPrecision($id, self::SECOND_UNIT_CODE, self::SECOND_UNIT_PRECISION);
        $this->assertProductPrecision($id, self::THIRD_UNIT_CODE, self::THIRD_UNIT_PRECISION);

        $expectedProductImageMatrix = [
            self::$expectedProductImageMatrixHeaders,
            [self::FIRST_IMAGE_FILENAME, 1, 0, 1],
            [self::SECOND_IMAGE_FILENAME, 0, 1, 0]
        ];

        $this->assertEquals($expectedProductImageMatrix, $this->parseProductImages($crawler));
    }

    /**
     * @depends testView
     * @return int
     */
    public function testDuplicate()
    {
        $this->client->followRedirects(true);

        $crawler = $this->client->getCrawler();
        $button = $crawler->filterXPath('//a[@title="Duplicate"]');
        $this->assertEquals(1, $button->count());

        $headers = ['HTTP_X-Requested-With' => 'XMLHttpRequest'];
        $this->client->request('GET', $button->eq(0)->link()->getUri(), [], [], $headers);
        $response = $this->client->getResponse();
        $this->assertJsonResponseStatusCodeEquals($response, 200);
        $data = json_decode($response->getContent(), true);
        $this->assertArrayHasKey('redirectUrl', $data);

        $crawler = $this->client->request('GET', $data['redirectUrl']);
        $html = $crawler->html();
        $this->assertContains('Product has been duplicated', $html);
        $this->assertContains(
            self::FIRST_DUPLICATED_SKU . ' - ' . self::DEFAULT_NAME_ALTERED . ' - Products - Products',
            $html
        );
        $this->assertContains(self::UPDATED_INVENTORY_STATUS, $html);
        $this->assertContains(self::STATUS, $html);

        $this->assertContains(
            $this->createUnitPrecisionString(self::FIRST_UNIT_FULL_NAME, self::FIRST_UNIT_PRECISION),
            $html
        );
        $this->assertContains(
            $this->createUnitPrecisionString(self::SECOND_UNIT_FULL_NAME, self::SECOND_UNIT_PRECISION),
            $html
        );
        $this->assertContains(
            $this->createUnitPrecisionString(self::THIRD_UNIT_FULL_NAME, self::THIRD_UNIT_PRECISION),
            $html
        );

        $expectedProductImageMatrix = [
            self::$expectedProductImageMatrixHeaders,
            [self::FIRST_IMAGE_FILENAME, 1, 0, 1],
            [self::SECOND_IMAGE_FILENAME, 0, 1, 0]
        ];

        $this->assertEquals($expectedProductImageMatrix, $this->parseProductImages($crawler));

        $product = $this->getProductDataBySku(self::FIRST_DUPLICATED_SKU);

        return $product->getId();
    }

    /**
     * @depends testDuplicate
     *
     * @return int
     */
    public function testSaveAndDuplicate()
    {
        $product = $this->getProductDataBySku(self::FIRST_DUPLICATED_SKU);
        $id = $product->getId();
        $locale = $this->getLocale();
        $localizedName = $this->getLocalizedName($product, $locale);

        $crawler = $this->client->request('GET', $this->getUrl('orob2b_product_update', ['id' => $id]));

        /** @var Form $form */
        $form = $crawler->selectButton('Save and Close')->form();

        $submittedData = [
            'input_action' => 'save_and_duplicate',
            'orob2b_product' => [
                '_token' => $form['orob2b_product[_token]']->getValue(),
                'sku' => self::FIRST_DUPLICATED_SKU,
                'owner' => $this->getBusinessUnitId(),
                'inventoryStatus' => Product::INVENTORY_STATUS_OUT_OF_STOCK,
                'status' => Product::STATUS_ENABLED,
                'unitPrecisions' => $form->getPhpValues()['orob2b_product']['unitPrecisions'],
                'names' => [
                    'values' => [
                        'default' => self::DEFAULT_NAME_ALTERED,
                        'locales' => [$locale->getId() => ['fallback' => FallbackType::SYSTEM]],
                    ],
                    'ids' => [$locale->getId() => $localizedName->getId()],
                ],
                'descriptions' => [
                    'values' => [
                        'default' => self::DEFAULT_DESCRIPTION,
                        'locales' => [$locale->getId() => ['fallback' => FallbackType::SYSTEM]],
                    ],
                    'ids' => [$locale->getId() => $localizedName->getId()],
                ],
                'shortDescriptions' => [
                    'values' => [
                        'default' => self::DEFAULT_SHORT_DESCRIPTION,
                        'locales' => [$locale->getId() => ['fallback' => FallbackType::SYSTEM]],
                    ],
                    'ids' => [$locale->getId() => $localizedName->getId()],
                ],
                'images' => []//remove all images
            ],
        ];

        $this->client->followRedirects(true);

        $crawler = $this->client->request($form->getMethod(), $form->getUri(), $submittedData);
        $result = $this->client->getResponse();
        $this->assertHtmlResponseStatusCodeEquals($result, 200);

        $html = $crawler->html();
        $this->assertContains('Product has been saved and duplicated', $html);
        $this->assertContains(
            self::SECOND_DUPLICATED_SKU . ' - ' . self::DEFAULT_NAME_ALTERED . ' - Products - Products',
            $html
        );
        $this->assertContains(self::UPDATED_INVENTORY_STATUS, $html);
        $this->assertContains(self::STATUS, $html);

        $this->assertContains(
            $this->createUnitPrecisionString(self::FIRST_UNIT_FULL_NAME, self::FIRST_UNIT_PRECISION),
            $html
        );
        $this->assertContains(
            $this->createUnitPrecisionString(self::SECOND_UNIT_FULL_NAME, self::SECOND_UNIT_PRECISION),
            $html
        );
        $this->assertContains(
            $this->createUnitPrecisionString(self::THIRD_UNIT_FULL_NAME, self::THIRD_UNIT_PRECISION),
            $html
        );

        $this->assertEmpty($this->parseProductImages($crawler));

        $product = $this->getProductDataBySku(self::UPDATED_SKU);

        return $product->getId();
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
                    'entityId' => $id,
                    'entityClass' => $this->getContainer()->getParameter('orob2b_product.entity.product.class'),
                ]
            ),
            [],
            [],
            ['HTTP_X_REQUESTED_WITH' => 'XMLHttpRequest']
        );
        $this->assertJsonResponseStatusCodeEquals($this->client->getResponse(), 200);
        $this->assertEquals(
            [
                'success' => true,
                'message' => '',
                'messages' => [],
                'redirectUrl' => $this->getUrl('orob2b_product_index')
            ],
            json_decode($this->client->getResponse()->getContent(), true)
        );

        $this->client->request('GET', $this->getUrl('orob2b_product_view', ['id' => $id]));

        $result = $this->client->getResponse();
        $this->assertHtmlResponseStatusCodeEquals($result, 404);
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
            ->getManagerForClass('OroB2BProductBundle:Product')
            ->getRepository('OroB2BProductBundle:Product')
            ->findOneBy(['sku' => $sku]);
        $this->assertNotEmpty($product);

        return $product;
    }

    /**
     * @param string $name
     * @param int $precision
     * @return string
     */
    private function createUnitPrecisionString($name, $precision)
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
     * @return Locale
     */
    protected function getLocale()
    {
        $locale = $this->getContainer()->get('doctrine')->getManagerForClass('OroB2BWebsiteBundle:Locale')
            ->getRepository('OroB2BWebsiteBundle:Locale')
            ->findOneBy([]);

        if (!$locale) {
            throw new \LogicException('At least one locale must be defined');
        }

        return $locale;
    }

    /**
     * @param Product $product
     * @param Locale $locale
     * @return LocalizedFallbackValue
     */
    protected function getLocalizedName(Product $product, Locale $locale)
    {
        $localizedName = null;
        foreach ($product->getNames() as $name) {
            $nameLocale = $name->getLocale();
            if ($nameLocale && $nameLocale->getId() === $locale->getId()) {
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
            ->getRepository('OroB2BProductBundle:ProductUnitPrecision')
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
        $expectedDefaultProductUnit = $configManager->get('orob2b_product.default_unit');
        $expectedDefaultProductUnitPrecision = $configManager->get('orob2b_product.default_unit_precision');

        $formValues = $form->getValues();

        $this->assertEquals(
            $expectedDefaultProductUnit,
            $formValues['orob2b_product[unitPrecisions][0][unit]']
        );
        $this->assertEquals(
            $expectedDefaultProductUnitPrecision,
            $formValues['orob2b_product[unitPrecisions][0][precision]']
        );
    }

    /**
     * @param string $fileName
     * @return UploadedFile
     */
    private function createUploadedFile($fileName)
    {
        return new UploadedFile(__DIR__ . '/files/example.gif', $fileName);
    }

    /**
     * @param Crawler $crawler
     * @return array
     */
    private function parseProductImages(Crawler $crawler)
    {
        $result = [];

        $children = $crawler->filter(self::IMAGES_VIEW_HEAD_SELECTOR);
        /** @var \DOMElement $child */
        foreach ($children as $child) {
            $result[0][] = $child->textContent;
        }

        $crawler->filter(self::IMAGES_VIEW_BODY_SELECTOR)->each(
            function (Crawler $node) use (&$result) {
                $data = [];
                $data[] = $node->children()->first()->children()->first()->attr(self::IMAGE_FILENAME_ATTR);

                /** @var \DOMElement $child */
                foreach ($node->children()->nextAll() as $child) {
                    $icon = $child->getElementsByTagName(self::IMAGE_TYPE_CHECKED_TAG)->item(0);
                    $checked = false;
                    if ($icon) {
                        $iconClass = $icon->attributes->getNamedItem('class')->nodeValue;
                        $checked = $iconClass == self::IMAGE_TYPE_CHECKED_CLASS;
                    }
                    $data[] = (int) $checked;
                }
                $result[] = $data;
            }
        );

        return $result;
    }
}
