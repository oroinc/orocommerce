<?php

namespace Oro\Bundle\ProductBundle\Test\Validator\Constraints;

use Symfony\Component\Validator\Context\ExecutionContextInterface;

use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;
use Oro\Bundle\EntityConfigBundle\Attribute\Entity\AttributeFamily;
use Oro\Bundle\EntityConfigBundle\Attribute\Entity\AttributeGroup;
use Oro\Bundle\EntityConfigBundle\Attribute\Entity\AttributeGroupRelation;
use Oro\Bundle\EntityConfigBundle\Entity\FieldConfigModel;
use Oro\Bundle\EntityConfigBundle\Entity\Repository\AttributeGroupRelationRepository;
use Oro\Bundle\EntityConfigBundle\Manager\AttributeManager;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\ProductBundle\Entity\Repository\ProductRepository;
use Oro\Bundle\ProductBundle\Validator\Constraints\AttributeFamilyUsageInVariantField;
use Oro\Bundle\ProductBundle\Validator\Constraints\AttributeFamilyUsageInVariantFieldValidator;
use Oro\Component\Testing\Unit\EntityTrait;

class AttributeFamilyUsageInVariantFieldValidatorTest extends \PHPUnit_Framework_TestCase
{
    use EntityTrait;

    /** @var AttributeManager|\PHPUnit_Framework_MockObject_MockObject */
    private $attributeManager;

    /** @var DoctrineHelper|\PHPUnit_Framework_MockObject_MockObject */
    private $doctrineHelper;

    /** @var AttributeFamilyUsageInVariantFieldValidator */
    private $validator;

    /** @var ExecutionContextInterface|\PHPUnit_Framework_MockObject_MockObject */
    private $context;

    /** @var AttributeGroupRelationRepository|\PHPUnit_Framework_MockObject_MockObject */
    private $attributeRelationRepository;

    /** @var ProductRepository|\PHPUnit_Framework_MockObject_MockObject */
    private $productRepository;

    /**
     * {@inheritdoc}
     */
    protected function setUp()
    {
        $this->attributeManager = $this->getMockBuilder(AttributeManager::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->doctrineHelper = $this->getMockBuilder(DoctrineHelper::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->validator = new AttributeFamilyUsageInVariantFieldValidator(
            $this->attributeManager,
            $this->doctrineHelper
        );

        $this->context = $this->createMock(ExecutionContextInterface::class);
        $this->validator->initialize($this->context);

        $this->attributeRelationRepository = $this->getMockBuilder(AttributeGroupRelationRepository::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->productRepository = $this->getMockBuilder(ProductRepository::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->doctrineHelper->expects($this->any())
            ->method('getEntityRepositoryForClass')
            ->willReturnCallback(function ($class) {
                switch ($class) {
                    case AttributeGroupRelation::class:
                        return $this->attributeRelationRepository;
                        break;
                    case Product::class:
                        return $this->productRepository;
                        break;
                }
            });
    }

    /**
     * {@inheritdoc}
     */
    protected function tearDown()
    {
        unset(
            $this->validator,
            $this->attributeManager,
            $this->doctrineHelper,
            $this->context,
            $this->attributeRelationRepository,
            $this->productRepository
        );
    }

    //@codingStandardsIgnoreStart
    /**
     * @expectedException \InvalidArgumentException
     * @expectedExceptionMessage Entity must be instance of "Oro\Bundle\EntityConfigBundle\Attribute\Entity\AttributeFamily", "stdClass" given
     */
    //@codingStandardsIgnoreEnd
    public function testValidateUnsupportedClass()
    {
        $this->validator->validate(new \stdClass(), new AttributeFamilyUsageInVariantField());
    }

    public function testReturnWhenEmptyDeleteFields()
    {
        $attributeFamily = $this->getEntity(AttributeFamily::class, ['id' => 1]);

        $this->attributeRelationRepository->expects($this->once())
            ->method('getAttributeGroupRelationsByFamily')
            ->with($attributeFamily)
            ->willReturn([]);

        $this->context->expects($this->never())->method('addViolation');

        $this->validator->validate($attributeFamily, new AttributeFamilyUsageInVariantField());
    }

    public function testValidateWhenAttributeFamilyNew()
    {
        $attributeFamily = new AttributeFamily();

        $this->context->expects($this->never())->method('addViolation');

        $this->validator->validate($attributeFamily, new AttributeFamilyUsageInVariantField());
    }

    public function testValidateWhenAttributeRelationsDoNotChange()
    {
        $attributeFamily = $this->getEntity(AttributeFamily::class, ['id' => 1]);

        $attributeGroup = new AttributeGroup();
        $attributeGroup2 = new AttributeGroup();

        list(
            $attributeRelation,
            $attributeRelation2,
            $attributeRelation3,
            $attributeRelation4,
            $attributeRelation5
        ) = $this->getAttributeRelations();

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

        $this->context->expects($this->never())->method('addViolation');

        $this->validator->validate($attributeFamily, new AttributeFamilyUsageInVariantField());
    }

    public function testValidateOneProductUsedAttributeFieldAsVariantFiled()
    {
        list($product, $product2) = $this->getProducts();

        $attributeFamily = $this->getEntity(AttributeFamily::class, ['id' => 1]);

        list(
            $attributeRelation,
            $attributeRelation2,
            $attributeRelation3,
            $attributeRelation4,
            $attributeRelation5
        ) = $this->getAttributeRelations();

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
            ->willReturn([$this->getEntity(FieldConfigModel::class, ['fieldName' => 'color'])]);

        $this->productRepository->expects($this->once())
            ->method('findBy')
            ->with(['type' => Product::TYPE_CONFIGURABLE])
            ->willReturn([$product, $product2]);

        $this->context->expects($this->once())
            ->method('addViolation')
            ->with(
                'oro.product.attribute_family.used_in_product_variant_field.message',
                [
                    '%products%' => 'sku1',
                    '%names%' => 'color'
                ]
            );

        $this->validator->validate($attributeFamily, new AttributeFamilyUsageInVariantField());
    }

    public function testValidateTwoProductUsedAttributeFieldAsVariantFiled()
    {
        list($product, $product2) = $this->getProducts();

        $attributeFamily = $this->getEntity(AttributeFamily::class, ['id' => 1]);

        list(
            $attributeRelation,
            $attributeRelation2,
            $attributeRelation3,
            $attributeRelation4,
            $attributeRelation5
        ) = $this->getAttributeRelations();

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
            ->willReturn(
                [
                    $this->getEntity(FieldConfigModel::class, ['fieldName' => 'color']),
                    $this->getEntity(FieldConfigModel::class, ['fieldName' => 'test']),
                ]
            );

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
            ->with(['type' => Product::TYPE_CONFIGURABLE])
            ->willReturn([$product, $product2]);

        $this->context->expects($this->once())
            ->method('addViolation')
            ->with(
                'oro.product.attribute_family.used_in_product_variant_field.message',
                [
                    '%products%' => 'sku1, sku2',
                    '%names%' => 'color, test'
                ]
            );

        $this->validator->validate($attributeFamily, new AttributeFamilyUsageInVariantField());
    }

    public function testValidateWhenMovingAttributeToAnotherGroup()
    {
        $attributeFamily = $this->getEntity(AttributeFamily::class, ['id' => 1]);

        $attributeGroup = new AttributeGroup();
        $attributeGroup2 = new AttributeGroup();

        list(
            $attributeRelation,
            $attributeRelation2,
            $attributeRelation3,
            $attributeRelation4,
            $attributeRelation5
            ) = $this->getAttributeRelations();

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

        $this->context->expects($this->never())->method('addViolation');

        $this->validator->validate($attributeFamily, new AttributeFamilyUsageInVariantField());
    }

    /**
     * @return array|AttributeGroupRelation[]
     */
    private function getAttributeRelations()
    {
        $attributeRelation = $this->getEntity(AttributeGroupRelation::class, ['id' => 1, 'entityConfigFieldId' => 11]);
        $attributeRelation2 = $this->getEntity(AttributeGroupRelation::class, ['id' => 2, 'entityConfigFieldId' => 12]);
        $attributeRelation3 = $this->getEntity(AttributeGroupRelation::class, ['id' => 3, 'entityConfigFieldId' => 13]);
        $attributeRelation4 = $this->getEntity(AttributeGroupRelation::class, ['id' => 4, 'entityConfigFieldId' => 14]);
        $attributeRelation5 = $this->getEntity(AttributeGroupRelation::class, ['id' => 5, 'entityConfigFieldId' => 15]);

        return [$attributeRelation, $attributeRelation2, $attributeRelation3, $attributeRelation4, $attributeRelation5];
    }

    /**
     * @return array|Product[]
     */
    private function getProducts()
    {
        $product = $this->getEntity(Product::class, ['variantFields' => ['color', 'size'], 'sku' => 'sku1']);
        $product2 = $this->getEntity(Product::class, ['variantFields' => ['test'], 'sku' => 'sku2']);

        return [$product, $product2];
    }
}
