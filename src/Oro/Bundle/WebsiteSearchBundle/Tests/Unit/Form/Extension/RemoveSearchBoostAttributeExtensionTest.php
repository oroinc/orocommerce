<?php

namespace Oro\Bundle\WebsiteSearchBundle\Tests\Unit\Form\Extension;

use Oro\Bundle\EntityConfigBundle\Attribute\AttributeTypeRegistry;
use Oro\Bundle\EntityConfigBundle\Attribute\Type\AttributeTypeInterface;
use Oro\Bundle\EntityConfigBundle\Attribute\Type\BooleanAttributeType;
use Oro\Bundle\EntityConfigBundle\Attribute\Type\StringAttributeType;
use Oro\Bundle\EntityConfigBundle\Config\Config;
use Oro\Bundle\EntityConfigBundle\Config\Id\ConfigIdInterface;
use Oro\Bundle\EntityConfigBundle\Entity\EntityConfigModel;
use Oro\Bundle\EntityConfigBundle\Entity\FieldConfigModel;
use Oro\Bundle\EntityConfigBundle\Form\Type\ConfigType;
use Oro\Bundle\EntityConfigBundle\Provider\ConfigProvider;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\WebsiteSearchBundle\Form\Extension\RemoveSearchBoostAttributeExtension;
use Oro\Component\Testing\ReflectionUtil;
use PHPUnit\Framework\MockObject\Rule\InvokedCount;
use Symfony\Component\Form\FormBuilderInterface;

class RemoveSearchBoostAttributeExtensionTest extends \PHPUnit\Framework\TestCase
{
    private const FIELD_NAME = 'test_field';

    /** @var ConfigProvider|\PHPUnit\Framework\MockObject\MockObject */
    private $attributeConfigProvider;

    /** @var AttributeTypeRegistry|\PHPUnit\Framework\MockObject\MockObject */
    private $attributeTypeRegistry;

    protected function setUp(): void
    {
        $this->attributeConfigProvider = $this->createMock(ConfigProvider::class);
        $this->attributeTypeRegistry = $this->createMock(AttributeTypeRegistry::class);
    }

    public function testGetExtendedTypes(): void
    {
        self::assertEquals([ConfigType::class], RemoveSearchBoostAttributeExtension::getExtendedTypes());
    }

    /**
     * @dataProvider buildFormDataProvider
     */
    public function testBuildForm(
        InvokedCount $expected,
        object $attributeConfigModel,
        bool $formHasAttribute,
        ?AttributeTypeInterface $attributeType,
        string $searchEngine,
        array $classConfigValues = [],
        array $attributeConfigValues = []
    ): void {
        /** @var ConfigIdInterface $attributeConfigId */
        $attributeConfigId = $this->createMock(ConfigIdInterface::class);
        $attributeConfig = new Config($attributeConfigId, $attributeConfigValues);

        /** @var ConfigIdInterface $classConfigId */
        $classConfigId = $this->createMock(ConfigIdInterface::class);
        $classConfig = new Config($classConfigId, $classConfigValues);

        $this->attributeConfigProvider
            ->method('getConfig')
            ->willReturnMap([
                [Product::class, self::FIELD_NAME, $attributeConfig],
                [Product::class, null, $classConfig]
            ]);

        $this->attributeTypeRegistry
            ->method('getAttributeType')
            ->with($attributeConfigModel)
            ->willReturn($attributeType);

        /** @var FormBuilderInterface|\PHPUnit\Framework\MockObject\MockObject $builder */
        $builder = $this->createMock(FormBuilderInterface::class);

        $builder->method('has')
            ->with('attribute')
            ->willReturn($formHasAttribute);

        /** @var FormBuilderInterface|\PHPUnit\Framework\MockObject\MockObject $builder */
        $attributeBuilder = $this->createMock(FormBuilderInterface::class);

        $builder->method('get')
            ->with('attribute')
            ->willReturn($attributeBuilder);

        $attributeBuilder->expects($expected)
            ->method('remove');

        $extension = new RemoveSearchBoostAttributeExtension(
            $searchEngine,
            $this->attributeConfigProvider,
            $this->attributeTypeRegistry
        );

        $extension->buildForm($builder, ['config_model' => $attributeConfigModel]);
    }

    /**
     * @return array|array[]
     */
    public function buildFormDataProvider(): array
    {
        return [
            'supported model, form has attribute, searchable attribute type, orm, class has attributes, ' .
                'field is attribute' => [
                'expected'                => self::once(),
                'attribute config model'  => $this->getFieldConfigModel(Product::class),
                'form has attribute'      => true,
                'attribute type'          => new StringAttributeType(),
                'search engine'           => 'orm',
                'class config values'     => ['has_attributes' => true],
                'attribute config values' => ['is_attribute' => true],
            ],
            'supported model, form has attribute, searchable attribute type, orm, class has attributes, .' .
                'field is not attribute' => [
                'expected'                => self::never(),
                'attribute config model'  => $this->getFieldConfigModel(Product::class),
                'form has attribute'      => true,
                'attribute type'          => new StringAttributeType(),
                'search engine'           => 'orm',
                'class config values'     => ['has_attributes' => true],
                'attribute config values' => ['is_attribute' => false],
            ],
            'supported model, form has attribute, searchable attribute type, orm, class has no attributes, ' .
                'field is attribute' => [
                'expected'                => self::never(),
                'attribute config model'  => $this->getFieldConfigModel(Product::class),
                'form has attribute'      => true,
                'attribute type'          => new StringAttributeType(),
                'search engine'           => 'orm',
                'class config values'     => ['has_attributes' => false],
                'attribute config values' => ['is_attribute' => true],
            ],
            'supported model, form has no attribute, searchable attribute type, orm, class has attributes, ' .
                'field is attribute' => [
                'expected'                => self::never(),
                'attribute config model'  => $this->getFieldConfigModel(Product::class),
                'form has attribute'      => false,
                'attribute type'          => new StringAttributeType(),
                'search engine'           => 'orm',
                'class config values'     => ['has_attributes' => true],
                'attribute config values' => ['is_attribute' => true],
            ],
            'unsupported model, form has attribute, searchable attribute type, orm, class has attributes, ' .
                'field is attribute' => [
                'expected'                => self::never(),
                'attribute config model'  => new \stdClass(),
                'form has attribute'      => true,
                'attribute type'          => new StringAttributeType(),
                'search engine'           => 'orm',
                'class config values'     => ['has_attributes' => true],
                'attribute config values' => ['is_attribute' => true],
            ],
            'supported model, form has attribute, not searchable attribute type, elasticsearch, class has attributes,' .
                ' field is attribute' => [
                'expected'                => self::once(),
                'attribute config model'  => $this->getFieldConfigModel(Product::class),
                'form has attribute'      => true,
                'attribute type'          => new BooleanAttributeType(),
                'search engine'           => 'elastic_search',
                'class config values'     => ['has_attributes' => true],
                'attribute config values' => ['is_attribute' => true],
            ],
            'supported model, form has attribute, not attribute type, elasticsearch, class has attributes,' .
                ' field is attribute' => [
                'expected'                => self::once(),
                'attribute config model'  => $this->getFieldConfigModel(Product::class),
                'form has attribute'      => true,
                'attribute type'          => null,
                'search engine'           => 'elastic_search',
                'class config values'     => ['has_attributes' => true],
                'attribute config values' => ['is_attribute' => true],
            ],
        ];
    }

    private function getFieldConfigModel(string $className): FieldConfigModel
    {
        $entityModel = new EntityConfigModel();
        $entityModel->setClassName($className);

        $fieldModel = new FieldConfigModel();
        $fieldModel->setFieldName(self::FIELD_NAME)->setEntity($entityModel);
        ReflectionUtil::setId($fieldModel, 1);

        return $fieldModel;
    }
}
