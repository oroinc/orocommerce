<?php

namespace Oro\Bundle\ProductBundle\Tests\Functional\Controller;

use Oro\Bundle\EntityConfigBundle\Attribute\Entity\AttributeFamily;
use Oro\Bundle\FrontendTestFrameworkBundle\Migrations\Schema\OroFrontendTestFrameworkBundleInstaller;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\ProductBundle\Migrations\Data\ORM\LoadProductDefaultAttributeFamilyData;
use Oro\Bundle\ProductBundle\Tests\Functional\DataFixtures\LoadProductData;
use Oro\Bundle\ProductBundle\Tests\Functional\DataFixtures\LoadVariantFields;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;

/**
 * @dbIsolationPerTest
 */
class ConfigurableProductControllerTest extends WebTestCase
{
    const TEST_SKU_FOR_CONFIGURABLE = 'SKU-002';

    const CATEGORY_ID = 1;
    const CATEGORY_MENU_NAME = 'Master Catalog';
    const CATEGORY_NAME = 'All Products';

    const EXTENDED_FIELD_COLOR = 'color';
    const EXTENDED_FIELD_SIZE = 'size';

    /** {@inheritdoc} */
    protected function setUp(): void
    {
        $this->initClient([], $this->generateBasicAuthHeader());
        $this->client->useHashNavigation(true);
        $this->loadFixtures([LoadProductData::class, LoadVariantFields::class]);
    }

    /** {@inheritdoc} */
    protected function tearDown(): void
    {
        parent::tearDown();

        $configManager = $this->getContainer()->get('oro_entity_config.config_manager');
        $configManager->clear();
    }

    public function testIndex()
    {
        $crawler = $this->client->request('GET', $this->getUrl('oro_product_index'));
        $result = $this->client->getResponse();
        $this->assertHtmlResponseStatusCodeEquals($result, 200);
        static::assertStringContainsString('products-grid', $crawler->html());
    }

    public function testCreateConfigurableProduct()
    {
        $crawler = $this->client->request('GET', $this->getUrl('oro_product_create'));

        $this->assertEquals(
            1,
            $crawler->filterXPath("//select/option[contains(text(),'Configurable')]")->count()
        );

        /** @var AttributeFamily $defaultAttributeFamily */
        $defaultAttributeFamily = $this->getContainer()
            ->get('oro_entity.doctrine_helper')
            ->getEntityRepository(AttributeFamily::class)
            ->findOneBy(['code' => LoadProductDefaultAttributeFamilyData::DEFAULT_FAMILY_CODE]);

        $form = $crawler->selectButton('Continue')->form();
        $formValues = $form->getPhpValues();
        $formValues['input_action'] = 'oro_product_create';
        $formValues['oro_product_step_one']['category'] = self::CATEGORY_ID;
        $formValues['oro_product_step_one']['type'] = Product::TYPE_CONFIGURABLE;
        $formValues['oro_product_step_one']['attributeFamily'] = $defaultAttributeFamily->getId();

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
            $crawler->filterXPath("//li/a[contains(text(),'".self::CATEGORY_MENU_NAME."')]")->count()
        );
        static::assertStringContainsString('Category: '.self::CATEGORY_NAME, $crawler->html());

        static::assertStringContainsString(
            OroFrontendTestFrameworkBundleInstaller::VARIANT_FIELD_NAME,
            $crawler->filterXPath("//*[@data-ftid='oro_product_variantFields']")->html()
        );
    }

    public function testUpdateConfigurableProduct()
    {
        $product = $this->getReference(LoadProductData::PRODUCT_8);

        $crawler = $this->client->request('GET', $this->getUrl('oro_product_update', ['id' => $product->getId()]));
        static::assertStringContainsString(
            OroFrontendTestFrameworkBundleInstaller::VARIANT_FIELD_NAME,
            $crawler->filterXPath("//*[@data-ftid='oro_product_variantFields']")->html()
        );
    }
}
