<?php

namespace Oro\Bundle\ProductBundle\Tests\Functional\Controller;

use Oro\Bundle\LocaleBundle\Entity\AbstractLocalizedFallbackValue;
use Oro\Bundle\LocaleBundle\Entity\Localization;
use Oro\Bundle\LocaleBundle\Model\FallbackType;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\ProductBundle\Entity\ProductUnitPrecision;
use Oro\Bundle\ProductBundle\Tests\Functional\Helper\ProductTestHelper;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;
use Symfony\Component\DomCrawler\Crawler;
use Symfony\Component\DomCrawler\Form;
use Symfony\Component\HttpFoundation\File\UploadedFile;

class ProductHelperTestCase extends WebTestCase
{
    protected function createProduct(): Crawler
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
        self::assertStringContainsString("Category: ".ProductTestHelper::CATEGORY_NAME, $crawler->html());

        $form = $crawler->selectButton('Save and Close')->form();
        $this->assertDefaultProductUnit($form);

        $formValues = $form->getPhpValues();
        $formValues['input_action'] = $crawler->selectButton('Save and Close')->attr('data-action');
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
        self::assertStringContainsString('Product has been saved', $html);
        self::assertStringContainsString(ProductTestHelper::TEST_SKU, $html);
        self::assertStringContainsString(ProductTestHelper::INVENTORY_STATUS, $html);
        self::assertStringContainsString(ProductTestHelper::STATUS, $html);
        self::assertStringContainsString(ProductTestHelper::FIRST_UNIT_CODE, $html);

        return $crawler;
    }

    protected function getSubmittedData(array $data, Product $product, Form $form): array
    {
        $localization = $this->getLocalization();
        $localizedName = $this->getLocalizedName($product, $localization);

        return [
            'input_action' => '{"route":"oro_product_update","params":{"id":"$id"}}',
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

    protected function getBusinessUnitId(): int
    {
        return $this->getContainer()->get('oro_security.token_accessor')->getUser()->getOwner()->getId();
    }

    protected function sortUnitPrecisions(array $unitPrecisions): array
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

    protected function getProductDataBySku(string $sku): Product
    {
        /** @var Product $product */
        $product = $this->getContainer()->get('doctrine')
            ->getRepository(Product::class)
            ->findOneBy(['sku' => $sku]);
        $this->assertNotEmpty($product);

        return $product;
    }

    protected function createPrimaryUnitPrecisionString(string $name, int $precision): string
    {
        if (0 === $precision) {
            return sprintf('%s (whole numbers)', $name);
        }
        if (1 === $precision) {
            return sprintf('%s (fractional, %d decimal digit)', $name, $precision);
        }

        return sprintf('%s (fractional, %d decimal digits)', $name, $precision);
    }

    protected function assertContainsAdditionalUnitPrecision(string $code, int $precision, string $html): void
    {
        self::assertStringContainsString(sprintf("<td>%s</td>", $code), $html);
        self::assertStringContainsString(sprintf("<td>%d</td>", $precision), $html);
    }

    protected function getLocalization(): Localization
    {
        $localization = $this->getContainer()->get('doctrine')
            ->getRepository(Localization::class)
            ->findOneBy([]);

        if (!$localization) {
            throw new \LogicException('At least one localization must be defined');
        }

        return $localization;
    }

    protected function getLocalizedName(Product $product, Localization $localization): AbstractLocalizedFallbackValue
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

    protected function assertProductPrecision(int $productId, string $unit, string $expectedPrecision): void
    {
        $productUnitPrecision = $this->getContainer()->get('doctrine')
            ->getRepository(ProductUnitPrecision::class)
            ->findOneBy(['product' => $productId, 'unit' => $unit]);

        $this->assertEquals($expectedPrecision, $productUnitPrecision->getPrecision());
    }

    /**
     * checking if default product unit field is added and filled
     */
    protected function assertDefaultProductUnit(Form $form): void
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

    protected function getActualAdditionalUnitPrecision(Crawler $crawler, int $position): array
    {
        return [
            'unit' => $crawler
                ->filter('select[name="oro_product[additionalUnitPrecisions][' . $position . '][unit]"] :selected')
                ->html(),
            'precision' => $crawler
                ->filter('input[name="oro_product[additionalUnitPrecisions][' . $position . '][precision]"]')
                ->extract(['value'])[0],
            'conversionRate' => $crawler
                ->filter('input[name="oro_product[additionalUnitPrecisions][' . $position . '][conversionRate]"]')
                ->extract(['value'])[0],
            'sell' => (bool)$crawler
                ->filter('input[name="oro_product[additionalUnitPrecisions][' . $position . '][sell]"]')
                ->extract(['checked'])[0],
        ];
    }

    protected function createUploadedFile(string $fileName): UploadedFile
    {
        return new UploadedFile(__DIR__ . '/../DataFixtures/files/example.gif', $fileName);
    }

    protected function parseProductImages(Crawler $crawler): array
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
                        $checked = $iconClass === ProductTestHelper::IMAGE_TYPE_CHECKED_CLASS;
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
