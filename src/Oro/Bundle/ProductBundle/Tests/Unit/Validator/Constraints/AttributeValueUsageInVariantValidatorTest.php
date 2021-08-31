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
use PHPUnit\Framework\MockObject\MockObject;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\Constraints\NotNull;
use Symfony\Component\Validator\Test\ConstraintValidatorTestCase;

class AttributeValueUsageInVariantValidatorTest extends ConstraintValidatorTestCase
{
    /**
     * @var ManagerRegistry|MockObject
     */
    private $registry;

    /**
     * @var EnumSynchronizer|MockObject
     */
    private $enumSynchronizer;

    /**
     * {@inheritDoc}
     */
    protected function createValidator()
    {
        $this->registry = $this->createMock(ManagerRegistry::class);
        $this->enumSynchronizer = $this->createMock(EnumSynchronizer::class);

        return new AttributeValueUsageInVariantValidator(
            $this->registry,
            $this->enumSynchronizer
        );
    }

    /**
     * {@inheritdoc}
     */
    protected function createContext()
    {
        $this->constraint = new AttributeValueUsageInVariant();
        $this->constraint->configModel = $this->createConfigModel();

        return parent::createContext();
    }

    public function testValidateEmptyValue()
    {
        $this->registry->expects($this->never())
            ->method($this->anything());
        $this->enumSynchronizer->expects($this->never())
            ->method($this->anything());

        $this->validator->validate(null, $this->constraint);
        $this->assertEmpty($this->context->getViolations());
    }

    /**
     * @dataProvider constraintDataProvider
     */
    public function testValidateWithUnsupportedConstraint(Constraint $constraint)
    {
        $this->registry->expects($this->never())
            ->method($this->anything());
        $this->enumSynchronizer->expects($this->never())
            ->method($this->anything());

        $this->validator->validate(null, $constraint);
        $this->assertEmpty($this->context->getViolations());
    }

    public function constraintDataProvider(): \Generator
    {
        yield 'unsupported constraint' => [new NotNull()];

        $constraint = new AttributeValueUsageInVariant();
        $constraint->configModel = $this->createMock(ConfigModel::class);
        yield 'unsupported config model' => [$constraint];

        $constraint = new AttributeValueUsageInVariant();
        $constraint->configModel = $this->createConfigModel(ProductVariantLink::class, Product::class);
        yield 'unsupported class name' => [$constraint];

        $constraint = new AttributeValueUsageInVariant();
        $constraint->configModel = $this->createConfigModel(Product::class, Product::class);
        yield 'unsupported target entity' => [$constraint];
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

    /**
     * @dataProvider allowedValuesDataProvider
     */
    public function testValidateNoRemovedItems(array $values)
    {
        $persistedOptions = [
            new TestEnumValue('test1', 'test1'),
            new TestEnumValue('test2', 'test2')
        ];

        /** @var EnumValueRepository|MockObject $enumRepo */
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

        $this->validator->validate($values, $this->constraint);
        $this->assertEmpty($this->context->getViolations());
    }

    public function allowedValuesDataProvider(): \Generator
    {
        yield [
            [
                ['id' => 'test1', 'label' => 'test1'],
                ['id' => 'test2', 'label' => 'test2']
            ]
        ];

        yield [
            [
                ['id' => 'test1', 'label' => 'test1'],
                ['id' => 'test2', 'label' => 'test2'],
                ['id' => 'test3', 'label' => 'test3'],
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

        /** @var EnumValueRepository|MockObject $enumRepo */
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

        $this->validator->validate($values, $this->constraint);
        $this->assertEmpty($this->context->getViolations());
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

        /** @var EnumValueRepository|MockObject $enumRepo */
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

        $this->validator->validate($values, $this->constraint);

        $this->buildViolation($this->constraint->message)
            ->setParameters([
                '%productSkus%' => 'SKU1',
                '%optionLabels%' => 'test1'
            ])
            ->assertRaised();
    }
}
