<?php

namespace Oro\Bundle\CatalogBundle\Api\Processor;

use Oro\Bundle\ApiBundle\Config\EntityDefinitionConfig;
use Oro\Bundle\ApiBundle\Processor\CustomizeLoadedData\CustomizeLoadedDataContext;
use Oro\Bundle\ApiBundle\Util\AclProtectedQueryResolver;
use Oro\Bundle\ApiBundle\Util\ConfigUtil;
use Oro\Bundle\ApiBundle\Util\DoctrineHelper;
use Oro\Bundle\CatalogBundle\Entity\Category;
use Oro\Component\ChainProcessor\ContextInterface;
use Oro\Component\ChainProcessor\ProcessorInterface;
use Oro\Component\EntitySerializer\DataAccessorInterface;
use Oro\Component\EntitySerializer\EntitySerializer;

/**
 * Computes a value of "category" field for CategoryNode entity
 * and all fields depend on this field via "property_path" option.
 */
class ComputeCategoryNodeCategory implements ProcessorInterface
{
    private const ID_FIELD = 'id';
    private const CATEGORY_FIELD = 'category';

    private EntitySerializer $entitySerializer;
    private DoctrineHelper $doctrineHelper;
    private DataAccessorInterface $dataAccessor;

    public function __construct(
        EntitySerializer $entitySerializer,
        DoctrineHelper $doctrineHelper,
        DataAccessorInterface $dataAccessor
    ) {
        $this->entitySerializer = $entitySerializer;
        $this->doctrineHelper = $doctrineHelper;
        $this->dataAccessor = $dataAccessor;
    }

    /**
     * {@inheritdoc}
     */
    public function process(ContextInterface $context): void
    {
        /** @var CustomizeLoadedDataContext $context */

        $data = $context->getData();
        $config = $context->getConfig();
        $categoryPrefix = self::CATEGORY_FIELD . ConfigUtil::PATH_DELIMITER;
        if (!$this->isCategoryFieldRequested($data, $context, $config, $categoryPrefix)) {
            return;
        }

        $categoryAssociationData = $this->loadCategoryAssociationData(
            $context->getIdentifierValues($data, self::ID_FIELD),
            $this->getCategoryConfig($config, $categoryPrefix),
            $context->getNormalizationContext()
        );

        $fields = $config->getFields();
        foreach ($data as $key => $item) {
            $category = $categoryAssociationData[$item[self::ID_FIELD]] ?? null;
            $data[$key][self::CATEGORY_FIELD] = $category;
            if ($category) {
                $categoryPrefixLength = \strlen($categoryPrefix);
                foreach ($fields as $fieldName => $field) {
                    $propertyPath = $field->getPropertyPath();
                    if ($propertyPath && str_starts_with($propertyPath, $categoryPrefix)) {
                        $categoryPropertyPath = substr($propertyPath, $categoryPrefixLength);
                        $value = null;
                        if ($this->dataAccessor->tryGetValue($category, $categoryPropertyPath, $value)) {
                            $data[$key][$fieldName] = $value;
                        }
                    }
                }
            }
        }
        $context->setData($data);
    }

    private function isCategoryFieldRequested(
        array $data,
        CustomizeLoadedDataContext $context,
        EntityDefinitionConfig $config,
        string $categoryPrefix
    ): bool {
        if ($context->isFieldRequestedForCollection(self::CATEGORY_FIELD, $data)) {
            return true;
        }

        $dependedFieldNames = $this->getFieldNamesDependOnCategory($config, $categoryPrefix);
        if (!$dependedFieldNames) {
            return false;
        }

        foreach ($data as $item) {
            foreach ($dependedFieldNames as $fieldName) {
                if ($context->isFieldRequested($fieldName, $item)) {
                    return true;
                }
            }
        }

        return false;
    }

    /**
     * @param EntityDefinitionConfig $config
     * @param string                 $categoryPrefix
     *
     * @return string[]
     */
    private function getFieldNamesDependOnCategory(EntityDefinitionConfig $config, string $categoryPrefix): array
    {
        $dependedFieldNames = [];
        $fields = $config->getFields();
        foreach ($fields as $fieldName => $field) {
            if ($field->isExcluded()) {
                continue;
            }
            $propertyPath = $field->getPropertyPath();
            if ($propertyPath && str_starts_with($propertyPath, $categoryPrefix)) {
                $dependedFieldNames[] = $fieldName;
            }
            $dependsOn = $field->getDependsOn();
            if ($dependsOn) {
                foreach ($dependsOn as $propertyPath) {
                    if (str_starts_with($propertyPath, $categoryPrefix)) {
                        $dependedFieldNames[] = $fieldName;
                        break;
                    }
                }
            }
        }
        if ($dependedFieldNames) {
            $dependedFieldNames = array_unique($dependedFieldNames);
        }

        return $dependedFieldNames;
    }

    private function getCategoryConfig(EntityDefinitionConfig $config, string $categoryPrefix): EntityDefinitionConfig
    {
        $categoryConfig = $config->getField(self::CATEGORY_FIELD)->getTargetEntity();

        // collect all category's fields and associations that should be loaded
        // due to computed node's fields require them
        $categoryDependsOn = $this->getCategoryFieldNamesToWhichOtherFieldsDependOn($config, $categoryPrefix);
        // add a fake field with "depends_on" option to the category config
        // to make sure that all required fields and associations will be loaded
        if ($categoryDependsOn) {
            $tmpDataField = $categoryConfig->addField('_nodeTmpData');
            $tmpDataField->setPropertyPath(ConfigUtil::IGNORE_PROPERTY_PATH);
            $tmpDataField->setDependsOn($categoryDependsOn);
            // for the case when it was requested to expand the category (via "include" filter)
            foreach ($categoryDependsOn as $fieldName) {
                $field = $categoryConfig->getField($fieldName);
                if (null !== $field && $field->isExcluded()) {
                    $field->setExcluded(false);
                }
            }
        }

        // as the same category entities are used to build nodes and their categories
        // we do not need to apply ACL protection to categories
        $categoryConfig->set(AclProtectedQueryResolver::SKIP_ACL_FOR_ROOT_ENTITY, true);

        return $categoryConfig;
    }

    /**
     * @param EntityDefinitionConfig $config
     * @param string                 $categoryPrefix
     *
     * @return string[]
     */
    private function getCategoryFieldNamesToWhichOtherFieldsDependOn(
        EntityDefinitionConfig $config,
        string $categoryPrefix
    ): array {
        $categoryDependsOn = [];
        $categoryPrefixLength = \strlen($categoryPrefix);
        $fields = $config->getFields();
        foreach ($fields as $field) {
            if ($field->isExcluded()) {
                continue;
            }
            $propertyPath = $field->getPropertyPath();
            if ($propertyPath && str_starts_with($propertyPath, $categoryPrefix)) {
                $categoryDependsOn[] = substr($propertyPath, $categoryPrefixLength);
            }
            $dependsOn = $field->getDependsOn();
            if ($dependsOn) {
                foreach ($dependsOn as $propertyPath) {
                    if (str_starts_with($propertyPath, $categoryPrefix)) {
                        $categoryDependsOn[] = substr($propertyPath, $categoryPrefixLength);
                    }
                }
            }
        }
        if ($categoryDependsOn) {
            $categoryDependsOn = array_unique($categoryDependsOn);
        }

        return $categoryDependsOn;
    }

    /**
     * @param array                  $ids
     * @param EntityDefinitionConfig $config
     * @param array                  $normalizationContext
     *
     * @return array [id => entity data, ...]
     */
    private function loadCategoryAssociationData(
        array $ids,
        EntityDefinitionConfig $config,
        array $normalizationContext
    ): array {
        $qb = $this->doctrineHelper
            ->createQueryBuilder(Category::class, 'e')
            ->where('e IN (:ids)')
            ->setParameter('ids', $ids);

        $rows = $this->entitySerializer->serialize($qb, $config, $normalizationContext);

        $result = [];
        $idFieldName = $this->getIdentifierFieldName($config);
        foreach ($rows as $row) {
            $result[$row[$idFieldName]] = $row;
        }

        return $result;
    }

    private function getIdentifierFieldName(EntityDefinitionConfig $config): string
    {
        $idFieldNames = $config->getIdentifierFieldNames();

        return reset($idFieldNames);
    }
}
