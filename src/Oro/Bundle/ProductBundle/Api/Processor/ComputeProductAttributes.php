<?php

namespace Oro\Bundle\ProductBundle\Api\Processor;

use Oro\Bundle\ApiBundle\Processor\CustomizeLoadedData\CustomizeLoadedDataContext;
use Oro\Bundle\ApiBundle\Util\DoctrineHelper;
use Oro\Bundle\EntityConfigBundle\Attribute\Entity\AttributeGroup;
use Oro\Bundle\EntityConfigBundle\Config\ConfigInterface;
use Oro\Bundle\EntityConfigBundle\Config\ConfigManager;
use Oro\Bundle\EntityConfigBundle\Entity\FieldConfigModel;
use Oro\Bundle\EntityExtendBundle\EntityConfig\ExtendScope;
use Oro\Bundle\EntityExtendBundle\Extend\FieldTypeHelper;
use Oro\Bundle\EntityExtendBundle\Extend\RelationType;
use Oro\Bundle\EntityExtendBundle\Tools\ExtendHelper;
use Oro\Bundle\ProductBundle\Api\ProductAttributeValueLoader;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Component\ChainProcessor\ContextInterface;
use Oro\Component\ChainProcessor\ProcessorInterface;

/**
 * Computes a value of "productAttributes" field for each element in a collection of Product entities.
 * This processor adds all visible attributes and attributes required to choose a variant
 * for configurable products, even if such attributes are invisible.
 */
class ComputeProductAttributes implements ProcessorInterface
{
    private const FIELD_NAME = 'productAttributes';

    private DoctrineHelper $doctrineHelper;
    private ConfigManager $configManager;
    private FieldTypeHelper $fieldTypeHelper;
    private ProductAttributeValueLoader $productAttributeValueLoader;

    public function __construct(
        DoctrineHelper $doctrineHelper,
        ConfigManager $configManager,
        FieldTypeHelper $fieldTypeHelper,
        ProductAttributeValueLoader $productAttributeValueLoader
    ) {
        $this->doctrineHelper = $doctrineHelper;
        $this->configManager = $configManager;
        $this->fieldTypeHelper = $fieldTypeHelper;
        $this->productAttributeValueLoader = $productAttributeValueLoader;
    }

    /**
     * {@inheritdoc}
     */
    public function process(ContextInterface $context): void
    {
        /** @var CustomizeLoadedDataContext $context */

        $data = $context->getData();

        if ($context->isFieldRequestedForCollection(self::FIELD_NAME, $data)) {
            $context->setData($this->applyAttributes($context, $data));
        }
    }

    private function applyAttributes(CustomizeLoadedDataContext $context, array $data): array
    {
        $version = $context->getVersion();
        $requestType = $context->getRequestType();
        $productIdFieldName = $context->getResultFieldName('id');
        $productTypeFieldName = $context->getResultFieldName('type');
        $familyFieldName = $context->getResultFieldName('attributeFamily');

        $productIdsPerFamily = $this->getProductIdsPerFamily(
            $data,
            $productIdFieldName,
            $familyFieldName
        );
        $attributesPerFamily = $this->getAttributesPerFamily(
            $productIdsPerFamily,
            $data,
            $productIdFieldName,
            $productTypeFieldName
        );
        $productAttributes = $this->productAttributeValueLoader->loadAttributes(
            $productIdsPerFamily,
            $attributesPerFamily,
            $version,
            $requestType
        );

        foreach ($data as $key => $item) {
            $data[$key][self::FIELD_NAME] = $productAttributes[$item[$productIdFieldName]] ?? [];
        }

        return $data;
    }

    /**
     * @param array  $data
     * @param string $productIdFieldName
     * @param string $familyFieldName
     *
     * @return array [family id => [product id, ...], ...]
     */
    private function getProductIdsPerFamily(
        array $data,
        string $productIdFieldName,
        string $familyFieldName
    ): array {
        $result = [];
        foreach ($data as $item) {
            $familyId = $item[$familyFieldName]['id'] ?? null;
            if (null !== $familyId) {
                $result[$familyId][] = $item[$productIdFieldName];
            }
        }

        return $result;
    }

    /**
     * @param array  $productIdsPerFamily [family id => [product id, ...], ...]
     * @param array  $data
     * @param string $productIdFieldName
     * @param string $productTypeFieldName
     *
     * @return array [family id => [field name => NULL or [target field name, ...], ...], ...]
     *
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     */
    private function getAttributesPerFamily(
        array $productIdsPerFamily,
        array $data,
        string $productIdFieldName,
        string $productTypeFieldName
    ): array {
        $familyIds = array_keys($productIdsPerFamily);
        $variantFieldNamesPerFamily = $this->getVariantFieldNamesPerFamily(
            $data,
            $productIdFieldName,
            $productTypeFieldName
        );
        if (empty($variantFieldNamesPerFamily)) {
            return $this->getAttributeNamesPerFamily($familyIds, true);
        }

        $result = [];
        $attributeNames = $this->getAttributeNamesPerFamily($familyIds, false);
        foreach ($attributeNames as $familyId => $fields) {
            $visibleAttributeNames = [];
            foreach ($fields as $fieldName => [$fieldType, $visible, $targetFieldNames]) {
                if ($visible
                    || (
                        isset($variantFieldNamesPerFamily[$familyId])
                        && \in_array($fieldName, $variantFieldNamesPerFamily[$familyId], true)
                    )
                ) {
                    $visibleAttributeNames[$fieldName] = $targetFieldNames;
                }
            }
            $result[$familyId] = $visibleAttributeNames;
        }

        return $result;
    }

    /**
     * @param int[] $familyIds
     * @param bool  $visibleOnly
     *
     * @return array [family id => [field name => field data], ...], ...]
     *               The field data is NULL or [target field name, ...] if $visibleOnly is TRUE
     *               and [field type, visible, NULL or [target field name, ...]] if $visibleOnly is FALSE
     *
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     */
    private function getAttributeNamesPerFamily(array $familyIds, bool $visibleOnly): array
    {
        [$fieldIds, $attributeIds] = $this->getAttributeIdsPerFamily($familyIds, $visibleOnly);
        $selectExpr = 'f.id, f.fieldName';
        if (!$visibleOnly) {
            $selectExpr .= ', f.type AS fieldType';
        }
        $rows = $this->doctrineHelper
            ->createQueryBuilder(FieldConfigModel::class, 'f')
            ->select($selectExpr)
            ->where('f.id IN (:ids)')
            ->setParameter('ids', $fieldIds)
            ->getQuery()
            ->getArrayResult();

        $fields = [];
        foreach ($rows as $row) {
            $fieldName = $row['fieldName'];
            $extendConfig = $this->configManager->getFieldConfig('extend', Product::class, $fieldName);
            if (!$extendConfig->is('owner', ExtendScope::OWNER_CUSTOM)
                || !ExtendHelper::isFieldAccessible($extendConfig)
            ) {
                continue;
            }

            $visible = $this->configManager->getFieldConfig('frontend', Product::class, $fieldName)
                ->is('is_displayable');

            $targetFields = $this->getTargetFields($extendConfig);
            $fieldId = $row['id'];

            if (!$visibleOnly) {
                $fields[$fieldId] = [$fieldName, $row['fieldType'], $visible, $targetFields];
            } elseif ($visible) {
                $fields[$fieldId] = [$fieldName, $targetFields];
            }
        }

        $result = [];
        foreach ($attributeIds as $familyId => $fieldIds) {
            foreach ($fieldIds as $fieldId => $groupVisible) {
                if (isset($fields[$fieldId])) {
                    if ($visibleOnly) {
                        [$fieldName, $targetFieldNames] = $fields[$fieldId];
                        $result[$familyId][$fieldName] = $targetFieldNames;
                    } else {
                        [$fieldName, $fieldType, $fieldVisible, $targetFieldNames] = $fields[$fieldId];
                        $result[$familyId][$fieldName] = [
                            $fieldType,
                            $fieldVisible && $groupVisible,
                            $targetFieldNames
                        ];
                    }
                }
            }
        }

        return $result;
    }

    /**
     * @param int[] $familyIds
     * @param bool  $visibleOnly
     *
     * @return array [[field id, ...], [family id => [field id => visible, ...], ...]]
     */
    private function getAttributeIdsPerFamily(array $familyIds, bool $visibleOnly): array
    {
        $selectExpr = 'IDENTITY(g.attributeFamily) AS familyId, rel.entityConfigFieldId AS fieldId';
        if (!$visibleOnly) {
            $selectExpr .= ', g.isVisible';
        }
        $qb = $this->doctrineHelper
            ->createQueryBuilder(AttributeGroup::class, 'g')
            ->select($selectExpr)
            ->innerJoin('g.attributeRelations', 'rel')
            ->where('g.attributeFamily IN (:familyIds)')
            ->setParameter('familyIds', $familyIds);
        if ($visibleOnly) {
            $qb->andWhere('g.isVisible = :visible')->setParameter('visible', true);
        }
        $rows = $qb->getQuery()->getArrayResult();

        $fieldIds = [];
        $attributeIds = [];
        foreach ($rows as $row) {
            $familyId = (int)$row['familyId'];
            $fieldId = $row['fieldId'];
            $fieldIds[] = $fieldId;
            $attributeIds[$familyId][$fieldId] = $visibleOnly || $row['isVisible'];
        }

        return [array_unique($fieldIds), $attributeIds];
    }

    /**
     * @param ConfigInterface $extendConfig
     *
     * @return string[]|null
     */
    private function getTargetFields(ConfigInterface $extendConfig): ?array
    {
        $targetFields = null;
        $underlyingType = $this->fieldTypeHelper->getUnderlyingType($extendConfig->getId()->getFieldType());
        if (\in_array($underlyingType, RelationType::$toOneRelations, true)) {
            $targetFields = [$extendConfig->get('target_field')];
        } elseif (\in_array($underlyingType, RelationType::$toManyRelations, true)) {
            $targetFields = $extendConfig->get('target_title');
        }

        return $targetFields;
    }

    /**
     * @param array  $data
     * @param string $productIdFieldName
     * @param string $productTypeFieldName
     *
     * @return array [family id => [variant field name, ...], ...]
     */
    private function getVariantFieldNamesPerFamily(
        array $data,
        string $productIdFieldName,
        string $productTypeFieldName
    ): array {
        $productIds = [];
        foreach ($data as $item) {
            if (isset($item[$productTypeFieldName]) && Product::TYPE_SIMPLE === $item[$productTypeFieldName]) {
                $productIds[] = $item[$productIdFieldName];
            }
        }

        $variantFieldMap = [];
        if (!empty($productIds)) {
            $rows = $this->doctrineHelper
                ->createQueryBuilder(Product::class, 'p')
                ->select('IDENTITY(pp.attributeFamily) AS familyId, pp.variantFields')
                ->innerJoin('p.parentVariantLinks', 'l')
                ->innerJoin('l.parentProduct', 'pp')
                ->where('p.id IN (:ids)')
                ->setParameter('ids', $productIds)
                ->getQuery()
                ->getArrayResult();
            foreach ($rows as $row) {
                $parentVariantFields = $row['variantFields'] ?? [];
                foreach ($parentVariantFields as $variantFieldName) {
                    $parentFamilyId = (int)$row['familyId'];
                    $variantFieldMap[$variantFieldName][$parentFamilyId] = true;
                }
            }
        }

        $variantFieldNamesPerFamily = [];
        foreach ($variantFieldMap as $variantFieldName => $familyIds) {
            foreach ($familyIds as $familyId => $val) {
                $variantFieldNamesPerFamily[$familyId][] = $variantFieldName;
            }
        }

        return $variantFieldNamesPerFamily;
    }
}
