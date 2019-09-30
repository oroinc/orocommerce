<?php

namespace Oro\Bundle\ProductBundle\Api\Processor;

use Oro\Bundle\ApiBundle\Config\EntityDefinitionConfig;
use Oro\Bundle\ApiBundle\Processor\CustomizeLoadedData\CustomizeLoadedDataContext;
use Oro\Bundle\ApiBundle\Util\DoctrineHelper;
use Oro\Component\ChainProcessor\ContextInterface;
use Oro\Component\ChainProcessor\ProcessorInterface;
use Oro\Component\EntitySerializer\EntitySerializer;

/**
 * Expands data for entities requested to be expanded (by the "include" filter) for ProductSearch entity.
 */
class ExpandProductSearchEntities implements ProcessorInterface
{
    /** @var EntitySerializer */
    private $entitySerializer;

    /** @var DoctrineHelper */
    private $doctrineHelper;

    /**
     * @param EntitySerializer $entitySerializer
     * @param DoctrineHelper   $doctrineHelper
     */
    public function __construct(EntitySerializer $entitySerializer, DoctrineHelper $doctrineHelper)
    {
        $this->entitySerializer = $entitySerializer;
        $this->doctrineHelper = $doctrineHelper;
    }

    /**
     * {@inheritdoc}
     */
    public function process(ContextInterface $context)
    {
        /** @var CustomizeLoadedDataContext $context */

        $config = $context->getConfig();
        $expandedAssociationNames = $this->getExpandedAssociationNames($config);
        if (empty($expandedAssociationNames)) {
            return;
        }

        $data = $context->getData();
        $normalizationContext = $context->getNormalizationContext();
        foreach ($expandedAssociationNames as $fieldName) {
            $field = $config->getField($fieldName);
            $targetIds = $this->getAssociationIds($data, $fieldName);
            if (!empty($targetIds)) {
                $associationData = $this->loadAssociationData(
                    $field->getTargetClass(),
                    $targetIds,
                    $field->getTargetEntity(),
                    $normalizationContext
                );
                $data = $this->applyAssociationData($data, $fieldName, $associationData);
            }
        }
        $context->setData($data);
    }

    /**
     * @param EntityDefinitionConfig $config
     *
     * @return string[]
     */
    private function getExpandedAssociationNames(EntityDefinitionConfig $config): array
    {
        $expandedAssociationNames = [];
        $fields = $config->getFields();
        foreach ($fields as $fieldName => $field) {
            if ($field->isExcluded()) {
                continue;
            }
            $targetConfig = $field->getTargetEntity();
            if (null === $targetConfig || $field->isCollectionValuedAssociation()) {
                continue;
            }
            if (!$this->doctrineHelper->isManageableEntityClass($field->getTargetClass())) {
                continue;
            }
            if ($targetConfig->isIdentifierOnlyRequested()) {
                continue;
            }
            $expandedAssociationNames[] = $fieldName;
        }

        return $expandedAssociationNames;
    }

    /**
     * @param EntityDefinitionConfig $config
     *
     * @return string|null
     */
    private function getIdentifierFieldName(EntityDefinitionConfig $config): ?string
    {
        $idFieldNames = $config->getIdentifierFieldNames();
        if (count($idFieldNames) === 1) {
            return reset($idFieldNames);
        }

        return null;
    }

    /**
     * @param array  $data
     * @param string $fieldName
     *
     * @return array
     */
    private function getAssociationIds(array $data, string $fieldName): array
    {
        $targetIds = [];
        foreach ($data as $item) {
            if (!isset($item[$fieldName])) {
                continue;
            }
            $targetIds[] = $item[$fieldName];
        }

        return $targetIds;
    }

    /**
     * @param array  $data
     * @param string $fieldName
     * @param array  $associationData
     *
     * @return array
     */
    private function applyAssociationData(array $data, string $fieldName, array $associationData): array
    {
        foreach ($data as $key => $item) {
            if (!isset($item[$fieldName])) {
                continue;
            }
            $targetId = $item[$fieldName];
            if (isset($associationData[$targetId])) {
                $data[$key][$fieldName] = $associationData[$targetId];
            }
        }

        return $data;
    }

    /**
     * @param string                 $entityClass
     * @param array                  $ids
     * @param EntityDefinitionConfig $config
     * @param array                  $normalizationContext
     *
     * @return array [id => entity data, ...]
     */
    private function loadAssociationData(
        string $entityClass,
        array $ids,
        EntityDefinitionConfig $config,
        array $normalizationContext
    ): array {
        $qb = $this->doctrineHelper
            ->createQueryBuilder($entityClass, 'e')
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
}
