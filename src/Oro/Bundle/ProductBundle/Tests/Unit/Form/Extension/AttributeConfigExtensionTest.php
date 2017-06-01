<?php

namespace Oro\Bundle\ProductBundle\Tests\Unit\Form\Extension;

use Symfony\Component\Form\PreloadedExtension;

use Oro\Bundle\EntityBundle\EntityConfig\DatagridScope;
use Oro\Bundle\EntityConfigBundle\Config\ConfigInterface;
use Oro\Bundle\EntityConfigBundle\Config\ConfigManager;
use Oro\Bundle\EntityConfigBundle\Config\Id\FieldConfigId;
use Oro\Bundle\EntityConfigBundle\Entity\EntityConfigModel;
use Oro\Bundle\EntityConfigBundle\Entity\FieldConfigModel;
use Oro\Bundle\EntityConfigBundle\Form\Type\ConfigType;
use Oro\Bundle\EntityConfigBundle\Provider\ConfigProvider;
use Oro\Bundle\EntityConfigBundle\Provider\PropertyConfigContainer;
use Oro\Bundle\EntityConfigBundle\Translation\ConfigTranslationHelper;
use Oro\Bundle\FormBundle\Form\Extension\DataBlockExtension;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\ProductBundle\Form\Extension\AttributeConfigExtension;
use Oro\Bundle\TranslationBundle\Translation\Translator;

use Oro\Component\Testing\Unit\EntityTrait;
use Oro\Component\Testing\Unit\FormIntegrationTestCase;

class AttributeConfigExtensionTest extends FormIntegrationTestCase
{
    use EntityTrait;

    /** @var ConfigInterface|\PHPUnit_Framework_MockObject_MockObject $datagridProvider */
    private $config;

    /**
     * @dataProvider configProvider
     *
     * @param array $datagrid
     * @param array $attribute
     * @param array $expected
     */
    public function testBuildForm(array $datagrid, array $attribute, array $expected)
    {
        $this->config->expects($this->any())
            ->method('all')
            ->willReturn(
                $attribute,
                $datagrid
            );

        $form = $this->factory->create('oro_entity_config_type', null, [
            'config_model' => $this->getEntity(FieldConfigModel::class, [
                'entity' => new EntityConfigModel(Product::class)
            ])
        ]);

        $data = $form->getData();

        $this->assertEquals($expected, $data['datagrid']);
    }

    public function testBuildFormWithExistedFieldConfigModel()
    {
        $this->config->expects($this->any())
            ->method('all')
            ->willReturn(
                ['is_attribute' => true],
                ['is_visible' => DatagridScope::IS_VISIBLE_TRUE]
            );

        $form = $this->factory->create('oro_entity_config_type', null, [
            'config_model' => $this->getEntity(FieldConfigModel::class, [
                'id' => 1,
                'entity' => new EntityConfigModel(Product::class)
            ])
        ]);

        $data = $form->getData();

        $this->assertEquals(DatagridScope::IS_VISIBLE_TRUE, $data['datagrid']['is_visible']);
    }

    public function testBuildFormWithoutDefaultValue()
    {
        $this->config->expects($this->any())
            ->method('all')
            ->willReturn(
                ['is_attribute' => true],
                []
            );

        $form = $this->factory->create('oro_entity_config_type', null, [
            'config_model' => $this->getEntity(FieldConfigModel::class, [
                'entity' => new EntityConfigModel(Product::class)
            ])
        ]);

        $data = $form->getData();

        $this->assertArrayNotHasKey('is_visible', $data['datagrid']);
    }

    public function testGetExtendedType()
    {
        $extension = new AttributeConfigExtension();
        $this->assertEquals('oro_entity_config_type', $extension->getExtendedType());
    }

    /**
     * @return array
     */
    public function configProvider()
    {
        return [
            'is attribute with default value' => [
                'datagrid' => [
                    'is_visible' => DatagridScope::IS_VISIBLE_TRUE
                ],
                'attribute' => [
                    'is_attribute' => true
                ],
                'expected datagrid' => [
                    'is_visible' => DatagridScope::IS_VISIBLE_HIDDEN
                ]
            ],
            'is attribute without default value' => [
                'datagrid' => [],
                'attribute' => [
                    'is_attribute' => true
                ],
                'expected datagrid' => []
            ]
        ];
    }

    /**
     * {@inheritdoc}
     */
    protected function getExtensions()
    {
        $this->config = $this->createMock(ConfigInterface::class);
        $this->config->expects($this->any())->method('get')->willReturn([]);

        $fieldConfigId = new FieldConfigId('extend', Product::class, 'test');

        $propertyConfig = $this->createMock(PropertyConfigContainer::class);
        $propertyConfig->expects($this->any())->method('hasForm')->willReturn(true);
        $propertyConfig->expects($this->any())->method('getFormItems')->willReturn([]);
        $propertyConfig->expects($this->any())->method('getTranslatableValues')->willReturn([]);

        /** @var ConfigProvider|\PHPUnit_Framework_MockObject_MockObject $attributeProvider */
        $attributeProvider = $this->createMock(ConfigProvider::class);
        $attributeProvider->expects($this->any())->method('getPropertyConfig')->willReturn($propertyConfig);
        $attributeProvider->expects($this->any())->method('getScope')->willReturn('attribute');
        $attributeProvider->expects($this->any())->method('getId')->willReturn($fieldConfigId);

        /** @var ConfigProvider|\PHPUnit_Framework_MockObject_MockObject $datagridProvider */
        $datagridProvider = $this->createMock(ConfigProvider::class);
        $datagridProvider->expects($this->any())->method('getPropertyConfig')->willReturn($propertyConfig);
        $datagridProvider->expects($this->any())->method('getScope')->willReturn('datagrid');
        $datagridProvider->expects($this->any())->method('getId')->willReturn($fieldConfigId);

        /** @var ConfigProvider|\PHPUnit_Framework_MockObject_MockObject $configProvider */
        $configProvider = $this->createMock(ConfigProvider::class);
        $configProvider->expects($this->any())->method('getConfigById')->willReturn($this->config);

        /** @var ConfigManager|\PHPUnit_Framework_MockObject_MockObject $configManager */
        $configManager = $this->createMock(ConfigManager::class);
        $configManager->expects($this->any())->method('getProvider')->willReturn($configProvider);
        $configManager->expects($this->any())->method('getConfig')->willReturn($this->config);
        $configManager->expects($this->any())->method('getConfigIdByModel')->willReturn($fieldConfigId);
        $configManager->expects($this->any())->method('getProviders')->willReturn([
            $attributeProvider,
            $datagridProvider
        ]);

        /** @var ConfigTranslationHelper|\PHPUnit_Framework_MockObject_MockObject $translatorHelper */
        $translatorHelper = $this->createMock(ConfigTranslationHelper::class);
        /** @var Translator|\PHPUnit_Framework_MockObject_MockObject $translator */
        $translator = $this->createMock(Translator::class);

        return [
            new PreloadedExtension(
                [
                    'oro_entity_config_type' => new ConfigType($translatorHelper, $configManager, $translator),
                ],
                [
                    'oro_entity_config_type' => [new AttributeConfigExtension()],
                    'form' => [new DataBlockExtension()]
                ]
            ),
        ];
    }
}
