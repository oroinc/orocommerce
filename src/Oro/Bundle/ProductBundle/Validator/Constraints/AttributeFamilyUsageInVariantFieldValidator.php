<?php

namespace Oro\Bundle\ProductBundle\Validator\Constraints;

use Doctrine\Common\Collections\ArrayCollection;
use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;
use Oro\Bundle\EntityConfigBundle\Attribute\Entity\AttributeFamily;
use Oro\Bundle\EntityConfigBundle\Attribute\Entity\AttributeGroupRelation;
use Oro\Bundle\EntityConfigBundle\Config\ConfigManager;
use Oro\Bundle\EntityConfigBundle\Entity\Repository\AttributeGroupRelationRepository;
use Oro\Bundle\EntityConfigBundle\Manager\AttributeManager;
use Oro\Bundle\ProductBundle\Entity\Product;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;

/**
 * Validates that attributes which are going to be deleted are not used in any product belonging to the product
 * attribute family.
 */
class AttributeFamilyUsageInVariantFieldValidator extends ConstraintValidator
{
    const ALIAS = 'oro_product_attribute_family_usage_in_variant_field';

    /** @var AttributeManager */
    private $attributeManager;

    /** @var DoctrineHelper */
    private $doctrineHelper;

    /** @var ConfigManager */
    private $configManager;

    public function __construct(
        AttributeManager $attributeManager,
        DoctrineHelper $doctrineHelper,
        ConfigManager $configManager
    ) {
        $this->attributeManager = $attributeManager;
        $this->doctrineHelper = $doctrineHelper;
        $this->configManager = $configManager;
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
        $errors = $this->validateConfigProducts($entityConfigNames, $value);

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
     * @param AttributeFamily $attributeFamily
     * @return array
     */
    private function validateConfigProducts(array $entityConfigNames, AttributeFamily $attributeFamily)
    {
        $attributeConfigProvider = $this->configManager->getProvider('attribute');
        $productRepository = $this->doctrineHelper->getEntityRepositoryForClass(Product::class);
        $products = $productRepository->findBy([
            'type' => Product::TYPE_CONFIGURABLE,
            'attributeFamily' => $attributeFamily
        ]);

        $errors = [];
        /** @var Product $product */
        foreach ($products as $product) {
            $className = get_class($product);
            foreach ($entityConfigNames as $name) {
                $attributeConfig = $attributeConfigProvider->getConfig($className, $name);
                $fieldName = $attributeConfig->get('field_name', false, $name);
                if (in_array($name, $product->getVariantFields())) {
                    $errors['products'][] = $product->getSku();
                    $errors['names'][] = $fieldName;
                }
            }
        }

        return $errors;
    }
}
