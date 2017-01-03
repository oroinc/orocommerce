<?php

namespace Oro\Bundle\ProductBundle\Tests\Functional\Controller;

use Oro\Bundle\EntityConfigBundle\Config\ConfigManager;
use Oro\Bundle\EntityConfigBundle\Entity\FieldConfigModel;
use Oro\Bundle\EntityExtendBundle\EntityConfig\ExtendScope;
use Oro\Bundle\ProductBundle\Tests\Functional\DataFixtures\LoadProductData;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;

/**
 * @SuppressWarnings(PHPMD.TooManyMethods)
 *
 * @dbIsolationPerTest
 */
class ConfigurableProductControllerTest extends WebTestCase
{
    const TEST_SKU_FOR_CONFIGURABLE = 'SKU-002';

    const CATEGORY_ID = 1;
    const CATEGORY_MENU_NAME = 'Master Catalog';
    const CATEGORY_NAME = 'Products Categories';

    const EXTENDED_FIELD_COLOR = 'color';
    const EXTENDED_FIELD_SIZE = 'size';

    /** {@inheritdoc} */
    protected function setUp()
    {
        $this->initClient([], $this->generateBasicAuthHeader());
        $this->client->useHashNavigation(true);
        $this->loadFixtures([LoadProductData::class]);

        $this->createExtendedFields();
    }

    /** {@inheritdoc} */
    protected function tearDown()
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
        $this->assertContains('products-grid', $crawler->html());
    }

    public function testCreateConfigurableProduct()
    {
        $crawler = $this->client->request('GET', $this->getUrl('oro_product_create'));

        $this->assertEquals(
            1,
            $crawler->filterXPath("//select/option[contains(text(),'Configurable')]")->count()
        );

        $form = $crawler->selectButton('Continue')->form();
        $formValues = $form->getPhpValues();
        $formValues['input_action'] = 'oro_product_create';
        $formValues['oro_product_step_one']['category'] = self::CATEGORY_ID;
        $formValues['oro_product_step_one']['type'] = Product::TYPE_CONFIGURABLE;

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
        $this->assertContains('Category: '.self::CATEGORY_NAME, $crawler->html());

        $this->assertContains(
            self::EXTENDED_FIELD_COLOR,
            $crawler->filterXPath("//*[@data-ftid='oro_product_variantFields']")->html()
        );

        $this->assertContains(
            self::EXTENDED_FIELD_SIZE,
            $crawler->filterXPath("//*[@data-ftid='oro_product_variantFields']")->html()
        );
    }

    public function testUpdateConfigurableProduct()
    {
        $product = $this->getReference(LoadProductData::PRODUCT_8);

        $crawler = $this->client->request('GET', $this->getUrl('oro_product_update', ['id' => $product->getId()]));
        $this->assertContains(
            self::EXTENDED_FIELD_COLOR,
            $crawler->filterXPath("//*[@data-ftid='oro_product_variantFields']")->html()
        );

        $this->assertContains(
            self::EXTENDED_FIELD_SIZE,
            $crawler->filterXPath("//*[@data-ftid='oro_product_variantFields']")->html()
        );
    }

    /**
     * @param ConfigManager $configManager
     * @param FieldConfigModel $fieldModel
     * @param array $options
     */
    private function updateFieldConfigs(ConfigManager $configManager, FieldConfigModel $fieldModel, $options)
    {
        $className = $fieldModel->getEntity()->getClassName();
        $fieldName = $fieldModel->getFieldName();
        foreach ($options as $scope => $scopeValues) {
            $configProvider = $configManager->getProvider($scope);
            $config = $configProvider->getConfig($className, $fieldName);
            $hasChanges = false;
            foreach ($scopeValues as $code => $val) {
                if (!$config->is($code, $val)) {
                    $config->set($code, $val);
                    $hasChanges = true;
                }
            }
            if ($hasChanges) {
                $configManager->persist($config);
                $indexedValues = $configProvider->getPropertyConfig()->getIndexedValues($config->getId());
                $fieldModel->fromArray($config->getId()->getScope(), $config->all(), $indexedValues);
            }
        }
    }

    private function createExtendedFields()
    {
        $configManager = $this->getContainer()->get('oro_entity_config.config_manager');

        $this->createExtendedField($configManager, self::EXTENDED_FIELD_COLOR, 'boolean');
        $this->createExtendedField($configManager, self::EXTENDED_FIELD_SIZE, 'boolean');

        $configManager->getEntityManager()->flush();
        $configManager->clear();
    }

    /**
     * @param ConfigManager $configManager
     * @param string $fieldName
     * @param string $fieldType
     */
    private function createExtendedField(ConfigManager $configManager, $fieldName, $fieldType)
    {
        $field = $configManager->createConfigFieldModel(Product::class, $fieldName, $fieldType);
        $options = [
            'extend' => [
                'owner' => ExtendScope::OWNER_CUSTOM,
                'is_extend' => true
            ],
            'entity' => [
                'label' => $fieldName
            ]
        ];
        $field->setCreated(new \DateTime());

        $this->updateFieldConfigs($configManager, $field, $options);
        $configManager->getEntityManager()->persist($field);
    }
}
