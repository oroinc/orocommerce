<?php

namespace Oro\Bundle\ProductBundle\Tests\Unit\Form\Extension;

use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;
use Oro\Bundle\EntityConfigBundle\Config\ConfigInterface;
use Oro\Bundle\EntityConfigBundle\Config\ConfigManager;
use Oro\Bundle\EntityConfigBundle\Config\Id\FieldConfigId;
use Oro\Bundle\EntityConfigBundle\Provider\ConfigProvider;
use Oro\Bundle\EntityExtendBundle\Form\Type\EnumValueCollectionType;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\ProductBundle\Entity\Repository\ProductRepository;
use Oro\Bundle\ProductBundle\Form\Extension\EnumValueCollectionExtension;
use Oro\Component\Testing\Unit\EntityTrait;
use Symfony\Component\Form\FormConfigInterface;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormView;

class EnumValueCollectionExtensionTest extends \PHPUnit\Framework\TestCase
{
    use EntityTrait;

    const ENUM_FIELD_NAME = 'enumField';

    /** @var DoctrineHelper|\PHPUnit\Framework\MockObject\MockObject */
    private $doctrineHelper;

    /** @var EnumValueCollectionExtension */
    private $extension;

    /** @var FormInterface|\PHPUnit\Framework\MockObject\MockObject $form */
    private $form;

    /** @var FormConfigInterface|\PHPUnit\Framework\MockObject\MockObject */
    private $configInterface;

    /** @var ProductRepository|\PHPUnit\Framework\MockObject\MockObject */
    private $productRepository;

    /** @var ConfigManager|\PHPUnit\Framework\MockObject\MockObject */
    private $configManager;

    /**
     * {@inheritdoc}
     */
    protected function setUp()
    {
        $this->doctrineHelper = $this->getMockBuilder(DoctrineHelper::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->form = $this->createMock(FormInterface::class);
        $this->configInterface = $this->createMock(FormConfigInterface::class);
        $this->productRepository = $this->getMockBuilder(ProductRepository::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->configManager = $this->getMockBuilder(ConfigManager::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->extension = new EnumValueCollectionExtension($this->doctrineHelper, $this->configManager);
    }

    public function testGetExtendedType()
    {
        $this->assertEquals(EnumValueCollectionType::class, $this->extension->getExtendedType());
    }

    public function testBuildViewWhenEmptyFormData()
    {
        $view = new FormView();

        $this->form->expects($this->once())
            ->method('getData')
            ->willReturn([]);

        $this->form->expects($this->never())
            ->method('getParent');

        $this->extension->finishView($view, $this->form, []);

        $this->assertArrayNotHasKey('tooltip', $view->vars);
        $this->assertArrayNotHasKey('tooltip_parameters', $view->vars);
    }

    public function testBuildViewWhenConfigIdIsNull()
    {
        $view = new FormView();

        $this->prepareForm();

        $this->configInterface->expects($this->once())
            ->method('getOption')
            ->with('config_id')
            ->willReturn(null);

        $this->extension->finishView($view, $this->form, []);

        $this->assertArrayNotHasKey('tooltip', $view->vars);
        $this->assertArrayNotHasKey('tooltip_parameters', $view->vars);
    }

    public function testBuildViewWhenConfigIdIsNotSupportedClass()
    {
        $view = new FormView();

        $this->prepareForm();

        $this->configInterface->expects($this->once())
            ->method('getOption')
            ->with('config_id')
            ->willReturn(new \stdClass());

        $this->extension->finishView($view, $this->form, []);

        $this->assertArrayNotHasKey('tooltip', $view->vars);
        $this->assertArrayNotHasKey('tooltip_parameters', $view->vars);
    }

    public function testBuildViewForUnsupportedClass()
    {
        $view = new FormView();

        $this->prepareForm();

        $configId = new FieldConfigId('testScope', 'stdClass', 'field');

        $this->configInterface->expects($this->once())
            ->method('getOption')
            ->with('config_id')
            ->willReturn($configId);

        $this->extension->finishView($view, $this->form, []);

        $this->assertArrayNotHasKey('tooltip', $view->vars);
        $this->assertArrayNotHasKey('tooltip_parameters', $view->vars);
    }

    public function testBuildViewWhenNoEnumValuesUsedByProductVariants()
    {
        $view = new FormView();

        $this->prepareForm();

        $configId = new FieldConfigId('testScope', Product::class, 'enumFieldName', 'enum');

        $this->configInterface->expects($this->once())
            ->method('getOption')
            ->with('config_id')
            ->willReturn($configId);

        $this->doctrineHelper->expects($this->once())
            ->method('getEntityRepositoryForClass')
            ->with(Product::class)
            ->willReturn($this->productRepository);

        $this->productRepository->expects($this->once())
            ->method('findParentSkusByAttributeOptions')
            ->with(
                Product::TYPE_SIMPLE,
                'enumFieldName',
                [1]
            )
            ->willReturn([]);

        $attributeConfigProvider = $this->getAttributeProvider();
        $attributeConfig = $this->createMock(ConfigInterface::class);

        $attributeConfig->expects($this->once())
            ->method('is')
            ->with('is_attribute')
            ->willReturn(true);

        $attributeConfigProvider->expects($this->once())
            ->method('getConfig')
            ->willReturn($attributeConfig);

        $this->extension->finishView($view, $this->form, []);

        $this->assertArrayNotHasKey('tooltip', $view->vars);
        $this->assertArrayNotHasKey('tooltip_parameters', $view->vars);
    }

    public function testBuildViewWhenFieldTypeNotEnum()
    {
        $view = new FormView();

        $this->prepareForm();

        $configId = new FieldConfigId('testScope', Product::class, 'enumFieldName', 'boolean');

        $this->configInterface->expects($this->once())
            ->method('getOption')
            ->with('config_id')
            ->willReturn($configId);

        $this->doctrineHelper->expects($this->never())
            ->method('getEntityRepositoryForClass');

        $this->productRepository->expects($this->never())
            ->method('findParentSkusByAttributeOptions');

        $attributeConfigProvider = $this->getAttributeProvider();
        $attributeConfig = $this->createMock(ConfigInterface::class);

        $attributeConfig->expects($this->once())
            ->method('is')
            ->with('is_attribute')
            ->willReturn(true);

        $attributeConfigProvider->expects($this->once())
            ->method('getConfig')
            ->willReturn($attributeConfig);

        $this->extension->finishView($view, $this->form, []);

        $this->assertArrayNotHasKey('tooltip', $view->vars);
        $this->assertArrayNotHasKey('tooltip_parameters', $view->vars);
    }

    /**
     * @dataProvider buildViewProvider
     * @param array $skus
     * @param $expectedTooltip
     */
    public function testFinishView(array $skus, $expectedTooltip)
    {
        $childView = new FormView();
        $childView->vars['value']['id'] = 1;

        $view = new FormView();
        $view->children[] = $childView;

        $this->prepareForm();

        $configId = new FieldConfigId('testScope', Product::class, self::ENUM_FIELD_NAME, 'enum');
        $this->configInterface->expects($this->once())
            ->method('getOption')
            ->with('config_id')
            ->willReturn($configId);

        $this->doctrineHelper->expects($this->once())
            ->method('getEntityRepositoryForClass')
            ->with(Product::class)
            ->willReturn($this->productRepository);

        $this->productRepository->expects($this->once())
            ->method('findParentSkusByAttributeOptions')
            ->with(
                Product::TYPE_SIMPLE,
                self::ENUM_FIELD_NAME,
                [1]
            )
            ->willReturn([1 => $skus]);

        $attributeConfigProvider = $this->getAttributeProvider();
        $attributeConfig = $this->createMock(ConfigInterface::class);

        $attributeConfig->expects($this->once())
            ->method('is')
            ->with('is_attribute')
            ->willReturn(true);

        $attributeConfigProvider->expects($this->once())
            ->method('getConfig')
            ->willReturn($attributeConfig);

        $this->extension->finishView($view, $this->form, []);

        $this->assertArrayHasKey('tooltip', $childView->vars);
        $this->assertArrayHasKey('tooltip_parameters', $childView->vars);
        $this->assertEquals(
            [
                '%skuList%' => $expectedTooltip
            ],
            $childView->vars['tooltip_parameters']
        );
        $this->assertArrayHasKey('allow_delete', $childView->vars);
        $this->assertFalse($childView->vars['allow_delete']);
    }

    /**
     * @return \PHPUnit\Framework\MockObject\MockObject|ConfigProvider
     */
    private function getAttributeProvider()
    {
        $attributeConfigProvider = $this->getMockBuilder(ConfigProvider::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->configManager->expects($this->once())
            ->method('getProvider')
            ->with('attribute')
            ->willReturn($attributeConfigProvider);

        return $attributeConfigProvider;
    }

    /**
     * @return array
     */
    public function buildViewProvider()
    {
        return [
            '3 config products sku' => [
                'products' => [
                    'sku1' => 'sku1',
                    'sku2' => 'sku2',
                    'sku3' => 'sku3',
                ],
                'expectedTooltip' => 'sku1, sku2, sku3'
            ],
            '11 config products sku' => [
                'products' => [
                    'sku1' => 'sku1',
                    'sku2' => 'sku2',
                    'sku3' => 'sku3',
                    'sku4' => 'sku4',
                    'sku5' => 'sku5',
                    'sku6' => 'sku6',
                    'sku7' => 'sku7',
                    'sku8' => 'sku8',
                    'sku9' => 'sku9',
                    'sku10' => 'sku10',
                    'sku12' => 'sku12',
                ],
                'expectedTooltip' => 'sku1, sku2, sku3, sku4, sku5, sku6, sku7, sku8, sku9, sku10 ...',
            ],
        ];
    }

    private function prepareForm()
    {
        $this->form->expects($this->once())
            ->method('getData')
            ->willReturn([['id' => 1]]);

        $this->form->expects($this->once())
            ->method('getConfig')
            ->willReturn($this->configInterface);
    }
}
