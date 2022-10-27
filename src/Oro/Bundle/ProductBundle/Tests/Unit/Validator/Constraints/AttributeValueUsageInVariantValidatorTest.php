<?php

namespace Oro\Bundle\ProductBundle\Tests\Unit\Validator\Constraints;

use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\EntityConfigBundle\Entity\ConfigModel;
use Oro\Bundle\EntityConfigBundle\Entity\EntityConfigModel;
use Oro\Bundle\EntityConfigBundle\Entity\FieldConfigModel;
use Oro\Bundle\EntityExtendBundle\Entity\AbstractEnumValue;
use Oro\Bundle\EntityExtendBundle\Entity\Repository\EnumValueRepository;
use Oro\Bundle\EntityExtendBundle\Tests\Unit\Fixtures\TestEnumValue;
use Oro\Bundle\EntityExtendBundle\Tools\EnumSynchronizer;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\ProductBundle\Entity\ProductVariantLink;
use Oro\Bundle\ProductBundle\Entity\Repository\ProductRepository;
use Oro\Bundle\ProductBundle\Validator\Constraints\AttributeValueUsageInVariant;
use Oro\Bundle\ProductBundle\Validator\Constraints\AttributeValueUsageInVariantValidator;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\Test\ConstraintValidatorTestCase;

class AttributeValueUsageInVariantValidatorTest extends ConstraintValidatorTestCase
{
    /** @var ManagerRegistry|\PHPUnit\Framework\MockObject\MockObject */
    private $registry;

    /** @var EnumSynchronizer|\PHPUnit\Framework\MockObject\MockObject */
    private $enumSynchronizer;

    protected function setUp(): void
    {
        $this->registry = $this->createMock(ManagerRegistry::class);
        $this->enumSynchronizer = $this->createMock(EnumSynchronizer::class);
        parent::setUp();
    }

    protected function createValidator(): AttributeValueUsageInVariantValidator
    {
        return new AttributeValueUsageInVariantValidator(
            $this->registry,
            $this->enumSynchronizer
        );
    }

    private function createConstraint(): AttributeValueUsageInVariant
    {
        $constraint = new AttributeValueUsageInVariant();
        $constraint->configModel = $this->createConfigModel();

        return $constraint;
    }

    private function createConfigModel(
        string $className = Product::class,
        string $targetClass = AbstractEnumValue::class
    ): FieldConfigModel {
        $entityConfig = new EntityConfigModel();
        $entityConfig->setClassName($className);
        $fieldConfig = new FieldConfigModel();
        $fieldConfig->setEntity($entityConfig);
        $fieldConfig->setFieldName('test_field');
        $fieldConfig->fromArray('extend', ['target_entity' => $targetClass]);

        return $fieldConfig;
    }

    public function testValidateEmptyValue()
    {
        $this->registry->expects($this->never())
            ->method($this->anything());
        $this->enumSynchronizer->expects($this->never())
            ->method($this->anything());

        $constraint = $this->createConstraint();
        $this->validator->validate(null, $constraint);
        $this->assertNoViolation();
    }

    public function testValidateWithUnsupportedConstraint()
    {
        $this->registry->expects($this->never())
            ->method($this->anything());
        $this->enumSynchronizer->expects($this->never())
            ->method($this->anything());

        $constraint = $this->createMock(Constraint::class);
        $this->validator->validate(null, $constraint);
        $this->assertNoViolation();
    }

    /**
     * @dataProvider unsupportedAttributeValueUsageInVariantConstraintDataProvider
     */
    public function testValidateWithUnsupportedAttributeValueUsageInVariantConstraint(ConfigModel $configModel)
    {
        $this->registry->expects($this->never())
            ->method($this->anything());
        $this->enumSynchronizer->expects($this->never())
            ->method($this->anything());

        $constraint = new AttributeValueUsageInVariant();
        $constraint->configModel = $configModel;
        $this->validator->validate(null, $constraint);
        $this->assertNoViolation();
    }

    public function unsupportedAttributeValueUsageInVariantConstraintDataProvider(): array
    {
        return [
            'unsupported config model' => [$this->createMock(ConfigModel::class)],
            'unsupported class name' => [$this->createConfigModel(ProductVariantLink::class, Product::class)],
            'unsupported target entity' => [$this->createConfigModel(Product::class, Product::class)],
        ];
    }

    /**
     * @dataProvider allowedValuesDataProvider
     */
    public function testValidateNoRemovedItems(array $values)
    {
        $persistedOptions = [
            new TestEnumValue('test1', 'test1'),
            new TestEnumValue('test2', 'test2')
        ];

        $enumRepo = $this->createMock(EnumValueRepository::class);
        $enumRepo->expects($this->any())
            ->method('findAll')
            ->willReturn($persistedOptions);

        $productRepo = $this->createMock(ProductRepository::class);

        $this->registry->expects($this->any())
            ->method('getRepository')
            ->willReturnMap([
                [AbstractEnumValue::class, null, $enumRepo],
                [Product::class, null, $productRepo]
            ]);

        $constraint = $this->createConstraint();
        $this->validator->validate($values, $constraint);
        $this->assertNoViolation();
    }

    public function allowedValuesDataProvider(): array
    {
        return [
            [
                [
                    ['id' => 'test1', 'label' => 'test1'],
                    ['id' => 'test2', 'label' => 'test2']
                ]
            ],
            [
                [
                    ['id' => 'test1', 'label' => 'test1'],
                    ['id' => 'test2', 'label' => 'test2'],
                    ['id' => 'test3', 'label' => 'test3'],
                ]
            ]
        ];
    }

    public function testValidateRemovedItemsThatAreNotUsedInVariant()
    {
        $persistedOptions = [
            new TestEnumValue('test1', 'test1'),
            new TestEnumValue('test2', 'test2')
        ];
        $values = [
            ['id' => 'test2', 'label' => 'test2']
        ];

        $enumRepo = $this->createMock(EnumValueRepository::class);
        $enumRepo->expects($this->any())
            ->method('findAll')
            ->willReturn($persistedOptions);

        $productRepo = $this->createMock(ProductRepository::class);
        $productRepo->expects($this->once())
            ->method('findParentSkusByAttributeOptions')
            ->with(Product::TYPE_SIMPLE, 'test_field', ['test1'])
            ->willReturn([]);

        $this->registry->expects($this->any())
            ->method('getRepository')
            ->willReturnMap([
                [AbstractEnumValue::class, null, $enumRepo],
                [Product::class, null, $productRepo]
            ]);

        $constraint = $this->createConstraint();
        $this->validator->validate($values, $constraint);
        $this->assertNoViolation();
    }

    public function testValidateRemovedItemsThatAreUsedInVariant()
    {
        $persistedOptions = [
            new TestEnumValue('test1', 'test1'),
            new TestEnumValue('test2', 'test2')
        ];
        $values = [
            ['id' => 'test2', 'label' => 'test2']
        ];

        $enumRepo = $this->createMock(EnumValueRepository::class);
        $enumRepo->expects($this->any())
            ->method('findAll')
            ->willReturn($persistedOptions);

        $productRepo = $this->createMock(ProductRepository::class);
        $productRepo->expects($this->once())
            ->method('findParentSkusByAttributeOptions')
            ->with(Product::TYPE_SIMPLE, 'test_field', ['test1'])
            ->willReturn(['test1' => ['SKU1']]);

        $this->registry->expects($this->any())
            ->method('getRepository')
            ->willReturnMap([
                [AbstractEnumValue::class, null, $enumRepo],
                [Product::class, null, $productRepo]
            ]);

        $constraint = $this->createConstraint();
        $this->validator->validate($values, $constraint);

        $this->buildViolation($constraint->message)
            ->setParameters([
                '%productSkus%' => 'SKU1',
                '%optionLabels%' => 'test1'
            ])
            ->assertRaised();
    }
}
