<?php

namespace Oro\Bundle\InventoryBundle\Tests\Functional\Controller;

use Symfony\Component\DomCrawler\Crawler;
use Symfony\Component\DomCrawler\Form;

use Oro\Bundle\CatalogBundle\Tests\Functional\DataFixtures\LoadCategoryProductData;
use Oro\Bundle\EntityBundle\Tests\Functional\Helper\FallbackTestTrait;
use Oro\Bundle\InventoryBundle\Entity\InventoryLevel;
use Oro\Bundle\LocaleBundle\Entity\Localization;
use Oro\Bundle\LocaleBundle\Entity\LocalizedFallbackValue;
use Oro\Bundle\LocaleBundle\Model\FallbackType;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\ProductBundle\Tests\Functional\Helper\ProductTestHelper;
use Oro\Bundle\ProductBundle\Tests\Functional\DataFixtures\LoadProductData;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;

class ProductControllerTest extends WebTestCase
{
    use FallbackTestTrait;

    public function setUp()
    {
        $this->initClient([], $this->generateBasicAuthHeader());
        $this->client->useHashNavigation(true);
        $this->loadFixtures([LoadCategoryProductData::class]);
    }

    public function testAddQuantityToOrder()
    {
        $productId = $this->getReference(LoadProductData::PRODUCT_1)->getId();
        $this->updateProduct($productId, '123', '321', null, null);
        $crawler = $this->client->request('GET', $this->getUrl('oro_product_view', ['id' => $productId]));
        $this->assertEquals('123', $this->getMinValue($crawler));
        $this->assertEquals('321', $this->getMaxValue($crawler));
    }

    public function testFallbackQuantity()
    {
        $productId = $this->getReference(LoadProductData::PRODUCT_1)->getId();
        $crawler = $this->client->request('GET', $this->getUrl('oro_product_view', ['id' => $productId]));
        $originalMinValue = $this->getMinValue($crawler);
        $originalMaxValue = $this->getMaxValue($crawler);
        $this->updateProduct($productId, null, null, 'systemConfig', 'category');

        $crawler = $this->client->request('GET', $this->getUrl('oro_product_view', ['id' => $productId]));
        $this->assertNotEquals($originalMinValue, $this->getMinValue($crawler));
        $this->assertNotEquals($originalMaxValue, $this->getMaxValue($crawler));
    }

    public function testCreateInventoryLevels()
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

        $businessUnitId = $this->getContainer()
            ->get('oro_security.security_facade')
            ->getLoggedUser()
            ->getOwner()
            ->getId();
        $form = $crawler->selectButton('Save and Close')->form();

        $formValues = $form->getPhpValues();
        $formValues['oro_product']['sku'] = ProductTestHelper::TEST_SKU;
        $formValues['oro_product']['owner'] = $businessUnitId;
        $formValues['oro_product']['inventory_status'] = Product::INVENTORY_STATUS_IN_STOCK;
        $formValues['oro_product']['status'] = Product::STATUS_DISABLED;
        $formValues['oro_product']['names']['values']['default'] = ProductTestHelper::DEFAULT_NAME;
        $formValues['oro_product']['descriptions']['values']['default'] = ProductTestHelper::DEFAULT_DESCRIPTION;
        $formValues['oro_product']['shortDescriptions']['values']['default'] = ProductTestHelper::DEFAULT_SHORT_DESCRIPTION;
        $formValues['oro_product']['type'] = Product::TYPE_SIMPLE;
        $formValues['oro_product']['primaryUnitPrecision'] = [
            'unit' => ProductTestHelper::FIRST_UNIT_CODE,
            'precision' => ProductTestHelper::FIRST_UNIT_PRECISION,
        ];
        $formValues['oro_product']['additionalUnitPrecisions'][] = [
            'unit' => ProductTestHelper::SECOND_UNIT_CODE,
            'precision' => ProductTestHelper::SECOND_UNIT_PRECISION,
            'conversionRate' => 10,
            'sell' => true,
        ];

        $this->client->followRedirects(true);
        $crawler = $this->client->request($form->getMethod(), $form->getUri(), $formValues);

        $result = $this->client->getResponse();
        $this->assertHtmlResponseStatusCodeEquals($result, 200);

        $html = $crawler->html();
        $this->assertContains('Product has been saved', $html);
        $this->assertContains(ProductTestHelper::TEST_SKU, $html);
        $this->assertContains(ProductTestHelper::INVENTORY_STATUS, $html);
        $this->assertContains(ProductTestHelper::STATUS, $html);
        $this->assertContains(ProductTestHelper::FIRST_UNIT_CODE, $html);
        $this->assertInventoryLevelsCreated();
    }

    /**
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     *
     * @depends testCreateInventoryLevels
     */
    public function testAddAdditionalUnit()
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
        $businessUnitId = $this->getContainer()
            ->get('oro_security.security_facade')
            ->getLoggedUser()
            ->getOwner()
            ->getId();
        /** @var Form $form */
        $form = $crawler->selectButton('Save and Close')->form();

        $data = $form->getPhpValues()['oro_product'];
        $submittedData = [
            'input_action' => 'save_and_stay',
            'oro_product' => array_merge($data, [
                '_token' => $form['oro_product[_token]']->getValue(),
                'sku' => ProductTestHelper::UPDATED_SKU,
                'owner' => $businessUnitId,
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
                        'localizations' => [$localization->getId() => ['fallback' => FallbackType::SYSTEM]],
                    ],
                    'ids' => [$localization->getId() => $localizedName->getId()],
                ],
                'descriptions' => [
                    'values' => [
                        'default' => ProductTestHelper::DEFAULT_DESCRIPTION,
                        'localizations' => [$localization->getId() => ['fallback' => FallbackType::SYSTEM]],
                    ],
                    'ids' => [$localization->getId() => $localizedName->getId()],
                ],
                'shortDescriptions' => [
                    'values' => [
                        'default' => ProductTestHelper::DEFAULT_SHORT_DESCRIPTION,
                        'localizations' => [$localization->getId() => ['fallback' => FallbackType::SYSTEM]],
                    ],
                    'ids' => [$localization->getId() => $localizedName->getId()],
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

        $this->client->followRedirects(true);
        $this->client->request($form->getMethod(), $form->getUri(), $submittedData);
        $result = $this->client->getResponse();
        $this->assertHtmlResponseStatusCodeEquals($result, 200);

        // Check product unit precisions
        $crawler = $this->client->request('GET', $this->getUrl('oro_product_update', ['id' => $id]));
        $this->assertInventoryLevelsCreated();
    }

    /**
     * check if inventory levels are created after updating/creating product
     */
    protected function assertInventoryLevelsCreated()
    {
        $product = $this->getContainer()
            ->get('oro_entity.doctrine_helper')
            ->getEntityManagerForClass(Product::class)
            ->getRepository(Product::class)
            ->findOneBy(['sku' => ProductTestHelper::TEST_SKU]);
        $inventoryLevels = $this->getContainer()
            ->get('oro_entity.doctrine_helper')
            ->getEntityManagerForClass(InventoryLevel::class)
            ->getRepository(InventoryLevel::class)
            ->findBy(['product' => $product]);
        $this->assertEquals($product->getUnitPrecisions()->count(), count($inventoryLevels));
    }

    /**
     * @param Crawler $crawler
     * @return string
     */
    protected function getMinValue(Crawler $crawler)
    {
        return $crawler->filterXPath(
            '//label[text()=\'Minimum Quantity To Order\']/following-sibling::div/div'
        )->html();
    }

    /**
     * @param Crawler $crawler
     * @return string
     */
    protected function getMaxValue(Crawler $crawler)
    {
        return $crawler->filterXPath(
            '//label[text()=\'Maximum Quantity To Order\']/following-sibling::div/div'
        )->html();
    }

    /**
     * @param integer $productId
     * @param mixed $minScalar
     * @param mixed $maxScalar
     * @param string $minFallback
     * @param string $maxFallback
     */
    protected function updateProduct($productId, $minScalar, $maxScalar, $minFallback, $maxFallback)
    {
        $crawler = $this->client->request('GET', $this->getUrl('oro_product_update', ['id' => $productId]));
        $form = $crawler->selectButton('Save and Close')->form();
        $this->updateFallbackField($form, $minScalar, $minFallback, 'oro_product', 'minimumQuantityToOrder');
        $this->updateFallbackField($form, $maxScalar, $maxFallback, 'oro_product', 'maximumQuantityToOrder');

        $this->client->submit($form);
        $this->client->followRedirects();
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
}
