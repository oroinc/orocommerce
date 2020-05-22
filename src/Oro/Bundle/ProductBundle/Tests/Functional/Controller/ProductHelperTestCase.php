<?php

namespace Oro\Bundle\ProductBundle\Tests\Functional\Controller;

use Oro\Bundle\LocaleBundle\Entity\Localization;
use Oro\Bundle\LocaleBundle\Entity\LocalizedFallbackValue;
use Oro\Bundle\LocaleBundle\Model\FallbackType;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\ProductBundle\Tests\Functional\Helper\ProductTestHelper;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;
use Symfony\Component\DomCrawler\Crawler;
use Symfony\Component\DomCrawler\Form;
use Symfony\Component\HttpFoundation\File\UploadedFile;

class ProductHelperTestCase extends WebTestCase
{
    /**
     * @return Crawler
     */
    protected function createProduct()
    {
        $crawler = $this->client->request('GET', $this->getUrl('oro_product_create'));
        $this->assertEquals(
            1,
            $crawler->filterXPath("//nav/a[contains(text(),'".ProductTestHelper::CATEGORY_MENU_NAME."')]")->count()
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
            $crawler->filterXPath("//nav/a[contains(text(),'".ProductTestHelper::CATEGORY_MENU_NAME."')]")->count()
        );
        static::assertStringContainsString("Category: ".ProductTestHelper::CATEGORY_NAME, $crawler->html());

        $form = $crawler->selectButton('Save and Close')->form();
        $this->assertDefaultProductUnit($form);

        $formValues = $form->getPhpValues();
        $formValues['oro_product']['sku'] = ProductTestHelper::TEST_SKU;
        $formValues['oro_product']['owner'] = $this->getBusinessUnitId();
        $formValues['oro_product']['inventory_status'] = Product::INVENTORY_STATUS_IN_STOCK;
        $formValues['oro_product']['status'] = Product::STATUS_DISABLED;
        $formValues['oro_product']['names']['values']['default'] = ProductTestHelper::DEFAULT_NAME;
        $formValues['oro_product']['descriptions']['values']['default']['wysiwyg'] =
            ProductTestHelper::DEFAULT_DESCRIPTION;
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
        static::assertStringContainsString('Product has been saved', $html);
        static::assertStringContainsString(ProductTestHelper::TEST_SKU, $html);
        static::assertStringContainsString(ProductTestHelper::INVENTORY_STATUS, $html);
        static::assertStringContainsString(ProductTestHelper::STATUS, $html);
        static::assertStringContainsString(ProductTestHelper::FIRST_UNIT_CODE, $html);

        return $crawler;
    }

    /**
     * @param array $data
     * @param Product $product
     * @param Form $form
     * @return array
     */
    protected function getSubmittedData(array $data, Product $product, Form $form)
    {
        $localization = $this->getLocalization();
        $localizedName = $this->getLocalizedName($product, $localization);

        return [
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
                'descriptions' => [
                    'values' => [
                        'default' => ['wysiwyg' => ProductTestHelper::DEFAULT_DESCRIPTION],
                        'localizations' => [
                            $localization->getId() => ['fallback' => FallbackType::SYSTEM]
                        ],
                    ],
                    'ids' => [
                        $localization->getId() => $localizedName->getId()
                    ],
                ],
                'names' => [
                    'values' => [
                        'default' => ProductTestHelper::DEFAULT_NAME_ALTERED,
                        'localizations' => [
                            $localization->getId() => ['fallback' => FallbackType::SYSTEM]
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
                ],
                'shortDescriptions' => [
                    'values' => [
                        'default' => ProductTestHelper::DEFAULT_SHORT_DESCRIPTION,
                        'localizations' => [
                            $localization->getId() => ['fallback' => FallbackType::SYSTEM]
                        ],
                    ],
                    'ids' => [
                        $localization->getId() => $localizedName->getId()
                    ],
                ],
            ]),
        ];
    }

    /**
     * @return int
     */
    protected function getBusinessUnitId()
    {
        return $this->getContainer()->get('oro_security.token_accessor')->getUser()->getOwner()->getId();
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
    protected function getProductDataBySku($sku)
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
    protected function createPrimaryUnitPrecisionString($name, $precision)
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
    protected function assertContainsAdditionalUnitPrecision($code, $precision, $html)
    {
        static::assertStringContainsString(sprintf("<td>%s</td>", $code), $html);
        static::assertStringContainsString(sprintf("<td>%d</td>", $precision), $html);
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

    /**
     * @param string $fileName
     * @return UploadedFile
     */
    protected function createUploadedFile($fileName)
    {
        return new UploadedFile(__DIR__ . '/../DataFixtures/files/example.gif', $fileName);
    }

    /**
     * @param Crawler $crawler
     * @return array
     */
    protected function parseProductImages(Crawler $crawler)
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
}
