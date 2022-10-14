<?php

namespace Oro\Bundle\ProductBundle\Tests\Unit\Validator\Constraints;

use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;
use Oro\Bundle\EntityConfigBundle\Attribute\Entity\AttributeFamily;
use Oro\Bundle\EntityConfigBundle\Attribute\Entity\AttributeGroup;
use Oro\Bundle\EntityConfigBundle\Attribute\Entity\AttributeGroupRelation;
use Oro\Bundle\EntityConfigBundle\Config\ConfigInterface;
use Oro\Bundle\EntityConfigBundle\Config\ConfigManager;
use Oro\Bundle\EntityConfigBundle\Entity\FieldConfigModel;
use Oro\Bundle\EntityConfigBundle\Entity\Repository\AttributeGroupRelationRepository;
use Oro\Bundle\EntityConfigBundle\Layout\DataProvider\ConfigProvider;
use Oro\Bundle\EntityConfigBundle\Manager\AttributeManager;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\ProductBundle\Entity\Repository\ProductRepository;
use Oro\Bundle\ProductBundle\Validator\Constraints\AttributeFamilyUsageInVariantField;
use Oro\Bundle\ProductBundle\Validator\Constraints\AttributeFamilyUsageInVariantFieldValidator;
use Oro\Component\Testing\ReflectionUtil;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\Test\ConstraintValidatorTestCase;

class AttributeFamilyUsageInVariantFieldValidatorTest extends ConstraintValidatorTestCase
{
    /** @var AttributeManager|\PHPUnit\Framework\MockObject\MockObject */
    private $attributeManager;

    /** @var DoctrineHelper|\PHPUnit\Framework\MockObject\MockObject */
    private $doctrineHelper;

    /** @var AttributeGroupRelationRepository|\PHPUnit\Framework\MockObject\MockObject */
    private $attributeRelationRepository;

    /** @var ProductRepository|\PHPUnit\Framework\MockObject\MockObject */
    private $productRepository;

    /** @var ConfigManager|\PHPUnit\Framework\MockObject\MockObject */
    private $configManager;

    protected function setUp(): void
    {
        $this->attributeManager = $this->createMock(AttributeManager::class);
        $this->doctrineHelper = $this->createMock(DoctrineHelper::class);
        $this->configManager = $this->createMock(ConfigManager::class);
        $this->attributeRelationRepository = $this->createMock(AttributeGroupRelationRepository::class);
        $this->productRepository = $this->createMock(ProductRepository::class);

        $config = $this->createMock(ConfigInterface::class);
        $config->expects($this->any())
            ->method('get')
            ->withAnyParameters()
            ->willReturnArgument(2);

        $attributeProvider = $this->createMock(ConfigProvider::class);
        $attributeProvider->expects($this->any())
            ->method('getConfig')
            ->willReturn($config);

        $this->configManager->expects($this->any())
            ->method('getProvider')
            ->willReturn($attributeProvider);

        $this->doctrineHelper->expects($this->any())
            ->method('getEntityRepositoryForClass')
            ->willReturnCallback(function (string $class) {
                switch ($class) {
                    case AttributeGroupRelation::class:
                        return $this->attributeRelationRepository;
                    case Product::class:
                        return $this->productRepository;
                }
                throw new \LogicException(sprintf('Unexpected entity class "%s".', $class));
            });

        parent::setUp();
    }

    /**
     * {@inheritDoc}
     */
    protected function createValidator(): AttributeFamilyUsageInVariantFieldValidator
    {
        return new AttributeFamilyUsageInVariantFieldValidator(
            $this->attributeManager,
            $this->doctrineHelper,
            $this->configManager
        );
    }

    private function getAttributeFamily(int $id): AttributeFamily
    {
        $attributeFamily = new AttributeFamily();
        ReflectionUtil::setId($attributeFamily, $id);

        return $attributeFamily;
    }

    private function getAttributeGroupRelation(int $id, int $entityConfigFieldId): AttributeGroupRelation
    {
        $attributeGroupRelation = new AttributeGroupRelation();
        ReflectionUtil::setId($attributeGroupRelation, $id);
        $attributeGroupRelation->setEntityConfigFieldId($entityConfigFieldId);

        return $attributeGroupRelation;
    }

    public function testGetTargets()
    {
        $constraint = new AttributeFamilyUsageInVariantField();
        self::assertEquals(Constraint::CLASS_CONSTRAINT, $constraint->getTargets());
    }

    public function testValidateUnsupportedClass()
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage(\sprintf(
            'Entity must be instance of "%s", "stdClass" given',
            AttributeFamily::class
        ));

        $constraint = new AttributeFamilyUsageInVariantField();
        $this->validator->validate(new \stdClass(), $constraint);
    }

    public function testReturnWhenEmptyDeleteFields()
    {
        $attributeFamily = $this->getAttributeFamily(1);

        $this->attributeRelationRepository->expects($this->once())
            ->method('getAttributeGroupRelationsByFamily')
            ->with($attributeFamily)
            ->willReturn([]);

        $constraint = new AttributeFamilyUsageInVariantField();
        $this->validator->validate($attributeFamily, $constraint);

        $this->assertNoViolation();
    }

    public function testValidateWhenAttributeFamilyNew()
    {
        $attributeFamily = new AttributeFamily();

        $constraint = new AttributeFamilyUsageInVariantField();
        $this->validator->validate($attributeFamily, $constraint);

        $this->assertNoViolation();
    }

    public function testValidateWhenAttributeRelationsDoNotChange()
    {
        $attributeFamily = $this->getAttributeFamily(1);

        $attributeGroup = new AttributeGroup();
        $attributeGroup2 = new AttributeGroup();

        [
            $attributeRelation,
            $attributeRelation2,
            $attributeRelation3,
            $attributeRelation4,
            $attributeRelation5
        ] = $this->getAttributeRelations();

        $attributeGroup
            ->addAttributeRelation($attributeRelation)
            ->addAttributeRelation($attributeRelation2)
            ->addAttributeRelation($attributeRelation3);

        $attributeGroup2
            ->addAttributeRelation($attributeRelation4)
            ->addAttributeRelation($attributeRelation5);

        $attributeFamily
            ->addAttributeGroup($attributeGroup)
            ->addAttributeGroup($attributeGroup2);

        $this->attributeRelationRepository->expects($this->once())
            ->method('getAttributeGroupRelationsByFamily')
            ->with($attributeFamily)
            ->willReturn([
                $attributeRelation,
                $attributeRelation2,
                $attributeRelation3,
                $attributeRelation4,
                $attributeRelation5
            ]);

        $constraint = new AttributeFamilyUsageInVariantField();
        $this->validator->validate($attributeFamily, $constraint);

        $this->assertNoViolation();
    }

    public function testValidateOneProductUsedAttributeFieldAsVariantFiled()
    {
        [$product, $product2] = $this->getProducts();

        $attributeFamily = new AttributeFamily();
        ReflectionUtil::setId($attributeFamily, 1);

        [
            $attributeRelation,
            $attributeRelation2,
            $attributeRelation3,
            $attributeRelation4,
            $attributeRelation5
        ] = $this->getAttributeRelations();

        $attributeGroup = new AttributeGroup();
        $attributeGroup2 = new AttributeGroup();

        $attributeGroup->addAttributeRelation($attributeRelation3);

        $attributeGroup2
            ->addAttributeRelation($attributeRelation2)
            ->addAttributeRelation($attributeRelation4)
            ->addAttributeRelation($attributeRelation5);

        $attributeFamily
            ->addAttributeGroup($attributeGroup)
            ->addAttributeGroup($attributeGroup2);

        $this->attributeRelationRepository->expects($this->once())
            ->method('getAttributeGroupRelationsByFamily')
            ->with($attributeFamily)
            ->willReturn([
                $attributeRelation,
                $attributeRelation2,
                $attributeRelation3,
                $attributeRelation4,
                $attributeRelation5
            ]);

        $this->attributeManager->expects($this->once())
            ->method('getAttributesByIdsWithIndex')
            ->with([11])
            ->willReturn([new FieldConfigModel('color')]);

        $this->productRepository->expects($this->once())
            ->method('findBy')
            ->with(['type' => Product::TYPE_CONFIGURABLE, 'attributeFamily' => $attributeFamily])
            ->willReturn([$product, $product2]);

        $constraint = new AttributeFamilyUsageInVariantField();
        $this->validator->validate($attributeFamily, $constraint);

        $this->buildViolation($constraint->message)
            ->setParameters(['%products%' => 'sku1', '%names%' => 'color'])
            ->assertRaised();
    }

    public function testValidateTwoProductUsedAttributeFieldAsVariantFiled()
    {
        [$product, $product2] = $this->getProducts();

        $attributeFamily = $this->getAttributeFamily(1);

        [
            $attributeRelation,
            $attributeRelation2,
            $attributeRelation3,
            $attributeRelation4,
            $attributeRelation5
        ] = $this->getAttributeRelations();

        $attributeGroup = new AttributeGroup();
        $attributeGroup2 = new AttributeGroup();

        $attributeGroup->addAttributeRelation($attributeRelation3);

        $attributeGroup2
            ->addAttributeRelation($attributeRelation2)
            ->addAttributeRelation($attributeRelation4);

        $attributeFamily
            ->addAttributeGroup($attributeGroup)
            ->addAttributeGroup($attributeGroup2);

        $this->attributeManager->expects($this->once())
            ->method('getAttributesByIdsWithIndex')
            ->with([11, 15])
            ->willReturn([new FieldConfigModel('color'), new FieldConfigModel('test')]);

        $this->attributeRelationRepository->expects($this->once())
            ->method('getAttributeGroupRelationsByFamily')
            ->with($attributeFamily)
            ->willReturn([
                $attributeRelation,
                $attributeRelation2,
                $attributeRelation3,
                $attributeRelation4,
                $attributeRelation5
            ]);

        $this->productRepository->expects($this->once())
            ->method('findBy')
            ->with(['type' => Product::TYPE_CONFIGURABLE, 'attributeFamily' => $attributeFamily])
            ->willReturn([$product, $product2]);

        $constraint = new AttributeFamilyUsageInVariantField();
        $this->validator->validate($attributeFamily, $constraint);

        $this->buildViolation($constraint->message)
            ->setParameters(['%products%' => 'sku1, sku2', '%names%' => 'color, test'])
            ->assertRaised();
    }

    public function testValidateWhenMovingAttributeToAnotherGroup()
    {
        $attributeFamily = $this->getAttributeFamily(1);

        $attributeGroup = new AttributeGroup();
        $attributeGroup2 = new AttributeGroup();

        [
            $attributeRelation,
            $attributeRelation2,
            $attributeRelation3,
            $attributeRelation4,
            $attributeRelation5
        ] = $this->getAttributeRelations();

        $attributeGroup
            ->addAttributeRelation($attributeRelation)
            ->addAttributeRelation($attributeRelation2)
            ->addAttributeRelation($attributeRelation3);

        $attributeGroup2
            ->addAttributeRelation($attributeRelation4)
            ->addAttributeRelation($attributeRelation5);

        $attributeFamily
            ->addAttributeGroup($attributeGroup)
            ->addAttributeGroup($attributeGroup2);

        $attributeGroup->removeAttributeRelation($attributeRelation);

        $movedAttributeRelation = new AttributeGroupRelation();
        $movedAttributeRelation->setEntityConfigFieldId(11);
        $attributeGroup2->addAttributeRelation($movedAttributeRelation);

        $this->attributeRelationRepository->expects($this->once())
            ->method('getAttributeGroupRelationsByFamily')
            ->with($attributeFamily)
            ->willReturn([
                $attributeRelation,
                $attributeRelation2,
                $attributeRelation3,
                $attributeRelation4,
                $attributeRelation5
            ]);

        $constraint = new AttributeFamilyUsageInVariantField();
        $this->validator->validate($attributeFamily, $constraint);

        $this->assertNoViolation();
    }

    /**
     * @return AttributeGroupRelation[]
     */
    private function getAttributeRelations(): array
    {
        return [
            $this->getAttributeGroupRelation(1, 11),
            $this->getAttributeGroupRelation(2, 12),
            $this->getAttributeGroupRelation(3, 13),
            $this->getAttributeGroupRelation(4, 14),
            $this->getAttributeGroupRelation(5, 15)
        ];
    }

    /**
     * @return Product[]
     */
    private function getProducts(): array
    {
        $product1 = new Product();
        $product1->setSku('sku1');
        $product1->setVariantFields(['color', 'size']);

        $product2 = new Product();
        $product2->setSku('sku2');
        $product2->setVariantFields(['test']);

        return [$product1, $product2];
    }
}
