<?php

namespace Oro\Bundle\ProductBundle\Validator\Constraints;

use Doctrine\Common\Collections\ArrayCollection;

use Symfony\Component\Validator\ConstraintValidator;
use Symfony\Component\Validator\Constraint;

use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;
use Oro\Bundle\EntityConfigBundle\Attribute\Entity\AttributeFamily;
use Oro\Bundle\EntityConfigBundle\Attribute\Entity\AttributeGroupRelation;
use Oro\Bundle\EntityConfigBundle\Manager\AttributeManager;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\EntityConfigBundle\Entity\Repository\AttributeGroupRelationRepository;

class AttributeFamilyUsageInVariantFieldValidator extends ConstraintValidator
{
    const ALIAS = 'oro_product_attribute_family_usage_in_variant_field';

    /** @var AttributeManager */
    private $attributeManager;

    /** @var DoctrineHelper */
    private $doctrineHelper;

    /**
     * @param AttributeManager $attributeManager
     * @param DoctrineHelper $doctrineHelper
     */
    public function __construct(AttributeManager $attributeManager, DoctrineHelper $doctrineHelper)
    {
        $this->attributeManager = $attributeManager;
        $this->doctrineHelper = $doctrineHelper;
    }

    /**
     * @param AttributeFamily $value
     * @param Constraint $constraint
     */
    public function validate($value, Constraint $constraint)
    {
        if (!$value instanceof AttributeFamily) {
            throw new \InvalidArgumentException(
                sprintf(
                    'Entity must be instance of "%s", "%s" given',
                    AttributeFamily::class,
                    is_object($value) ? get_class($value) : gettype($value)
                )
            );
        }

        if (!$value->getId()) {
            return;
        }

        $toDeleteIds = $this->getAttributesToDelete($value);

        if (!$toDeleteIds) {
            return;
        }

        $entityConfigNames = $this->getDeletedEntityConfigNames($toDeleteIds);
        $errors = $this->validateConfigProducts($entityConfigNames);

        if ($errors) {
            $this->context->addViolation(
                $constraint->message,
                [
                    '%products%' => implode(', ', array_unique($errors['products'])),
                    '%names%' => implode(', ', array_unique($errors['names']))
                ]
            );
        }
    }

    /**
     * @param AttributeFamily $value
     * @return array
     */
    private function getAttributesToDelete(AttributeFamily $value)
    {
        /** @var AttributeGroupRelationRepository $attributeGroupRepository */
        $attributeGroupRepository = $this->doctrineHelper->getEntityRepositoryForClass(AttributeGroupRelation::class);
        $savedAttributeRelations = $attributeGroupRepository->getAttributeGroupRelationsByFamily($value);

        $toSaveIds = new ArrayCollection();

        foreach ($value->getAttributeGroups() as $attributeGroup) {
            $attributeGroup->getAttributeRelations()->map(function (AttributeGroupRelation $relation) use ($toSaveIds) {
                $toSaveIds->add($relation->getEntityConfigFieldId());
            });
        }

        $toDeleteIds = [];

        foreach ($savedAttributeRelations as $relation) {
            if (!$toSaveIds->contains($relation->getEntityConfigFieldId())) {
                $toDeleteIds[] = $relation->getEntityConfigFieldId();
            }
        }

        return $toDeleteIds;
    }

    /**
     * @param array $toDeleteIds
     * @return array
     */
    private function getDeletedEntityConfigNames(array $toDeleteIds)
    {
        $entityConfigs = $this->attributeManager->getAttributesByIdsWithIndex($toDeleteIds);
        $entityConfigNames = [];

        foreach ($entityConfigs as $entityConfig) {
            $entityConfigNames[] = $entityConfig->getFieldName();
        }

        return $entityConfigNames;
    }

    /**
     * @param array $entityConfigNames
     * @return array
     */
    private function validateConfigProducts(array $entityConfigNames)
    {
        $productRepository = $this->doctrineHelper->getEntityRepositoryForClass(Product::class);
        $products = $productRepository->findBy(['type' => Product::TYPE_CONFIGURABLE]);

        $errors = [];

        /** @var Product $product */
        foreach ($products as $product) {
            foreach ($entityConfigNames as $name) {
                if (in_array($name, $product->getVariantFields())) {
                    $errors['products'][] = $product->getSku();
                    $errors['names'][] = $name;
                }
            }
        }

        return $errors;
    }
}
